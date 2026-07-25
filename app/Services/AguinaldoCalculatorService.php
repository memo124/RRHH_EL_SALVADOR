<?php

namespace App\Services;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AguinaldoCalculatorService
{
    public function calcularDiasAguinaldo(Empleado $empleado): int
    {
        $anios = Carbon::parse($empleado->FECHAINGRESO)->floatDiffInYears(Carbon::now());
        $aniosInt = (int) floor($anios);

        $param = DB::table('PARAMETROS_AGUINALDOS')
            ->where('ID_EMPRESA', $empleado->ID_EMPRESA)
            ->where('DESDE_ANOS', '<=', $aniosInt)
            ->where('HASTA_ANOS', '>=', $aniosInt)
            ->first();

        if ($param) {
            return (int) $param->NUMERO_DIAS;
        }

        // Tabla default El Salvador
        if ($aniosInt < 1) return 15;
        if ($aniosInt < 3) return 19;
        if ($aniosInt < 10) return 21;
        return 30;
    }

    public function calcularMontoAguinaldo(Empleado $empleado): array
    {
        $dias = $this->calcularDiasAguinaldo($empleado);
        $salarioDiario = (float) $empleado->SALARIOMENSUAL / 30;
        $monto = round($salarioDiario * $dias, 2);

        return [
            'DIAS_AGUINALDO' => $dias,
            'MONTO_AGUINALDO' => $monto,
            'SALARIO_BASE' => (float) $empleado->SALARIOMENSUAL,
        ];
    }
}
