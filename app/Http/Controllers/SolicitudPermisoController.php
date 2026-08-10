<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\SolicitudPermisoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudPermisoController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected SolicitudPermisoService $permiso) {}

    public function catalogs()
    {
        return response()->json([
            'tipos' => $this->permiso->tiposPermiso(),
        ]);
    }

    public function index(Request $request)
    {
        $query = DB::table('SOLICITUD_PERMISO')
            ->join('EMPLEADO', 'SOLICITUD_PERMISO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->leftJoin('PLANILLA', 'SOLICITUD_PERMISO.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->select(
                'SOLICITUD_PERMISO.*',
                'TIPO_PERMISO_LABORAL.NOMBRE as TIPO_NOMBRE',
                'TIPO_PERMISO_LABORAL.CODIGO as TIPO_CODIGO',
                'TIPO_PERMISO_LABORAL.DESCUENTA_SALDO_VACACIONES as DESCUENTA_SALDO',
                'PLANILLA.TITULO as PLANILLA_TITULO',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPLEADO.CODIGOEMPLEADO'
            )
            ->orderByDesc('SOLICITUD_PERMISO.FECHA_SOLICITUD');

        if ($request->filled('estado')) {
            $query->where('SOLICITUD_PERMISO.ESTADO', $request->estado);
        }
        if ($request->filled('ID_EMPLEADO')) {
            $query->where('SOLICITUD_PERMISO.ID_EMPLEADO', (int) $request->ID_EMPLEADO);
        }

        return $this->paginateQuery($query, $request, ['EMPLEADO.NOMBRES', 'TIPO_PERMISO_LABORAL.NOMBRE']);
    }

    public function pendientes(Request $request)
    {
        $request->merge(['estado' => 'pendiente']);
        return $this->index($request);
    }

    public function saldo(Request $request, $idEmpleado)
    {
        $anio = $request->input('anio');
        return response()->json($this->permiso->saldoDisponible((int) $idEmpleado, $anio ? (int) $anio : null));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'ID_TIPO_PERMISO' => 'required|integer',
            'FECHA_INICIO' => 'required|date',
            'FECHA_FIN' => 'required|date|after_or_equal:FECHA_INICIO',
            'MOTIVO' => 'nullable|string|max:500',
        ]);

        try {
            $id = $this->permiso->crearSolicitud($request->all(), $request->user()->ID_USUARIO);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['ID_SOLICITUD' => $id, 'message' => 'Solicitud registrada correctamente.'], 201);
    }

    public function aprobar(Request $request, $id)
    {
        $result = $this->permiso->aprobar((int) $id, $request->user()->ID_USUARIO);

        if (!$result['ok']) {
            return response()->json(['error' => 'No se pudo aprobar la solicitud.'], 422);
        }

        $payload = ['message' => 'Solicitud aprobada. Evento creado en calendario.'];

        if ($result['planilla']) {
            $payload['message'] .= ' Planilla de vacaciones #' . $result['planilla']['ID_PLANILLA'] . ' generada.';
            $payload['planilla'] = $result['planilla'];
        } elseif ($result['planilla_error']) {
            $payload['planilla_warning'] = 'Aprobada, pero no se pudo generar planilla: ' . $result['planilla_error'];
        }

        return response()->json($payload);
    }

    public function integrarPlanilla(Request $request, $id)
    {
        try {
            $planilla = app(\App\Services\VacacionesPlanillaService::class)->integrarSolicitud((int) $id);
            return response()->json([
                'message' => 'Planilla de vacaciones integrada correctamente.',
                'planilla' => $planilla,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rechazar(Request $request, $id)
    {
        $request->validate(['MOTIVO_RECHAZO' => 'required|string|max:500']);

        if (!$this->permiso->rechazar((int) $id, $request->user()->ID_USUARIO, $request->MOTIVO_RECHAZO)) {
            return response()->json(['error' => 'No se pudo rechazar la solicitud.'], 422);
        }
        return response()->json(['message' => 'Solicitud rechazada.']);
    }

    public function cancelar($id)
    {
        if (!$this->permiso->cancelar((int) $id)) {
            return response()->json(['error' => 'No se pudo cancelar la solicitud.'], 422);
        }
        return response()->json(['message' => 'Solicitud cancelada.']);
    }

    public function inicializarSaldos(Request $request)
    {
        $request->validate([
            'ANIO' => 'nullable|integer',
            'ID_EMPLEADOS' => 'nullable|array',
        ]);

        $anio = $request->ANIO ?? (int) date('Y');
        $query = DB::table('EMPLEADO')->where('ESACTIVO', true);
        if ($request->filled('ID_EMPLEADOS')) {
            $query->whereIn('ID_EMPLEADO', $request->ID_EMPLEADOS);
        }

        $count = 0;
        foreach ($query->pluck('ID_EMPLEADO') as $idEmp) {
            $this->permiso->getOrCreateSaldo($idEmp, $anio);
            $count++;
        }

        return response()->json(['message' => "Saldos inicializados para {$count} empleados.", 'ANIO' => $anio]);
    }
}
