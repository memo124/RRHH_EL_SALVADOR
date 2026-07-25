<?php

namespace App\Services;

use App\Models\Empleado;

class HorasExtrasCalculatorService
{
    public const INICIO_JORNADA_DIURNA = '06:00:00';
    public const FIN_JORNADA_DIURNA = '19:00:00';

    public function salarioHoraOrdinaria(Empleado $empleado): float
    {
        if ((float) $empleado->SALARIODIARIO > 0) {
            return round((float) $empleado->SALARIODIARIO / 8, 4);
        }

        return round((float) $empleado->SALARIOMENSUAL / 30 / 8, 4);
    }

    public function calcularMonto(Empleado $empleado, float $horas, float $factor): float
    {
        return round($this->salarioHoraOrdinaria($empleado) * $horas * $factor, 2);
    }

    /**
     * Divide horas totales en fijas (autorizadas) y adicionales (excedente).
     */
    public function dividirFijaAdicional(float $horas, float $cuposFijasRestantes): array
    {
        $fijas = min($horas, max(0, $cuposFijasRestantes));
        $adicionales = max(0, $horas - $fijas);

        return [
            'fijas' => round($fijas, 2),
            'adicionales' => round($adicionales, 2),
            'restantes' => round(max(0, $cuposFijasRestantes - $fijas), 2),
        ];
    }

    /**
     * Clasifica minutos de horas extra entre jornada diurna (06:00-19:00) y nocturna.
     */
    public function clasificarMinutosPorJornada(\DateTimeInterface $inicioExtra, \DateTimeInterface $finExtra): array
    {
        $cursor = \Carbon\Carbon::instance($inicioExtra instanceof \Carbon\Carbon ? $inicioExtra : \Carbon\Carbon::parse($inicioExtra));
        $fin = \Carbon\Carbon::instance($finExtra instanceof \Carbon\Carbon ? $finExtra : \Carbon\Carbon::parse($finExtra));

        $minutosDiurnos = 0;
        $minutosNocturnos = 0;

        while ($cursor->lt($fin)) {
            $inicioDiurno = $cursor->copy()->setTimeFromTimeString(self::INICIO_JORNADA_DIURNA);
            $finDiurno = $cursor->copy()->setTimeFromTimeString(self::FIN_JORNADA_DIURNA);

            if ($cursor->lt($inicioDiurno)) {
                $segmentoFin = $fin->lt($inicioDiurno) ? $fin : $inicioDiurno;
                $minutosNocturnos += $cursor->diffInMinutes($segmentoFin);
                $cursor = $segmentoFin;
                continue;
            }

            if ($cursor->lt($finDiurno)) {
                $segmentoFin = $fin->lt($finDiurno) ? $fin : $finDiurno;
                $minutosDiurnos += $cursor->diffInMinutes($segmentoFin);
                $cursor = $segmentoFin;
                continue;
            }

            $inicioNocturnoSiguiente = $cursor->copy()->addDay()->setTimeFromTimeString(self::INICIO_JORNADA_DIURNA);
            $segmentoFin = $fin->lt($inicioNocturnoSiguiente) ? $fin : $inicioNocturnoSiguiente;
            $minutosNocturnos += $cursor->diffInMinutes($segmentoFin);
            $cursor = $segmentoFin;
        }

        return [
            'diurnas' => round($minutosDiurnos / 60, 2),
            'nocturnas' => round($minutosNocturnos / 60, 2),
        ];
    }
}
