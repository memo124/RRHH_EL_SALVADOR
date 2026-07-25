<?php

namespace App\Services;

use App\Models\Planilla;
use Illuminate\Support\Facades\DB;

class PayrollMonthlyCeilingService
{
    public const TECHO_ISSS = 1000.00;
    public const TECHO_INSAFORP = 1000.00;

    /**
     * Obtiene el devengado gravado acumulado del mes para un empleado,
     * excluyendo la planilla actual (planillas ya calculadas y no anuladas).
     */
    public function getDevengadoAcumuladoMes(int $empleadoId, Planilla $planilla): float
    {
        $periodo = $planilla->periodoLaboral;
        if (!$periodo) {
            return 0.00;
        }

        $mes = (int) date('n', strtotime($periodo->FECHAFIN));
        $anio = (int) date('Y', strtotime($periodo->FECHAFIN));

        $sum = DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $empleadoId)
            ->where('PLANILLA.ID_PLANILLA', '!=', $planilla->ID_PLANILLA)
            ->where('PLANILLA.ANULADA', false)
            ->where('PLANILLA.RECALCULADA', true)
            ->whereRaw('EXTRACT(MONTH FROM "PERIODO_LABORAL"."FECHAFIN") = ?', [$mes])
            ->whereRaw('EXTRACT(YEAR FROM "PERIODO_LABORAL"."FECHAFIN") = ?', [$anio])
            ->sum('DETALLE_PLANILLA.DEVENGADO_GRAVADO');

        return (float) ($sum ?? 0);
    }

    /**
     * Calcula ISSS empleado/patronal respetando el techo mensual de $1,000.
     */
    public function calcularIsss(float $devengadoPeriodo, float $devengadoAcumuladoMes, bool $aplica): array
    {
        if (!$aplica) {
            return ['empleado' => 0.00, 'patronal' => 0.00, 'base' => 0.00];
        }

        $techo = self::TECHO_ISSS;
        $baseYaConsumida = min($devengadoAcumuladoMes, $techo);
        $baseDisponible = max(0, $techo - $baseYaConsumida);
        $baseIsss = min($devengadoPeriodo, $baseDisponible);

        return [
            'empleado' => round($baseIsss * 0.03, 2),
            'patronal' => round($baseIsss * 0.075, 2),
            'base' => round($baseIsss, 2),
        ];
    }

    /**
     * Calcula INSAFORP patronal respetando el techo mensual de $1,000.
     */
    public function calcularInsaforp(float $devengadoPeriodo, float $devengadoAcumuladoMes, bool $aplica, bool $empresaAplica): float
    {
        if (!$aplica || !$empresaAplica) {
            return 0.00;
        }

        $techo = self::TECHO_INSAFORP;
        $baseYaConsumida = min($devengadoAcumuladoMes, $techo);
        $baseDisponible = max(0, $techo - $baseYaConsumida);
        $baseInsaforp = min($devengadoPeriodo, $baseDisponible);

        return round($baseInsaforp * 0.01, 2);
    }

    public function empresaAplicaInsaforp(int $empresaId): bool
    {
        $numEmpleados = DB::table('EMPLEADO')
            ->where('ID_EMPRESA', $empresaId)
            ->where('ESACTIVO', true)
            ->count();

        return $numEmpleados > 10;
    }
}
