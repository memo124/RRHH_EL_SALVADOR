<?php

namespace App\Services;

class NumeroALetrasService
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
        'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS',
        'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];

    private const DECENAS = [
        '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    /**
     * Convierte un monto numérico a letras en español (El Salvador).
     * Ejemplo: 1250.50 → "MIL DOSCIENTOS CINCUENTA DÓLARES CON 50/100"
     */
    public function convertir(float|int|string $numero, string $moneda = 'DÓLARES'): string
    {
        $numero = round((float) $numero, 2);
        $parteEntera = (int) floor($numero);
        $centavos = (int) round(($numero - $parteEntera) * 100);

        if ($parteEntera === 0 && $centavos === 0) {
            return "CERO {$moneda} CON 00/100";
        }

        $letras = $this->enteroALetras($parteEntera);
        $letras = $this->ajustarUno($letras);

        return "{$letras} {$moneda} CON " . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100';
    }

    public function enteroALetras(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }
        if ($n < 0) {
            return 'MENOS ' . $this->enteroALetras(abs($n));
        }
        if ($n < 30) {
            return self::UNIDADES[$n];
        }
        if ($n < 100) {
            return $this->decenasALetras($n);
        }
        if ($n < 1000) {
            return $this->centenasALetras($n);
        }
        if ($n < 1000000) {
            return $this->milesALetras($n);
        }
        if ($n < 1000000000) {
            return $this->millonesALetras($n);
        }

        return number_format($n, 0, '', '.');
    }

    private function decenasALetras(int $n): string
    {
        if ($n < 30) {
            return self::UNIDADES[$n];
        }
        $decena = intdiv($n, 10);
        $unidad = $n % 10;
        if ($unidad === 0) {
            return self::DECENAS[$decena];
        }

        return self::DECENAS[$decena] . ' Y ' . self::UNIDADES[$unidad];
    }

    private function centenasALetras(int $n): string
    {
        if ($n === 100) {
            return 'CIEN';
        }
        $centena = intdiv($n, 100);
        $resto = $n % 100;

        return trim(self::CENTENAS[$centena] . ' ' . ($resto > 0 ? $this->enteroALetras($resto) : ''));
    }

    private function milesALetras(int $n): string
    {
        $miles = intdiv($n, 1000);
        $resto = $n % 1000;

        $textoMiles = $miles === 1 ? 'MIL' : $this->enteroALetras($miles) . ' MIL';
        if ($resto === 0) {
            return $textoMiles;
        }

        return trim($textoMiles . ' ' . $this->enteroALetras($resto));
    }

    private function millonesALetras(int $n): string
    {
        $millones = intdiv($n, 1000000);
        $resto = $n % 1000000;

        $textoMillones = $millones === 1 ? 'UN MILLÓN' : $this->enteroALetras($millones) . ' MILLONES';
        if ($resto === 0) {
            return $textoMillones;
        }

        return trim($textoMillones . ' ' . $this->enteroALetras($resto));
    }

    private function ajustarUno(string $texto): string
    {
        return preg_replace('/\bUNO\b/u', 'UN', $texto) ?? $texto;
    }
}
