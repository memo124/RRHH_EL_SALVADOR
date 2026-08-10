<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;
use App\Services\PayrollCalculatorService;
use App\Services\PayrollPostingService;
use App\Services\PlanillaLifecycleService;
use App\Services\PlanillaReportService;
use App\Models\Empleado;
use App\Models\Planilla;

class PlanillaController extends Controller
{
    use PaginatesQueries;

    protected $calculator;
    protected $posting;
    protected $lifecycle;
    protected $reportService;
    protected $accounting;

    public function __construct(
        PayrollCalculatorService $calculator,
        PayrollPostingService $posting,
        PlanillaLifecycleService $lifecycle,
        PlanillaReportService $reportService,
        AccountingPostingService $accounting
    ) {
        $this->calculator = $calculator;
        $this->posting = $posting;
        $this->lifecycle = $lifecycle;
        $this->reportService = $reportService;
        $this->accounting = $accounting;
    }

    public function catalogs()
    {
        return response()->json([
            'empresas' => DB::table('EMPRESA')->where('EMPRESAACTIVA', true)->get(),
            'tiposPlanilla' => DB::table('TIPO_PLANILLA')->where('ESACTIVO', true)->orderBy('ID_TIPOPLANILLA')->get(),
            'periodos' => DB::table('PERIODO_LABORAL')->where('ESACTIVO', true)->orderBy('FECHAINICIO', 'desc')->get(),
            'frecuencias' => DB::table('FRECUENCIA_PAGO')->get(),
            'cuentas' => DB::table('CUENTA')->where('ESACTIVO', true)->get(),
            'tiposHorasExtras' => DB::table('HORAS_EXTRAS')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $query = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->select('PLANILLA.*', 'TIPO_PLANILLA.TIPOPLANILLA', 'PERIODO_LABORAL.CALPERIODO')
            ->orderBy('PLANILLA.ID_PLANILLA', 'desc');

        return $this->paginateQuery($query, $request, ['TITULO', 'TIPOPLANILLA', 'CALPERIODO']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'TITULO' => 'required|string|max:250',
            'ID_EMPRESA' => 'required|integer',
            'ID_TIPOPLANILLA' => 'required|integer',
            'ID_PERIODO' => 'required|integer',
            'ID_FRECUENCIAPAGO' => 'required|integer',
            'ID_CUENTA' => 'nullable|integer',
            'FORMAPAGO' => 'required|string|max:50',
            'FECHAPAGO' => 'required|date',
            'OBSERVACION' => 'nullable|string|max:500',
        ]);

        $maxId = DB::table('PLANILLA')->max('ID_PLANILLA') ?? 0;
        $id = $maxId + 1;

        DB::table('PLANILLA')->insert([
            'ID_PLANILLA' => $id,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'ID_TIPOPLANILLA' => $request->ID_TIPOPLANILLA,
            'ID_PERIODO' => $request->ID_PERIODO,
            'ID_FRECUENCIAPAGO' => $request->ID_FRECUENCIAPAGO,
            'ID_CUENTA' => $request->ID_CUENTA ?? 1,
            'TITULO' => $request->TITULO,
            'FECHAPAGO' => $request->FECHAPAGO,
            'FORMAPAGO' => $request->FORMAPAGO,
            'OBSERVACION' => $request->OBSERVACION,
            'ESACTIVA' => true,
            'CERRADA' => false,
            'ANULADA' => false,
            'CONTABILIZADA' => false,
        ]);

        return response()->json(['ID_PLANILLA' => $id, 'message' => 'Planilla creada correctamente.'], 201);
    }

    public function calculate($id)
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $planilla = Planilla::with('periodoLaboral')->find($id);
        if (!$planilla) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }
        if ($planilla->CERRADA || $planilla->ANULADA) {
            return response()->json(['error' => 'No se puede calcular una planilla cerrada o anulada.'], 422);
        }

