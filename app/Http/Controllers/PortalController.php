<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthenticatesPrintRequests;
use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\EncuestaService;
use App\Services\PlanillaReportService;
use App\Services\PortalService;
use App\Services\SolicitudPermisoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Autoservicio del empleado. Todas las acciones se restringen al ID_EMPLEADO
 * vinculado al usuario autenticado (nunca se expone información de otros empleados).
 */
class PortalController extends Controller
{
    use PaginatesQueries;
    use AuthenticatesPrintRequests;

    public function __construct(
        protected PortalService $portal,
        protected SolicitudPermisoService $permiso,
        protected EncuestaService $encuesta,
        protected PlanillaReportService $reports,
    ) {}

    private function idEmpleado(Request $request): int
    {
        $idEmpleado = $request->user()?->ID_EMPLEADO;
        abort_if(!$idEmpleado, 403, 'Usuario sin empleado vinculado.');

        return (int) $idEmpleado;
    }

    public function me(Request $request)
    {
        $perfil = $this->portal->perfil($this->idEmpleado($request));
        if (!$perfil) {
            return response()->json(['error' => 'Perfil de empleado no encontrado.'], 404);
        }

        return response()->json($perfil);
    }

    public function boletas(Request $request)
    {
        $perPage = $this->perPage($request, 12, 50);

        return response()->json($this->portal->boletas($this->idEmpleado($request), $perPage));
    }

    /**
     * Descarga/visualización del PDF de una boleta propia. Acepta token Sanctum
     * por query string porque se abre en una pestaña nueva del navegador.
     */
    public function boletaPdf(Request $request, $detalleId)
    {
        $this->authenticatePrint($request);

        $idEmpleado = $request->user()?->ID_EMPLEADO;
        if (!$idEmpleado) {
            abort(403, 'Usuario sin empleado vinculado.');
        }

        $detalle = $this->portal->boletaDeEmpleado((int) $idEmpleado, (int) $detalleId);
        if (!$detalle) {
            abort(404, 'Boleta no encontrada.');
        }

        try {
            $data = $this->reports->getBoletaData((int) $detalle->ID_PLANILLA, (int) $detalleId);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $pdf = Pdf::loadView('reportes.boleta-document', $data)->setPaper('letter', 'portrait');

        return $pdf->stream("boleta_{$detalleId}.pdf");
    }

    public function permisosCatalogs(Request $request)
    {
        $idEmpleado = $this->idEmpleado($request);

        return response()->json([
            'tipos' => $this->permiso->tiposPermiso(),
            'saldo' => $this->permiso->saldoDisponible($idEmpleado),
        ]);
    }

    public function permisos(Request $request)
    {
        $idEmpleado = $this->idEmpleado($request);

        $query = DB::table('SOLICITUD_PERMISO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->where('SOLICITUD_PERMISO.ID_EMPLEADO', $idEmpleado)
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->select(
                'SOLICITUD_PERMISO.*',
                'TIPO_PERMISO_LABORAL.NOMBRE as TIPO_NOMBRE',
                'TIPO_PERMISO_LABORAL.DESCUENTA_SALDO_VACACIONES as DESCUENTA_SALDO'
            )
            ->orderByDesc('SOLICITUD_PERMISO.FECHA_SOLICITUD');

        return $this->paginateQuery($query, $request, ['TIPO_PERMISO_LABORAL.NOMBRE']);
    }

    public function storePermiso(Request $request)
    {
        $idEmpleado = $this->idEmpleado($request);

        $request->validate([
            'ID_TIPO_PERMISO' => 'required|integer',
            'FECHA_INICIO' => 'required|date',
            'FECHA_FIN' => 'required|date|after_or_equal:FECHA_INICIO',
            'MOTIVO' => 'nullable|string|max:500',
        ]);

        try {
            $id = $this->permiso->crearSolicitud(
                array_merge($request->all(), ['ID_EMPLEADO' => $idEmpleado]),
                $request->user()->ID_USUARIO
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['ID_SOLICITUD' => $id, 'message' => 'Solicitud registrada correctamente.'], 201);
    }

    public function cancelarPermiso(Request $request, $id)
    {
        $idEmpleado = $this->idEmpleado($request);

        $esPropia = DB::table('SOLICITUD_PERMISO')
            ->where('ID_SOLICITUD', $id)
            ->where('ID_EMPLEADO', $idEmpleado)
            ->exists();

        if (!$esPropia || !$this->permiso->cancelar((int) $id)) {
            return response()->json(['error' => 'No se pudo cancelar la solicitud.'], 422);
        }

        return response()->json(['message' => 'Solicitud cancelada.']);
    }

    public function encuestas(Request $request)
    {
        $idEmpleado = $this->idEmpleado($request);
        $lista = $this->encuesta->encuestasParaEmpleado($idEmpleado);

        $result = [];
        foreach ($lista as $enc) {
            $enc = (object) $enc;
            $result[] = [
                'encuesta' => $enc,
                'preguntas' => $this->encuesta->getPreguntas($enc->ID_ENCUESTA),
                'respondida' => $this->encuesta->yaRespondio($enc->ID_ENCUESTA, $idEmpleado),
            ];
        }

        return response()->json($result);
    }

    public function responderEncuesta(Request $request, $id)
    {
        $idEmpleado = $this->idEmpleado($request);

        $enc = $this->encuesta->find((int) $id);
        if (!$enc || $enc->ESTADO !== 'publicada') {
            return response()->json(['error' => 'Encuesta no disponible.'], 404);
        }

        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.ID_PREGUNTA' => 'required|integer',
        ]);

        if ($this->encuesta->yaRespondio((int) $id, $idEmpleado)) {
            return response()->json(['error' => 'Ya respondió esta encuesta.'], 422);
        }

        $idRespuesta = $this->encuesta->guardarRespuesta((int) $id, $idEmpleado, $request->detalles);

        return response()->json(['ID_RESPUESTA' => $idRespuesta, 'message' => 'Respuesta registrada correctamente.'], 201);
    }

    public function evaluaciones(Request $request)
    {
        return response()->json($this->portal->misEvaluaciones($this->idEmpleado($request)));
    }

    public function evaluacionShow(Request $request, $id)
    {
        $data = $this->portal->evaluacionDetalle($this->idEmpleado($request), (int) $id);
        if (!$data) {
            return response()->json(['error' => 'Evaluación no encontrada.'], 404);
        }

        return response()->json($data);
    }
}
