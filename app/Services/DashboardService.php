<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function getStats(?int $empresaId = null): array
    {
        $ultimaPlanilla = $this->ultimaPlanilla($empresaId);
        $nominaUltimaPlanilla = 0.0;

        if ($ultimaPlanilla && $ultimaPlanilla->RECALCULADA) {
            $nominaUltimaPlanilla = (float) DB::table('DETALLE_PLANILLA')
                ->where('ID_PLANILLA', $ultimaPlanilla->ID_PLANILLA)
                ->sum('LIQUIDO_A_RECIBIR');
        }

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $limiteContratos = now()->addDays(30)->toDateString();

        $charts = [
            'empleados_por_departamento' => $this->empleadosPorDepartamento($empresaId),
            'empleados_por_contratacion' => $this->empleadosPorTipoContratacion($empresaId),
            'nomina_ultimas_planillas' => $this->nominaUltimasPlanillas($empresaId),
            'planillas_por_estado' => $this->planillasPorEstado($empresaId),
            'incapacidades_por_mes' => $this->incapacidadesPorMes($empresaId),
            'desglose_costo_nomina' => $this->desgloseCostoNomina($ultimaPlanilla),
            'prestamos_por_tipo' => $this->prestamosPorTipo($empresaId),
            'altas_por_mes' => $this->altasPorMes($empresaId),
        ];

        if ($this->hasGhTables()) {
            $charts['permisos_por_estado'] = $this->permisosPorEstado($empresaId);
            $charts['reclutamiento_pipeline'] = $this->reclutamientoPipeline($empresaId);
        }

        return [
            'filtro_empresa' => $empresaId,
            'empleados_activos' => $this->countEmpleadosActivos($empresaId),
            'empleados_nuevos_mes' => $this->countEmpleadosNuevosMes($empresaId, $inicioMes, $finMes),
            'planillas_pendientes' => $this->countPlanillas($empresaId, false, null),
            'planillas_abiertas' => $this->countPlanillas($empresaId, true, false),
            'incapacidades_activas' => $this->countIncapacidadesActivas($empresaId),
            'prestamos_activos' => $this->countPrestamosActivos($empresaId),
            'prestamos_saldo_total' => round($this->sumPrestamosSaldo($empresaId), 2),
            'marcaciones_pendientes' => $this->countMarcacionesPendientes($empresaId),
            'contratos_por_vencer' => $this->countContratosPorVencer($empresaId, $limiteContratos),
            'nomina_ultima_planilla' => round($nominaUltimaPlanilla, 2),
            'ultima_planilla' => $ultimaPlanilla,
            'kpis_gh' => $this->hasGhTables() ? $this->kpisGestionHumana($empresaId) : null,
            'charts' => $charts,
            'alertas' => [
                'contratos_por_vencer' => $this->contratosPorVencerDetalle($empresaId, $limiteContratos),
                'permisos_pendientes' => $this->hasGhTables()
                    ? $this->permisosPendientesDetalle($empresaId)
                    : [],
            ],
        ];
    }

    protected function ultimaPlanilla(?int $empresaId): ?object
    {
        $query = DB::table('PLANILLA')
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
            ->where('PLANILLA.ANULADA', false);

        if ($empresaId) {
            $query->where('PLANILLA.ID_EMPRESA', $empresaId);
        }

        return $query->orderBy('PLANILLA.ID_PLANILLA', 'desc')->first();
    }

    protected function empleadoQuery(?int $empresaId)
    {
        $query = DB::table('EMPLEADO')->where('EMPLEADO.ESACTIVO', true);
        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return $query;
    }

    protected function planillaQuery(?int $empresaId)
    {
        $query = DB::table('PLANILLA')->where('PLANILLA.ANULADA', false);
        if ($empresaId) {
            $query->where('PLANILLA.ID_EMPRESA', $empresaId);
        }

        return $query;
    }

    protected function countEmpleadosActivos(?int $empresaId): int
    {
        return (int) $this->empleadoQuery($empresaId)->count();
    }

    protected function countEmpleadosNuevosMes(?int $empresaId, string $inicio, string $fin): int
    {
        return (int) $this->empleadoQuery($empresaId)
            ->whereBetween('FECHAINGRESO', [$inicio, $fin])
            ->count();
    }

    protected function countPlanillas(?int $empresaId, bool $recalculada, ?bool $cerrada): int
    {
        $query = $this->planillaQuery($empresaId)->where('RECALCULADA', $recalculada);
        if ($cerrada !== null) {
            $query->where('CERRADA', $cerrada);
        }

        return (int) $query->count();
    }

    protected function countIncapacidadesActivas(?int $empresaId): int
    {
        $query = DB::table('INCAPACIDAD')
            ->join('EMPLEADO', 'INCAPACIDAD.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('INCAPACIDAD.ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
            ->where('INCAPACIDAD.FECHA_FIN', '>=', now()->toDateString());

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return (int) $query->count();
    }

    protected function countPrestamosActivos(?int $empresaId): int
    {
        $query = DB::table('PRESTAMOS')
            ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('PRESTAMOS.PRESTAMOESTADO', true)
            ->where('PRESTAMOS.SALDO_ACTUAL', '>', 0);

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return (int) $query->count();
    }

    protected function sumPrestamosSaldo(?int $empresaId): float
    {
        $query = DB::table('PRESTAMOS')
            ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('PRESTAMOS.PRESTAMOESTADO', true)
            ->where('PRESTAMOS.SALDO_ACTUAL', '>', 0);

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return (float) ($query->sum('PRESTAMOS.SALDO_ACTUAL') ?? 0);
    }

    protected function countMarcacionesPendientes(?int $empresaId): int
    {
        $query = DB::table('MARCACION_RAW')
            ->join('EMPLEADO', 'MARCACION_RAW.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('MARCACION_RAW.PROCESADO', false);

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return (int) $query->count();
    }

    protected function countContratosPorVencer(?int $empresaId, string $limite): int
    {
        $query = DB::table('CONTRATO')
            ->join('EMPLEADO', 'CONTRATO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('CONTRATO.ESACTIVO', true)
            ->where('CONTRATO.ESTADO', 'VIGENTE')
            ->where('CONTRATO.SIN_FECHA_DEFINIDA', false)
            ->whereNotNull('CONTRATO.FECHA_FIN')
            ->whereBetween('CONTRATO.FECHA_FIN', [now()->toDateString(), $limite]);

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return (int) $query->count();
    }

    protected function empleadosPorDepartamento(?int $empresaId): array
    {
        $query = $this->empleadoQuery($empresaId)
            ->join('DEPARTAMENTO', 'EMPLEADO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->select('DEPARTAMENTO.NOMBREDEPARTAMENTO as label', DB::raw('COUNT(*) as total'))
            ->groupBy('DEPARTAMENTO.NOMBREDEPARTAMENTO')
            ->orderByDesc('total')
            ->limit(8);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function empleadosPorTipoContratacion(?int $empresaId): array
    {
        $rows = $this->empleadoQuery($empresaId)
            ->join('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->select('TIPO_CONTRATACION.TIPOCONTRATACION as label', DB::raw('COUNT(*) as total'))
            ->groupBy('TIPO_CONTRATACION.TIPOCONTRATACION')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function nominaUltimasPlanillas(?int $empresaId): array
    {
        $query = DB::table('PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->where('PLANILLA.RECALCULADA', true)
            ->where('PLANILLA.ANULADA', false)
            ->select('PLANILLA.ID_PLANILLA', 'PLANILLA.TITULO', 'PERIODO_LABORAL.CALPERIODO', 'PERIODO_LABORAL.FECHAFIN');

        if ($empresaId) {
            $query->where('PLANILLA.ID_EMPRESA', $empresaId);
        }

        $planillas = $query
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

    protected function planillasPorEstado(?int $empresaId): array
    {
        $base = $this->planillaQuery($empresaId);
        $pendientes = (clone $base)->where('RECALCULADA', false)->count();
        $calculadas = (clone $base)->where('RECALCULADA', true)->where('CERRADA', false)->count();
        $cerradas = (clone $base)->where('CERRADA', true)->count();
        $anuladasQuery = DB::table('PLANILLA')->where('ANULADA', true);
        if ($empresaId) {
            $anuladasQuery->where('ID_EMPRESA', $empresaId);
        }
        $anuladas = $anuladasQuery->count();

        return [
            'labels' => ['Sin calcular', 'Calculada abierta', 'Cerrada', 'Anulada'],
            'values' => [(int) $pendientes, (int) $calculadas, (int) $cerradas, (int) $anuladas],
        ];
    }

    protected function incapacidadesPorMes(?int $empresaId): array
    {
        $desde = now()->subMonths(5)->startOfMonth();

        $query = DB::table('INCAPACIDAD')
            ->join('EMPLEADO', 'INCAPACIDAD.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('INCAPACIDAD.ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
            ->where('INCAPACIDAD.FECHA_INICIO', '>=', $desde->toDateString());

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        $rows = $query
            ->select(
                DB::raw('EXTRACT(YEAR FROM "INCAPACIDAD"."FECHA_INICIO") as anio'),
                DB::raw('EXTRACT(MONTH FROM "INCAPACIDAD"."FECHA_INICIO") as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();

        return $this->serieUltimosSeisMeses($rows);
    }

    protected function desgloseCostoNomina(?object $ultimaPlanilla): array
    {
        if (!$ultimaPlanilla || !$ultimaPlanilla->RECALCULADA) {
            return ['labels' => [], 'values' => [], 'planilla_id' => null];
        }

        $row = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $ultimaPlanilla->ID_PLANILLA)
            ->selectRaw('
                COALESCE(SUM("AFP_EMPLEADO"), 0) as afp,
                COALESCE(SUM("ISSS_EMPLEADO"), 0) as isss,
                COALESCE(SUM("RENTA_EMPLEADO"), 0) as renta,
                COALESCE(SUM("PRESTAMOS"), 0) as prestamos,
                COALESCE(SUM("OTRO_DESCUENTOS"), 0) as otros_desc,
                COALESCE(SUM("LIQUIDO_A_RECIBIR"), 0) as liquido,
                COALESCE(SUM("TOTAL_DEVENGADO"), 0) as devengado,
                COALESCE(SUM("AFP_PATRONAL"), 0) as afp_patronal,
                COALESCE(SUM("ISSS_PATRONAL"), 0) as isss_patronal,
                COALESCE(SUM("INSAFORP_PATRONAL"), 0) as insaforp
            ')
            ->first();

        if (!$row) {
            return ['labels' => [], 'values' => [], 'planilla_id' => $ultimaPlanilla->ID_PLANILLA];
        }

        $labels = [
            'Devengado',
            'AFP empleado',
            'ISSS empleado',
            'Renta (ISR)',
            'Préstamos',
            'Otros descuentos',
            'Líquido a pagar',
            'Carga patronal',
        ];

        $cargaPatronal = (float) $row->afp_patronal + (float) $row->isss_patronal + (float) $row->insaforp;

        $values = [
            round((float) $row->devengado, 2),
            round((float) $row->afp, 2),
            round((float) $row->isss, 2),
            round((float) $row->renta, 2),
            round((float) $row->prestamos, 2),
            round((float) $row->otros_desc, 2),
            round((float) $row->liquido, 2),
            round($cargaPatronal, 2),
        ];

        return [
            'labels' => $labels,
            'values' => $values,
            'planilla_id' => $ultimaPlanilla->ID_PLANILLA,
            'planilla_titulo' => $ultimaPlanilla->TITULO,
        ];
    }

    protected function prestamosPorTipo(?int $empresaId): array
    {
        $query = DB::table('PRESTAMOS')
            ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
            ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('PRESTAMOS.PRESTAMOESTADO', true)
            ->where('PRESTAMOS.SALDO_ACTUAL', '>', 0)
            ->select('TIPO_PRESTAMO.NOMBREPRESTAMO as label', DB::raw('SUM("PRESTAMOS"."SALDO_ACTUAL") as total'))
            ->groupBy('TIPO_PRESTAMO.NOMBREPRESTAMO')
            ->orderByDesc('total')
            ->limit(8);

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    protected function altasPorMes(?int $empresaId): array
    {
        $desde = now()->subMonths(5)->startOfMonth();

        $query = DB::table('EMPLEADO')
            ->where('FECHAINGRESO', '>=', $desde->toDateString());

        if ($empresaId) {
            $query->where('ID_EMPRESA', $empresaId);
        }

        $rows = $query
            ->select(
                DB::raw('EXTRACT(YEAR FROM "FECHAINGRESO") as anio'),
                DB::raw('EXTRACT(MONTH FROM "FECHAINGRESO") as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();

        return $this->serieUltimosSeisMeses($rows);
    }

    protected function permisosPorEstado(?int $empresaId): array
    {
        $query = DB::table('SOLICITUD_PERMISO')
            ->join('EMPLEADO', 'SOLICITUD_PERMISO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->select('SOLICITUD_PERMISO.ESTADO as label', DB::raw('COUNT(*) as total'))
            ->groupBy('SOLICITUD_PERMISO.ESTADO');

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        $rows = $query->orderByDesc('total')->get();
        $labels = $rows->pluck('label')->map(fn ($l) => ucfirst((string) $l))->all();

        return [
            'labels' => $labels,
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function reclutamientoPipeline(?int $empresaId): array
    {
        $query = DB::table('CANDIDATO')
            ->join('VACANTE', 'CANDIDATO.ID_VACANTE', '=', 'VACANTE.ID_VACANTE')
            ->join('ETAPA_RECLUTAMIENTO', 'CANDIDATO.ID_ETAPA_ACTUAL', '=', 'ETAPA_RECLUTAMIENTO.ID_ETAPA')
            ->where('CANDIDATO.ESACTIVO', true)
            ->where('CANDIDATO.ESTADO', '!=', 'contratado')
            ->select('ETAPA_RECLUTAMIENTO.NOMBRE as label', DB::raw('COUNT(*) as total'))
            ->groupBy('ETAPA_RECLUTAMIENTO.NOMBRE', 'ETAPA_RECLUTAMIENTO.ORDEN')
            ->orderBy('ETAPA_RECLUTAMIENTO.ORDEN');

        if ($empresaId) {
            $query->where('VACANTE.ID_EMPRESA', $empresaId);
        }

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function kpisGestionHumana(?int $empresaId): array
    {
        $permisosPendientes = DB::table('SOLICITUD_PERMISO')
            ->join('EMPLEADO', 'SOLICITUD_PERMISO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->where('SOLICITUD_PERMISO.ESTADO', 'pendiente');

        if ($empresaId) {
            $permisosPendientes->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        $vacantesAbiertas = DB::table('VACANTE')
            ->where('ESACTIVO', true)
            ->where('ESTADO', 'abierta');

        if ($empresaId) {
            $vacantesAbiertas->where('ID_EMPRESA', $empresaId);
        }

        $candidatosActivos = DB::table('CANDIDATO')
            ->join('VACANTE', 'CANDIDATO.ID_VACANTE', '=', 'VACANTE.ID_VACANTE')
            ->where('CANDIDATO.ESACTIVO', true)
            ->where('CANDIDATO.ESTADO', 'activo');

        if ($empresaId) {
            $candidatosActivos->where('VACANTE.ID_EMPRESA', $empresaId);
        }

        $capacitacionesPublicadas = DB::table('CAPACITACION')
            ->where('ESACTIVO', true)
            ->where('ESTADO', 'publicada');

        if ($empresaId) {
            $capacitacionesPublicadas->where(function ($q) use ($empresaId) {
                $q->where('ID_EMPRESA', $empresaId)->orWhereNull('ID_EMPRESA');
            });
        }

        return [
            'permisos_pendientes' => (int) $permisosPendientes->count(),
            'vacantes_abiertas' => (int) $vacantesAbiertas->count(),
            'candidatos_activos' => (int) $candidatosActivos->count(),
            'capacitaciones_publicadas' => (int) $capacitacionesPublicadas->count(),
        ];
    }

    protected function contratosPorVencerDetalle(?int $empresaId, string $limite): array
    {
        $query = DB::table('CONTRATO')
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
            );

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return $query->get()
            ->map(fn ($r) => [
                'id' => $r->ID_CONTRATO,
                'numero' => $r->NUMERO_CONTRATO,
                'empleado' => $r->nombre_empleado,
                'fecha_fin' => $r->FECHA_FIN,
            ])
            ->all();
    }

    protected function permisosPendientesDetalle(?int $empresaId): array
    {
        $query = DB::table('SOLICITUD_PERMISO')
            ->join('EMPLEADO', 'SOLICITUD_PERMISO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->where('SOLICITUD_PERMISO.ESTADO', 'pendiente')
            ->orderBy('SOLICITUD_PERMISO.FECHA_SOLICITUD')
            ->limit(5)
            ->select(
                'SOLICITUD_PERMISO.ID_SOLICITUD',
                'SOLICITUD_PERMISO.FECHA_INICIO',
                'SOLICITUD_PERMISO.DIAS_SOLICITADOS',
                'TIPO_PERMISO_LABORAL.NOMBRE as tipo',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" as empleado')
            );

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        return $query->get()
            ->map(fn ($r) => [
                'id' => $r->ID_SOLICITUD,
                'empleado' => $r->empleado,
                'tipo' => $r->tipo,
                'fecha_inicio' => $r->FECHA_INICIO,
                'dias' => (float) $r->DIAS_SOLICITADOS,
            ])
            ->all();
    }

    protected function serieUltimosSeisMeses($rows): array
    {
        $desde = now()->subMonths(5)->startOfMonth();
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

    protected function hasGhTables(): bool
    {
        return Schema::hasTable('SOLICITUD_PERMISO')
            && Schema::hasTable('VACANTE')
            && Schema::hasTable('CANDIDATO');
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