        try {
            $procesados = DB::transaction(function () use ($id, $planilla) {
                $this->posting->reverseLoanPayments($id);
                DB::table('DETALLE_DESCUENTO_PLANILLA')
                    ->whereIn('ID_DETALLEPLANILLA', function ($q) use ($id) {
                        $q->select('ID_DETALLEPLANILLA')->from('DETALLE_PLANILLA')->where('ID_PLANILLA', $id);
                    })
                    ->delete();
                DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $id)->delete();

                $tipoPlanilla = DB::table('TIPO_PLANILLA')
                    ->where('ID_TIPOPLANILLA', $planilla->ID_TIPOPLANILLA)
                    ->first();

                $grupoNomina = $tipoPlanilla->GRUPO_NOMINA ?? null;

                $empleadosQuery = Empleado::with(['tipoContratacion', 'departamento.area', 'cargo', 'centroCosto'])
                    ->where('ID_EMPRESA', $planilla->ID_EMPRESA)
                    ->where('ESACTIVO', true);

                if ($grupoNomina) {
                    $empleadosQuery->whereHas('tipoContratacion', function ($q) use ($grupoNomina) {
                        $q->where('GRUPO_NOMINA', $grupoNomina);
                    });
                }

                $empleados = $empleadosQuery->orderBy('ID_EMPLEADO')->get();

                if ($empleados->isEmpty()) {
                    throw new \RuntimeException('No hay empleados activos para el grupo de nómina de esta planilla.');
                }

                $maxDetailId = DB::table('DETALLE_PLANILLA')->max('ID_DETALLEPLANILLA') ?? 0;
                $maxDescId = DB::table('DETALLE_DESCUENTO_PLANILLA')->max('ID_DETALLEDESCPLANILLA') ?? 0;
                $esAguinaldo = (int) $planilla->ID_TIPOPLANILLA === 3;
                $correlativo = 0;

                foreach ($empleados as $emp) {
                    $correlativo++;
                    if ($esAguinaldo) {
                        $line = $this->calculator->calculateAguinaldoLine($emp, $planilla);
                    } else {
                        $dias = $this->calculator->getDiasTrabajados($emp, $planilla);
                        $line = $this->calculator->calculatePayrollLine($emp, $planilla, $dias);
                    }

                    $descuentosDetalle = $line['DESCUENTOS_DETALLE'] ?? [];
                    unset($line['DESCUENTOS_DETALLE']);

                    $maxDetailId++;
                    DB::table('DETALLE_PLANILLA')->insert(array_merge([
                        'ID_DETALLEPLANILLA' => $maxDetailId,
                        'ID_PLANILLA' => $id,
                        'ID_EMPLEADO' => $emp->ID_EMPLEADO,
                        'CORRELATIVO' => $correlativo,
                    ], $line));

                    foreach ($descuentosDetalle as $desc) {
                        $maxDescId++;
                        DB::table('DETALLE_DESCUENTO_PLANILLA')->insert([
                            'ID_DETALLEDESCPLANILLA' => $maxDescId,
                            'ID_DETALLEPLANILLA' => $maxDetailId,
                            'ID_TIPODESCUENTO' => $desc['ID_TIPODESCUENTO'],
                            'CONCEPTO' => $desc['CONCEPTO'],
                            'CATEGORIA' => $desc['CATEGORIA'],
                            'MONTO' => $desc['MONTO'],
                        ]);
                    }

                    if ($line['PRESTAMOS'] > 0) {
                        $this->posting->postLoanPayments($emp->ID_EMPLEADO, $maxDetailId, $planilla);
                    }
                }

                DB::table('PLANILLA')->where('ID_PLANILLA', $id)->update(['RECALCULADA' => true]);

                return $empleados->count();
            });

