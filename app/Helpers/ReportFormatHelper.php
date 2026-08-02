<?php

namespace App\Helpers;

class ReportFormatHelper
{
    /**
     * Divide etiquetas largas de encabezado con <br> para PDF/impresión.
     */
    public static function multilineHeader(string $text, int $maxCharsPerLine = 20): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }

        if (str_contains($text, ' / ')) {
            $parts = array_map('trim', explode(' / ', $text));

            return implode('<br>', array_map(
                fn (string $part) => self::wrapPart($part, $maxCharsPerLine),
                $parts
            ));
        }

        if (preg_match('/^(.+?)\s(\([^)]+\))$/u', $text, $matches)) {
            return e($matches[1]) . '<br>' . e($matches[2]);
        }

        return self::wrapPart($text, $maxCharsPerLine);
    }

    private static function wrapPart(string $text, int $maxCharsPerLine): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $maxCharsPerLine) {
            return e($text);
        }

        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($candidate) <= $maxCharsPerLine) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            // Mantener palabras completas; evitar cortes PRÉSTA/MO/PERSON/AL
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return implode('<br>', array_map(fn (string $line) => e($line), $lines));
    }
}
