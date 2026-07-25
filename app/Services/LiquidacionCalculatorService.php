<?php

namespace App\Services;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidacionCalculatorService
{
    public function calcular(Empleado $empleado, Carbon $fechaLiquidacion, bool $incluirIndemnizacion = false): array
    {
        $fechaIngreso = Carbon::parse($empleado->FECHAINGRESO);
        $mesesTrabajados = max(1, $fechaIngreso->diffInMonths($fechaLiquidacion));
        $aniosTrabajados = $fechaIngreso->floatDiffInYears($fechaLiquidacion);
        $salario = (float) $empleado->SALARIOMENSUAL;
        $salarioDiario = $salario / 30;

        // Vacaciones proporcionales: 15 días por año
        $diasVacacion = ($mesesTrabajados / 12) * 15;
        $vacacionProporcional = round($salarioDiario * $diasVacacion, 2);

        // Aguinaldo proporcional: salario * meses / 12
        $aguinaldoProporcional = round($salario * ($mesesTrabajados % 12 ?: 12) / 12, 2);
        if ($mesesTrabajados < 12) {
            $aguinaldoProporcional = round($salario * ($mesesTrabajados / 12), 2);
        }

        // Indemnización: 30 días por año (despido injustificado)
        $indemnizacion = 0.00;
        if ($incluirIndemnizacion) {
            $indemnizacion = round($salarioDiario * 30 * max(1, floor($aniosTrabajados)), 2);
        }

        $devengado = $vacacionProporcional + $aguinaldoProporcional + $indemnizacion;

        // Descuentos ley simplificados sobre el devengado
        $isss = round(min($devengado, 1000) * 0.03, 2);
        $afp = round($devengado * 0.0725, 2);
        $renta = 0.00;
        $totalDescuentos = $isss + $afp + $renta;
        $liquido = round($devengado - $totalDescuentos, 2);

        return [
            'FECHA_CONTRATACION' => $fechaIngreso->toDateString(),
            'FECHA_LIQUIDACION' => $fechaLiquidacion->toDateString(),
            'SUELDO' => $salario,
            'VACACION_PROPORCIONAL' => $vacacionProporcional,
            'AGUINALDO_PROPORCIONAL' => $aguinaldoProporcional,
            'INDEMNIZACION_PROPORCIONAL' => $indemnizacion,
            'DEVENGADO' => $devengado,
            'ISSS' => $isss,
            'AFP' => $afp,
            'RENTA' => $renta,
            'TOTAL_DESCUENTOS' => $totalDescuentos,
            'LIQUIDO_A_RECIBIR' => $liquido,
        ];
    }
}
