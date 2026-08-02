<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ErrorJournalService
{
    private const MAX_FILES = 3;

    private const DAYS_PER_FILE = 3;

    public function directory(): string
    {
        $dir = storage_path('app/error-journal');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function record(Throwable $exception, Request $request, ?string $reference = null): string
    {
        $reference = $reference ?? strtoupper(Str::substr(Str::uuid()->toString(), 0, 8));

        $entry = [
            'id' => $reference,
            'logged_at' => now()->toIso8601String(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatTrace($exception),
            'request' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'url' => $request->fullUrl(),
                'query' => $request->query(),
                'input' => $this->sanitizeInput($request->all()),
                'user_id' => $request->user()?->ID_USUARIO ?? null,
                'ip' => $request->ip(),
            ],
        ];

        $this->appendEntry($entry);
        $this->pruneOldFiles();

        return $reference;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFiles(): array
    {
        $files = $this->sortedJournalFiles();

        return array_map(function (string $path) {
            $name = basename($path);
            $payload = $this->readJournalFile($path);

            return [
                'name' => $name,
                'period_start' => $payload['period_start'] ?? null,
                'period_end' => $payload['period_end'] ?? null,
                'created_at' => $payload['created_at'] ?? null,
                'entries_count' => count($payload['entries'] ?? []),
            ];
        }, $files);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJournal(string $filename): array
    {
        $path = $this->resolveJournalPath($filename);
        $payload = $this->readJournalFile($path);

        return [
            'meta' => [
                'name' => basename($path),
                'period_start' => $payload['period_start'] ?? null,
                'period_end' => $payload['period_end'] ?? null,
                'created_at' => $payload['created_at'] ?? null,
                'entries_count' => count($payload['entries'] ?? []),
            ],
            'entries' => array_map(fn (array $entry) => $this->humanizeEntry($entry), $payload['entries'] ?? []),
            'raw_entries' => $payload['entries'] ?? [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatTrace(Throwable $exception): array
    {
        return collect($exception->getTrace())
            ->take(20)
            ->map(function (array $frame, int $index) {
                $file = $frame['file'] ?? 'unknown';
                $line = $frame['line'] ?? '?';
                $call = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');

                return [
                    'step' => $index + 1,
                    'call' => trim($call) ?: 'closure',
                    'location' => "{$file}:{$line}",
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function sanitizeInput(array $input): array
    {
        $hidden = ['password', 'password_confirmation', 'token', 'current_password'];

        foreach ($hidden as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = '[oculto]';
            }
        }

        return $input;
    }

    private function appendEntry(array $entry): void
    {
        $path = $this->currentJournalPath();
        $payload = file_exists($path)
            ? $this->readJournalFile($path)
            : $this->newJournalPayload();

        $payload['entries'][] = $entry;

        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function currentJournalPath(): string
    {
        $files = $this->sortedJournalFiles();
        $latest = $files !== [] ? end($files) : null;

        if ($latest !== null) {
            $payload = $this->readJournalFile($latest);
            $periodEnd = isset($payload['period_end'])
                ? strtotime((string) $payload['period_end'])
                : false;

            if ($periodEnd !== false && time() <= $periodEnd) {
                return $latest;
            }
        }

        $start = now()->startOfDay();
        $end = $start->copy()->addDays(self::DAYS_PER_FILE - 1)->endOfDay();
        $name = 'error-journal-' . $start->format('Y-m-d') . '.json';
        $path = $this->directory() . DIRECTORY_SEPARATOR . $name;

        if (!file_exists($path)) {
            file_put_contents(
                $path,
                json_encode($this->newJournalPayload($start, $end), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function newJournalPayload($start = null, $end = null): array
    {
        $start = $start ?? now()->startOfDay();
        $end = $end ?? $start->copy()->addDays(self::DAYS_PER_FILE - 1)->endOfDay();

        return [
            'period_start' => $start->toIso8601String(),
            'period_end' => $end->toIso8601String(),
            'created_at' => now()->toIso8601String(),
            'entries' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJournalFile(string $path): array
    {
        if (!file_exists($path)) {
            return $this->newJournalPayload();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : $this->newJournalPayload();
    }

    /**
     * @return array<int, string>
     */
    private function sortedJournalFiles(): array
    {
        $files = glob($this->directory() . DIRECTORY_SEPARATOR . 'error-journal-*.json') ?: [];
        sort($files, SORT_STRING);

        return $files;
    }

    private function pruneOldFiles(): void
    {
        $files = $this->sortedJournalFiles();
        while (count($files) > self::MAX_FILES) {
            $oldest = array_shift($files);
            if ($oldest && file_exists($oldest)) {
                unlink($oldest);
            }
        }
    }

    private function resolveJournalPath(string $filename): string
    {
        $safe = basename($filename);
        if (!preg_match('/^error-journal-\d{4}-\d{2}-\d{2}\.json$/', $safe)) {
            abort(404, 'Archivo de errores no encontrado.');
        }

        $path = $this->directory() . DIRECTORY_SEPARATOR . $safe;
        if (!file_exists($path)) {
            abort(404, 'Archivo de errores no encontrado.');
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function humanizeEntry(array $entry): array
    {
        $request = $entry['request'] ?? [];

        return [
            'referencia' => $entry['id'] ?? '',
            'fecha' => isset($entry['logged_at'])
                ? date('d/m/Y H:i:s', strtotime((string) $entry['logged_at']))
                : '',
            'resumen' => $entry['message'] ?? '',
            'tipo' => $entry['exception'] ?? '',
            'ubicacion' => isset($entry['file'], $entry['line'])
                ? ($entry['file'] . ':' . $entry['line'])
                : '',
            'peticion' => trim(($request['method'] ?? '') . ' ' . ($request['path'] ?? '')),
            'usuario_id' => $request['user_id'] ?? null,
            'ip' => $request['ip'] ?? null,
            'trace' => collect($entry['trace'] ?? [])
                ->map(fn (array $step) => "#{$step['step']} {$step['call']} — {$step['location']}")
                ->values()
                ->all(),
        ];
    }
}