            return response()->json([
                'message' => "Cálculo de planilla procesado con éxito ({$procesados} empleados).",
                'empleados_procesados' => $procesados,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Error al calcular la planilla. Si tiene muchos empleados, espere unos minutos e intente de nuevo.',
            ], 500);
        }
    }

    public function cerrar($id)
    {
        try {
            $this->lifecycle->cerrar($id);
            return response()->json(['message' => 'Planilla cerrada correctamente.']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function anular($id)
    {
        try {
            $this->lifecycle->anular($id);
            return response()->json(['message' => 'Planilla anulada correctamente.']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function contabilizar(Request $request, $id)
    {
        try {
            $this->lifecycle->contabilizar($id, $request->user()?->USUARIO, $request->user()?->ID_USUARIO);
            return response()->json(['message' => 'Planilla contabilizada correctamente.']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Asiento contable generado al contabilizar la planilla.
     */
    public function asiento($id)
    {
        $data = $this->accounting->getAsiento((int) $id);
        if (!$data) {
            return response()->json(['error' => 'Esta planilla aún no tiene un asiento contable generado.'], 404);
        }

        return response()->json($data);
    }

    /**
     * Exporta el asiento contable de la planilla en JSON o CSV.
     */
    public function asientoExport(Request $request, $id)
    {
        $data = $this->accounting->getAsiento((int) $id);
        if (!$data) {
            return response()->json(['error' => 'Esta planilla aún no tiene un asiento contable generado.'], 404);
        }

        if ($request->input('format') !== 'csv') {
            return response()->json($data);
        }

        $filename = "asiento_planilla_{$id}.csv";
        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Cuenta', 'Descripción', 'Debe', 'Haber']);
            foreach ($data['detalles'] as $detalle) {
                fputcsv($handle, [$detalle->CUENTA, $detalle->DESCRIPCION, $detalle->DEBE, $detalle->HABER]);
            }
            fputcsv($handle, ['', 'TOTALES', $data['asiento']->TOTAL_DEBE, $data['asiento']->TOTAL_HABER]);
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function show($id)
    {
        $summary = $this->buildPlanillaSummary((int) $id);
        if (!$summary) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }

        return response()->json([
            'planilla' => $summary['planilla'],
            'totales' => $summary['totales'],
            'conceptos_descuento' => $summary['conceptos_descuento'],
            'conceptos_ingreso' => $summary['conceptos_ingreso'],
            'conceptos_patronal' => $summary['conceptos_patronal'],
            'totales_conceptos' => $summary['totales_conceptos'],
            'has_detalles' => $summary['totales']['COUNT'] > 0,
        ]);
    }

    public function detalles(Request $request, $id)
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $id)->first();
        if (!$planilla) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }

        $query = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $id)
            ->orderBy('CORRELATIVO')
            ->orderBy('ID_DETALLEPLANILLA');

        if ($search = trim($request->input('search', ''))) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('NOM_EMPLEADO', 'like', $like)
                    ->orWhere('CARGO', 'like', $like)
                    ->orWhere('TIPO_CONTRATACION_NOM', 'like', $like);
            });
        }

        $perPage = min(200, max(10, (int) $request->input('per_page', 50)));
        $paginated = $query->paginate($perPage);

        $detalles = collect($paginated->items())->map(function ($d) {
            return $this->normalizeDetalleRow($d);
        });

        $descuentosPorDetalle = DB::table('DETALLE_DESCUENTO_PLANILLA')
            ->whereIn('ID_DETALLEPLANILLA', $detalles->pluck('ID_DETALLEPLANILLA'))
            ->orderBy('ID_DETALLEDESCPLANILLA')
            ->get()
            ->groupBy('ID_DETALLEPLANILLA');

        $detalles = $this->reportService->attachDescuentosDetalle($detalles, $descuentosPorDetalle);

        return response()->json([
            'data' => $detalles->values(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    /**
     * Empleados de una planilla calculada (AsyncSelect en horas extras).
     */
    public function empleadosSelect(Request $request, $id)
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $id)->first();
        if (!$planilla) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }

        $query = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $id)
            ->select('ID_EMPLEADO', 'NOM_EMPLEADO', 'CORRELATIVO')
            ->orderBy('CORRELATIVO');

        if ($search = trim($request->input('q', ''))) {
            $query->where('NOM_EMPLEADO', 'like', '%' . $search . '%');
        }

        $perPage = min(50, max(10, (int) $request->input('per_page', 30)));
        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(function ($row) {
            return [
                'value' => $row->ID_EMPLEADO,
                'label' => $row->NOM_EMPLEADO ?: ('Empleado #' . $row->ID_EMPLEADO),
            ];
        })->values();

        if ($request->filled('id')) {
            $selectedId = (int) $request->input('id');
            if (!$data->contains('value', $selectedId)) {
                $selected = DB::table('DETALLE_PLANILLA')
                    ->where('ID_PLANILLA', $id)
                    ->where('ID_EMPLEADO', $selectedId)
                    ->select('ID_EMPLEADO', 'NOM_EMPLEADO')
                    ->first();
                if ($selected) {
                    $data->prepend([
                        'value' => $selected->ID_EMPLEADO,
                        'label' => $selected->NOM_EMPLEADO ?: ('Empleado #' . $selected->ID_EMPLEADO),
                    ]);
                }
            }
        }

        return response()->json([
            'data' => $data->values(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    private function buildPlanillaSummary(int $id): ?array
    {
        $planilla = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->select('PLANILLA.*', 'TIPO_PLANILLA.TIPOPLANILLA', 'PERIODO_LABORAL.CALPERIODO')
            ->where('PLANILLA.ID_PLANILLA', $id)
            ->first();

        if (!$planilla) {
            return null;
        }

        $detalles = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $id)
            ->orderBy('CORRELATIVO')
            ->orderBy('ID_DETALLEPLANILLA')
            ->get()
            ->map(function ($d) {
                return $this->normalizeDetalleRow($d);
            });

        $descuentosPorDetalle = DB::table('DETALLE_DESCUENTO_PLANILLA')
            ->whereIn('ID_DETALLEPLANILLA', $detalles->pluck('ID_DETALLEPLANILLA'))
            ->orderBy('ID_DETALLEDESCPLANILLA')
            ->get()
            ->groupBy('ID_DETALLEPLANILLA');

        $detalles = $this->reportService->attachDescuentosDetalle($detalles, $descuentosPorDetalle);

        $conceptosDescuento = $this->reportService->collectConceptosDescuento($detalles);
        $conceptosIngreso = $this->reportService->collectConceptosIngreso($detalles);
        $conceptosPatronal = $this->reportService->collectConceptosPatronal($detalles);
        $totalesConceptos = $this->reportService->computeConceptTotals($detalles, $conceptosDescuento, $conceptosPatronal);

        $totales = [
            'TOTAL_DEVENGADO' => $detalles->sum('TOTAL_DEVENGADO'),
            'AFP_EMPLEADO' => $detalles->sum('AFP_EMPLEADO'),
            'ISSS_EMPLEADO' => $detalles->sum('ISSS_EMPLEADO'),
            'RENTA_EMPLEADO' => $detalles->sum('RENTA_EMPLEADO'),
            'PRESTAMOS' => $detalles->sum('PRESTAMOS'),
            'OTRO_DESCUENTOS' => $detalles->sum('OTRO_DESCUENTOS'),
            'ANTICIPO' => $detalles->sum('ANTICIPO'),
            'TOTAL_DEDUCCIONES' => $detalles->sum('TOTAL_DEDUCCIONES'),
            'LIQUIDO_A_RECIBIR' => $detalles->sum('LIQUIDO_A_RECIBIR'),
            'AFP_PATRONAL' => $detalles->sum('AFP_PATRONAL'),
            'ISSS_PATRONAL' => $detalles->sum('ISSS_PATRONAL'),
            'INSAFORP_PATRONAL' => $detalles->sum('INSAFORP_PATRONAL'),
            'COUNT' => $detalles->count(),
        ];

        return [
            'planilla' => $planilla,
            'totales' => $totales,
            'conceptos_descuento' => $conceptosDescuento,
            'conceptos_ingreso' => $conceptosIngreso,
            'conceptos_patronal' => $conceptosPatronal,
            'totales_conceptos' => $totalesConceptos,
        ];
    }

    private function normalizeDetalleRow(object $d): object
    {
        $d->SALARIO_BASE = (float) ($d->SALARIO_BASE ?? 0);
        $d->SALARIO_DIAS = (float) ($d->SALARIO_DIAS ?? 0);
        $d->DIASLABORADOS = (float) ($d->DIASLABORADOS ?? 0);
        $d->HORAEXTRAS = (float) ($d->HORAEXTRAS ?? 0);
        $d->PRODUCTIVIDAD = (float) ($d->PRODUCTIVIDAD ?? 0);
        $d->COMISION = (float) ($d->COMISION ?? 0);
        $d->OTROS_INGRESOS = (float) ($d->OTROS_INGRESOS ?? 0);
        $d->DEVENGADO_EXENTO = (float) ($d->DEVENGADO_EXENTO ?? 0);
        $d->TOTAL_DEVENGADO = (float) ($d->TOTAL_DEVENGADO ?? 0);
        $d->AFP_EMPLEADO = (float) ($d->AFP_EMPLEADO ?? 0);
        $d->ISSS_EMPLEADO = (float) ($d->ISSS_EMPLEADO ?? 0);
        $d->RENTA_EMPLEADO = (float) ($d->RENTA_EMPLEADO ?? 0);
        $d->PRESTAMOS = (float) ($d->PRESTAMOS ?? 0);
        $d->OTRO_DESCUENTOS = (float) ($d->OTRO_DESCUENTOS ?? 0);
        $d->TOTAL_DEDUCCIONES = (float) ($d->TOTAL_DEDUCCIONES ?? 0);
        $d->LIQUIDO_A_RECIBIR = (float) ($d->LIQUIDO_A_RECIBIR ?? 0);
        $d->AFP_PATRONAL = (float) ($d->AFP_PATRONAL ?? 0);
        $d->ISSS_PATRONAL = (float) ($d->ISSS_PATRONAL ?? 0);
        $d->INSAFORP_PATRONAL = (float) ($d->INSAFORP_PATRONAL ?? 0);

        return $d;
    }
}
