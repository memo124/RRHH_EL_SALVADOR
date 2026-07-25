<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PayrollCalculatorService;
use App\Services\PayrollPostingService;
use App\Services\PlanillaLifecycleService;
use App\Models\Empleado;
use App\Models\Planilla;

class PlanillaController extends Controller
{
    protected $calculator;
    protected $posting;
    protected $lifecycle;

    public function __construct(
        PayrollCalculatorService $calculator,
        PayrollPostingService $posting,
        PlanillaLifecycleService $lifecycle
    ) {
        $this->calculator = $calculator;
        $this->posting = $posting;
        $this->lifecycle = $lifecycle;
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
            'empleados' => DB::table('EMPLEADO')->where('ESACTIVO', true)
                ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'ID_EMPRESA')
                ->get(),
        ]);
    }

    public function index()
    {
        $planillas = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->select('PLANILLA.*', 'TIPO_PLANILLA.TIPOPLANILLA', 'PERIODO_LABORAL.CALPERIODO')
            ->orderBy('PLANILLA.ID_PLANILLA', 'desc')
            ->get();

        return response()->json($planillas);
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
        $planilla = Planilla::with('periodoLaboral')->find($id);
        if (!$planilla) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }
        if ($planilla->CERRADA || $planilla->ANULADA) {
            return response()->json(['error' => 'No se puede calcular una planilla cerrada o anulada.'], 422);
        }

        $this->posting->reverseLoanPayments($id);
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

        $empleados = $empleadosQuery->get();

        $maxDetailId = DB::table('DETALLE_PLANILLA')->max('ID_DETALLEPLANILLA') ?? 0;
        $esAguinaldo = (int) $planilla->ID_TIPOPLANILLA === 3;

        foreach ($empleados as $emp) {
            if ($esAguinaldo) {
                $line = $this->calculator->calculateAguinaldoLine($emp, $planilla);
            } else {
                $dias = $this->calculator->getDiasTrabajados($emp, $planilla);
                $line = $this->calculator->calculatePayrollLine($emp, $planilla, $dias);
            }

            $maxDetailId++;
            DB::table('DETALLE_PLANILLA')->insert(array_merge([
                'ID_DETALLEPLANILLA' => $maxDetailId,
                'ID_PLANILLA' => $id,
                'ID_EMPLEADO' => $emp->ID_EMPLEADO,
            ], $line));

            if ($line['PRESTAMOS'] > 0) {
                $this->posting->postLoanPayments($emp->ID_EMPLEADO, $maxDetailId, $planilla);
            }
        }

        DB::table('PLANILLA')->where('ID_PLANILLA', $id)->update(['RECALCULADA' => true]);

        return response()->json(['message' => 'Cálculo de planilla procesado con éxito.']);
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
            $this->lifecycle->contabilizar($id, $request->user()?->USUARIO);
            return response()->json(['message' => 'Planilla contabilizada correctamente.']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $planilla = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->select('PLANILLA.*', 'TIPO_PLANILLA.TIPOPLANILLA', 'PERIODO_LABORAL.CALPERIODO')
            ->where('PLANILLA.ID_PLANILLA', $id)
            ->first();

        if (!$planilla) {
            return response()->json(['error' => 'Planilla no encontrada.'], 404);
        }

        $detalles = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $id)
            ->orderBy('CORRELATIVO')
            ->orderBy('ID_DETALLEPLANILLA')
            ->get()
            ->map(function ($d) {
                $d->SALARIO_BASE = (float) ($d->SALARIO_BASE ?? 0);
                $d->SALARIO_DIAS = (float) ($d->SALARIO_DIAS ?? 0);
                $d->DIASLABORADOS = (float) ($d->DIASLABORADOS ?? 0);
                $d->HORAEXTRAS = (float) ($d->HORAEXTRAS ?? 0);
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
            });

        $totales = [
            'TOTAL_DEVENGADO' => $detalles->sum('TOTAL_DEVENGADO'),
            'AFP_EMPLEADO' => $detalles->sum('AFP_EMPLEADO'),
            'ISSS_EMPLEADO' => $detalles->sum('ISSS_EMPLEADO'),
            'RENTA_EMPLEADO' => $detalles->sum('RENTA_EMPLEADO'),
            'PRESTAMOS' => $detalles->sum('PRESTAMOS'),
            'TOTAL_DEDUCCIONES' => $detalles->sum('TOTAL_DEDUCCIONES'),
            'LIQUIDO_A_RECIBIR' => $detalles->sum('LIQUIDO_A_RECIBIR'),
            'AFP_PATRONAL' => $detalles->sum('AFP_PATRONAL'),
            'ISSS_PATRONAL' => $detalles->sum('ISSS_PATRONAL'),
            'INSAFORP_PATRONAL' => $detalles->sum('INSAFORP_PATRONAL'),
            'COUNT' => $detalles->count(),
        ];

        return response()->json([
            'planilla' => $planilla,
            'detalles' => $detalles,
            'totales' => $totales,
        ]);
    }
}
