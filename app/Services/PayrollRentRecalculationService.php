<?php

namespace App\Services;

use App\Models\Planilla;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PayrollRentRecalculationService
{
    /** Tipo planilla ordinaria: recibe el ajuste de renta en junio/diciembre. */
    public const TIPO_PLANILLA_ORDINARIA = 1;

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
     * Planillas cuyos montos suman al acumulado semestral (ordinaria, vacaciones, extraordinaria…).
     * Excluye aguinaldo y tipos sin renta.
     */
    public function aplicaAcumuladoRecalculo(int $tipoPlanillaId): bool
    {
        $tipo = DB::table('TIPO_PLANILLA')
            ->where('ID_TIPOPLANILLA', $tipoPlanillaId)
            ->first();

        return $tipo && (bool) $tipo->APLICA_RENTA;
    }

    /**
     * Solo la planilla ordinaria recibe el ajuste de junio/diciembre.
     * Vacaciones y otros tipos gravados aportan al acumulado pero retienen ISR normal del periodo.
     */
    public function aplicaAjusteRecalculoEnPlanilla(Planilla $planilla): bool
    {
        return (int) $planilla->ID_TIPOPLANILLA === self::TIPO_PLANILLA_ORDINARIA;
    }

    /**
     * Obtiene la renta retenida acumulada en el periodo de recálculo
     * (enero-junio o julio-diciembre) excluyendo la planilla actual.
     */
    public function getRentaAcumuladaPeriodo(int $empleadoId, Planilla $planilla, int $mes, int $anio): float
    {
        $mesInicio = $mes <= 6 ? 1 : 7;
        $mesFin = $mes <= 6 ? 5 : 11;

        $sum = $this->queryDetalleAcumulado($empleadoId, $planilla, $mesInicio, $mesFin, $anio)
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

        $rows = $this->queryDetalleAcumulado($empleadoId, $planilla, $mesInicio, $mesFin, $anio)
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

        if (!$this->esMesRecalculo($mes) || !$this->aplicaAjusteRecalculoEnPlanilla($planilla)) {
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

    /**
     * Detalle de planilla que aporta al acumulado semestral (incluye vacaciones).
     */
    protected function queryDetalleAcumulado(
        int $empleadoId,
        Planilla $planilla,
        int $mesInicio,
        int $mesFin,
        int $anio
    ): Builder {
        return DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $empleadoId)
            ->where('PLANILLA.ID_PLANILLA', '!=', $planilla->ID_PLANILLA)
            ->where('PLANILLA.ANULADA', false)
            ->where('PLANILLA.RECALCULADA', true)
            ->where('TIPO_PLANILLA.APLICA_RENTA', true)
            ->whereRaw('EXTRACT(YEAR FROM "PERIODO_LABORAL"."FECHAFIN") = ?', [$anio])
            ->whereRaw('EXTRACT(MONTH FROM "PERIODO_LABORAL"."FECHAFIN") BETWEEN ? AND ?', [$mesInicio, $mesFin]);
    }

    protected function resolverFrecuenciaMensual(int $frecuenciaIsrId): int
    {
        $mensual = DB::table('FRECUENCIA_ISR')
            ->whereRaw('UPPER("FRECUENCIAISR") LIKE ?', ['%MENSUAL%'])
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
