<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmpresaLogoService
{
    /**
     * Resuelve el logo de la empresa como data URI (ideal para PDF) o URL pública.
     */
    public function resolve(int $empresaId, bool $preferDataUri = true): ?string
    {
        $empresa = DB::table('EMPRESA')->where('ID_EMPRESA', $empresaId)->first();
        if (!$empresa) {
            return null;
        }

        if (!empty($empresa->URL_LOGO)) {
            $resolved = $this->resolveUrl($empresa->URL_LOGO, $preferDataUri);
            if ($resolved) {
                return $resolved;
            }
        }

        $stored = $this->findStoredLogo($empresaId);
        if ($stored) {
            return $preferDataUri ? $this->fileToDataUri($stored) : Storage::url("logos/empresa_{$empresaId}." . pathinfo($stored, PATHINFO_EXTENSION));
        }

        $default = public_path('images/logos/empresa-default.svg');
        if (is_file($default)) {
            return $preferDataUri ? $this->fileToDataUri($default) : '/images/logos/empresa-default.svg';
        }

        return null;
    }

    public function resolveDataUri(int $empresaId): ?string
    {
        return $this->resolve($empresaId, true);
    }

    public function resolvePublicUrl(int $empresaId): ?string
    {
        return $this->resolve($empresaId, false);
    }

    protected function resolveUrl(string $url, bool $preferDataUri): ?string
    {
        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $preferDataUri ? $this->remoteToDataUri($url) : $url;
        }

        $path = $this->pathFromPublicUrl($url);
        if ($path && is_file($path)) {
            return $preferDataUri ? $this->fileToDataUri($path) : $url;
        }

        return null;
    }

    protected function pathFromPublicUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        if (str_starts_with($path, '/storage/')) {
            return storage_path('app/public/' . ltrim(substr($path, strlen('/storage/')), '/'));
        }

        if (str_starts_with($path, '/')) {
            return public_path(ltrim($path, '/'));
        }

        return public_path($path);
    }

    protected function findStoredLogo(int $empresaId): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            $relative = "logos/empresa_{$empresaId}.{$ext}";
            if (Storage::disk('public')->exists($relative)) {
                return Storage::disk('public')->path($relative);
            }
        }

        return null;
    }

    protected function fileToDataUri(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    protected function remoteToDataUri(string $url): ?string
    {
        try {
            $contents = @file_get_contents($url);
            if ($contents === false) {
                return $url;
            }
            $mime = 'image/png';
            if (str_contains($url, '.svg')) {
                $mime = 'image/svg+xml';
            } elseif (str_contains($url, '.jpg') || str_contains($url, '.jpeg')) {
                $mime = 'image/jpeg';
            }

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable) {
            return $url;
        }
    }
}
