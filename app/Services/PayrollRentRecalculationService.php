<?php

namespace App\Services;

use App\Models\Planilla;
use Illuminate\Support\Facades\DB;

class PayrollRentRecalculationService
{
    /** @var PayrollCalculatorService */
    protected $calculator;

    public function __construct(PayrollCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Meses en los que aplica recálculo de renta (junio y diciembre).
     */
    public function esMesRecalculo(int $mes): bool
    {
        return in_array($mes, [6, 12], true);
    }

    /**
     * Obtiene la renta retenida acumulada en el periodo de recálculo
     * (enero-junio o julio-diciembre) excluyendo la planilla actual.
     */
    public function getRentaAcumuladaPeriodo(int $empleadoId, Planilla $planilla, int $mes, int $anio): float
    {
        $mesInicio = $mes <= 6 ? 1 : 7;
        $mesFin = $mes <= 6 ? 5 : 11;

        $sum = DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $empleadoId)
            ->where('PLANILLA.ID_PLANILLA', '!=', $planilla->ID_PLANILLA)
            ->where('PLANILLA.ANULADA', false)
            ->where('PLANILLA.RECALCULADA', true)
            ->whereRaw('EXTRACT(YEAR FROM "PERIODO_LABORAL"."FECHAFIN") = ?', [$anio])
            ->whereRaw('EXTRACT(MONTH FROM "PERIODO_LABORAL"."FECHAFIN") BETWEEN ? AND ?', [$mesInicio, $mesFin])
            ->sum('DETALLE_PLANILLA.RENTA_EMPLEADO');

        return (float) ($sum ?? 0);
    }

    /**
     * Obtiene la base imponible ISR acumulada en el semestre.
     */
    public function getBaseIsrAcumuladaPeriodo(int $empleadoId, Planilla $planilla, int $mes, int $anio): float
    {
        $mesInicio = $mes <= 6 ? 1 : 7;
        $mesFin = $mes <= 6 ? 5 : 11;

        $rows = DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $empleadoId)
            ->where('PLANILLA.ID_PLANILLA', '!=', $planilla->ID_PLANILLA)
            ->where('PLANILLA.ANULADA', false)
            ->where('PLANILLA.RECALCULADA', true)
            ->whereRaw('EXTRACT(YEAR FROM "PERIODO_LABORAL"."FECHAFIN") = ?', [$anio])
            ->whereRaw('EXTRACT(MONTH FROM "PERIODO_LABORAL"."FECHAFIN") BETWEEN ? AND ?', [$mesInicio, $mesFin])
            ->select('DETALLE_PLANILLA.DEVENGADO_GRAVADO', 'DETALLE_PLANILLA.AFP_EMPLEADO', 'DETALLE_PLANILLA.ISSS_EMPLEADO')
            ->get();

        $base = 0.00;
        foreach ($rows as $row) {
            $basePeriodo = (float) $row->DEVENGADO_GRAVADO - (float) $row->AFP_EMPLEADO - (float) $row->ISSS_EMPLEADO;
            $base += max(0, $basePeriodo);
        }

        return round($base, 2);
    }

    /**
     * Recalcula renta en junio/diciembre: compara lo retenido vs lo que corresponde
     * sobre el acumulado semestral y ajusta la renta del periodo actual.
     */
    public function calcularRentaConRecalculo(
        float $baseIsrPeriodo,
        float $rentaPeriodoNormal,
        int $empleadoId,
        Planilla $planilla,
        int $frecuenciaIsrId,
        bool $aplicaTabla
    ): array {
        if (!$aplicaTabla) {
            return ['renta' => $rentaPeriodoNormal, 'ajuste' => 0.00, 'recalculada' => false];
        }

        $periodo = $planilla->periodoLaboral;
        $mes = (int) date('n', strtotime($periodo->FECHAFIN));
        $anio = (int) date('Y', strtotime($periodo->FECHAFIN));

        if (!$this->esMesRecalculo($mes)) {
            return ['renta' => $rentaPeriodoNormal, 'ajuste' => 0.00, 'recalculada' => false];
        }

        $baseAcumulada = $this->getBaseIsrAcumuladaPeriodo($empleadoId, $planilla, $mes, $anio);
        $baseTotalSemestre = $baseAcumulada + $baseIsrPeriodo;
        $rentaAcumulada = $this->getRentaAcumuladaPeriodo($empleadoId, $planilla, $mes, $anio);

        $frecuenciaMensual = $this->resolverFrecuenciaMensual($frecuenciaIsrId);
        $rentaDebidaSemestre = $this->calculator->calculateISR($baseTotalSemestre, $frecuenciaMensual);
        $ajuste = round($rentaDebidaSemestre - $rentaAcumulada - $rentaPeriodoNormal, 2);
        $rentaFinal = round(max(0, $rentaPeriodoNormal + $ajuste), 2);

        $this->registrarAcumulado($empleadoId, $planilla, $baseTotalSemestre, $rentaFinal, $ajuste, $mes, $anio);

        return [
            'renta' => $rentaFinal,
            'ajuste' => $ajuste,
            'recalculada' => true,
        ];
    }

    protected function resolverFrecuenciaMensual(int $frecuenciaIsrId): int
    {
        $mensual = DB::table('FRECUENCIA_ISR')
            ->whereRaw('UPPER("NOMBREFRECUENCIA") LIKE ?', ['%MENSUAL%'])
            ->value('ID_FRECUENCIAISR');

        return $mensual ? (int) $mensual : $frecuenciaIsrId;
    }

    protected function registrarAcumulado(
        int $empleadoId,
        Planilla $planilla,
        float $msr,
        float $renta,
        float $ajuste,
        int $mes,
        int $anio
    ): void {
        DB::table('ACUMULADO_RECALCULO')->insert([
            'ID_EMPLEADO' => $empleadoId,
            'ID_PLANILLA' => $planilla->ID_PLANILLA,
            'MSR' => $msr,
            'RENTA' => $renta,
            'RENTA_PENDIENTE_APLICAR' => max(0, $ajuste),
            'MES' => $mes,
            'ANIO' => $anio,
        ]);
    }
}
