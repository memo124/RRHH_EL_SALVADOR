<?php

namespace App\Services\Concerns;

use Illuminate\Support\Collection;

/**
 * Genera archivos planos (CSV / delimitados) para exportes de cumplimiento legal
 * (ISSS, AFP, Renta, INSAFORP). Formato: texto plano con BOM UTF-8 y delimitador
 * configurable, apto para carga en los portales de las instituciones salvadoreñas.
 */
trait BuildsDelimitedFile
{
    /**
     * @param string[] $headers
     * @param Collection<int, array<string, mixed>>|array<int, array<string, mixed>> $rows
     */
    protected function buildDelimited(array $headers, iterable $rows, string $delimiter = ';'): string
    {
        $lines = [$this->csvLine($headers, $delimiter)];

        foreach ($rows as $row) {
            $lines[] = $this->csvLine(array_values($row), $delimiter);
        }

        return "\xEF\xBB\xBF" . implode("\r\n", $lines);
    }

    protected function csvLine(array $fields, string $delimiter): string
    {
        $escaped = array_map(function ($field) use ($delimiter) {
            $field = (string) $field;
            if (str_contains($field, '"') || str_contains($field, $delimiter) || str_contains($field, "\n")) {
                return '"' . str_replace('"', '""', $field) . '"';
            }

            return $field;
        }, $fields);

        return implode($delimiter, $escaped);
    }

    protected function formatMonto(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
