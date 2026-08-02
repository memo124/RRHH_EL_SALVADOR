<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        $ultimaPlanilla = DB::table('PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->select(
                'PLANILLA.ID_PLANILLA',
                'PLANILLA.TITULO',
                'PLANILLA.CERRADA',
                'PLANILLA.RECALCULADA',
                'PERIODO_LABORAL.CALPERIODO',
                'TIPO_PLANILLA.TIPOPLANILLA'
            )
            ->where('PLANILLA.ANULADA', false)
            ->orderBy('PLANILLA.ID_PLANILLA', 'desc')
            ->first();

        $nominaUltimaPlanilla = 0.0;
        if ($ultimaPlanilla && $ultimaPlanilla->RECALCULADA) {
            $nominaUltimaPlanilla = (float) DB::table('DETALLE_PLANILLA')
                ->where('ID_PLANILLA', $ultimaPlanilla->ID_PLANILLA)
                ->sum('LIQUIDO_A_RECIBIR');
        }

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $limiteContratos = now()->addDays(30)->toDateString();

        return [
            'empleados_activos' => DB::table('EMPLEADO')->where('ESACTIVO', true)->count(),
            'empleados_nuevos_mes' => DB::table('EMPLEADO')
                ->where('ESACTIVO', true)
                ->whereBetween('FECHAINGRESO', [$inicioMes, $finMes])
                ->count(),
            'planillas_pendientes' => DB::table('PLANILLA')
                ->where('RECALCULADA', false)
                ->where('ANULADA', false)
                ->count(),
            'planillas_abiertas' => DB::table('PLANILLA')
                ->where('RECALCULADA', true)
                ->where('CERRADA', false)
                ->where('ANULADA', false)
                ->count(),
            'incapacidades_activas' => DB::table('INCAPACIDAD')
                ->where('ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
                ->where('FECHA_FIN', '>=', now()->toDateString())
                ->count(),
            'prestamos_activos' => DB::table('PRESTAMOS')
                ->where('PRESTAMOESTADO', true)
                ->where('SALDO_ACTUAL', '>', 0)
                ->count(),
            'marcaciones_pendientes' => DB::table('MARCACION_RAW')->where('PROCESADO', false)->count(),
            'contratos_por_vencer' => DB::table('CONTRATO')
                ->where('ESACTIVO', true)
                ->where('ESTADO', 'VIGENTE')
                ->where('SIN_FECHA_DEFINIDA', false)
                ->whereNotNull('FECHA_FIN')
                ->whereBetween('FECHA_FIN', [now()->toDateString(), $limiteContratos])
                ->count(),
            'nomina_ultima_planilla' => round($nominaUltimaPlanilla, 2),
            'ultima_planilla' => $ultimaPlanilla,
            'charts' => [
                'empleados_por_departamento' => $this->empleadosPorDepartamento(),
                'empleados_por_contratacion' => $this->empleadosPorTipoContratacion(),
                'nomina_ultimas_planillas' => $this->nominaUltimasPlanillas(),
                'planillas_por_estado' => $this->planillasPorEstado(),
                'incapacidades_por_mes' => $this->incapacidadesPorMes(),
            ],
            'alertas' => [
                'contratos_por_vencer' => $this->contratosPorVencerDetalle(),
            ],
        ];
    }

    protected function empleadosPorDepartamento(): array
    {
        $rows = DB::table('EMPLEADO')
            ->join('DEPARTAMENTO', 'EMPLEADO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->where('EMPLEADO.ESACTIVO', true)
            ->select('DEPARTAMENTO.NOMBREDEPARTAMENTO as label', DB::raw('COUNT(*) as total'))
            ->groupBy('DEPARTAMENTO.NOMBREDEPARTAMENTO')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function empleadosPorTipoContratacion(): array
    {
        $rows = DB::table('EMPLEADO')
            ->join('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->where('EMPLEADO.ESACTIVO', true)
            ->select('TIPO_CONTRATACION.TIPOCONTRATACION as label', DB::raw('COUNT(*) as total'))
            ->groupBy('TIPO_CONTRATACION.TIPOCONTRATACION')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function nominaUltimasPlanillas(): array
    {
        $planillas = DB::table('PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('PLANILLA.RECALCULADA', true)
            ->where('PLANILLA.ANULADA', false)
            ->select('PLANILLA.ID_PLANILLA', 'PLANILLA.TITULO', 'PERIODO_LABORAL.CALPERIODO', 'PERIODO_LABORAL.FECHAFIN')
            ->orderBy('PERIODO_LABORAL.FECHAFIN', 'desc')
            ->orderBy('PLANILLA.ID_PLANILLA', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $liquido = [];
        $empleados = [];

        foreach ($planillas as $plan) {
            $etiqueta = $plan->TITULO ?: $plan->CALPERIODO;
            $labels[] = '#' . $plan->ID_PLANILLA . ' · ' . $this->acortarEtiqueta($etiqueta, 18);
            $liquido[] = round((float) DB::table('DETALLE_PLANILLA')
                ->where('ID_PLANILLA', $plan->ID_PLANILLA)
                ->sum('LIQUIDO_A_RECIBIR'), 2);
            $empleados[] = (int) DB::table('DETALLE_PLANILLA')
                ->where('ID_PLANILLA', $plan->ID_PLANILLA)
                ->count();
        }

        return [
            'labels' => $labels,
            'liquido' => $liquido,
            'empleados' => $empleados,
        ];
    }

    protected function planillasPorEstado(): array
    {
        $pendientes = DB::table('PLANILLA')->where('RECALCULADA', false)->where('ANULADA', false)->count();
        $calculadas = DB::table('PLANILLA')->where('RECALCULADA', true)->where('CERRADA', false)->where('ANULADA', false)->count();
        $cerradas = DB::table('PLANILLA')->where('CERRADA', true)->where('ANULADA', false)->count();
        $anuladas = DB::table('PLANILLA')->where('ANULADA', true)->count();

        return [
            'labels' => ['Sin calcular', 'Calculada abierta', 'Cerrada', 'Anulada'],
            'values' => [(int) $pendientes, (int) $calculadas, (int) $cerradas, (int) $anuladas],
        ];
    }

    protected function incapacidadesPorMes(): array
    {
        $desde = now()->subMonths(5)->startOfMonth();

        $rows = DB::table('INCAPACIDAD')
            ->where('ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
            ->where('FECHA_INICIO', '>=', $desde->toDateString())
            ->select(
                DB::raw('EXTRACT(YEAR FROM "FECHA_INICIO") as anio'),
                DB::raw('EXTRACT(MONTH FROM "FECHA_INICIO") as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();

        $labels = [];
        $values = [];
        $cursor = $desde->copy();
        $mesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        for ($i = 0; $i < 6; $i++) {
            $labels[] = $mesesCortos[(int) $cursor->month - 1] . ' ' . $cursor->format('y');
            $match = $rows->first(fn ($r) => (int) $r->anio === (int) $cursor->year && (int) $r->mes === (int) $cursor->month);
            $values[] = $match ? (int) $match->total : 0;
            $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    protected function contratosPorVencerDetalle(): array
    {
        $limite = now()->addDays(30)->toDateString();

        return DB::table('CONTRATO')
            ->join('EMPLEADO', 'CONTRATO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('CONTRATO.ESACTIVO', true)
            ->where('CONTRATO.ESTADO', 'VIGENTE')
            ->where('CONTRATO.SIN_FECHA_DEFINIDA', false)
            ->whereNotNull('CONTRATO.FECHA_FIN')
            ->whereBetween('CONTRATO.FECHA_FIN', [now()->toDateString(), $limite])
            ->orderBy('CONTRATO.FECHA_FIN')
            ->limit(5)
            ->select(
                'CONTRATO.ID_CONTRATO',
                'CONTRATO.NUMERO_CONTRATO',
                'CONTRATO.FECHA_FIN',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" as nombre_empleado')
            )
            ->get()
            ->map(fn ($r) => [
                'id' => $r->ID_CONTRATO,
                'numero' => $r->NUMERO_CONTRATO,
                'empleado' => $r->nombre_empleado,
                'fecha_fin' => $r->FECHA_FIN,
            ])
            ->all();
    }

    protected function acortarEtiqueta(?string $texto, int $max = 22): string
    {
        if (!$texto) {
            return '—';
        }
        $texto = trim($texto);
        if (strlen($texto) <= $max) {
            return $texto;
        }

        return substr($texto, 0, $max - 1) . '…';
    }
}
