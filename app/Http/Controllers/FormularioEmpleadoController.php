<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\FormularioAprobacionService;
use App\Services\FormularioEmpleadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormularioEmpleadoController extends Controller
{
    use PaginatesQueries;

    public function __construct(
        protected FormularioEmpleadoService $formulario,
        protected FormularioAprobacionService $aprobacion
    ) {}

    // ── Plantillas ────────────────────────────────────────────────────────────

    public function indexPlantillas(Request $request)
    {
        $query = DB::table('FORMULARIO_PLANTILLA')
            ->where('ESACTIVO', true)
            ->orderByDesc('FECHA_CREACION');

        return $this->paginateQuery($query, $request, ['NOMBRE', 'DESCRIPCION']);
    }

    public function selectPlantillas(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $query = DB::table('FORMULARIO_PLANTILLA')->where('ESACTIVO', true)->orderBy('NOMBRE');
        if ($q !== '') {
            $query->where('NOMBRE', 'ILIKE', "%{$q}%");
        }
        $perPage = min(50, max(10, (int) $request->input('per_page', 30)));
        $paginated = $query->paginate($perPage, ['ID_PLANTILLA', 'NOMBRE']);

        $data = collect($paginated->items())->map(fn ($row) => [
            'value' => $row->ID_PLANTILLA,
            'label' => $row->NOMBRE,
        ])->values();

        return response()->json([
            'data' => $data,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function showPlantilla($id)
    {
        $p = $this->formulario->findPlantilla((int) $id);
        if (!$p) {
            return response()->json(['error' => 'Plantilla no encontrada.'], 404);
        }

        return response()->json([
            'plantilla' => $p,
            'campos' => $this->formulario->getCampos((int) $id),
        ]);
    }

    public function storePlantilla(Request $request)
    {
        $request->validate([
            'NOMBRE' => 'required|string|max:200',
            'DESCRIPCION' => 'nullable|string',
            'campos' => 'nullable|array',
        ]);

        $maxId = DB::table('FORMULARIO_PLANTILLA')->max('ID_PLANTILLA') ?? 0;
        $id = $maxId + 1;

        DB::table('FORMULARIO_PLANTILLA')->insert([
            'ID_PLANTILLA' => $id,
            'NOMBRE' => $request->NOMBRE,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'FECHA_CREACION' => now(),
            'ESACTIVO' => true,
        ]);

        if ($request->has('campos')) {
            $this->formulario->saveCampos($id, $request->campos);
        }

        return response()->json(['ID_PLANTILLA' => $id, 'message' => 'Plantilla creada correctamente.'], 201);
    }

    public function updatePlantilla(Request $request, $id)
    {
        if (!$this->formulario->findPlantilla((int) $id)) {
            return response()->json(['error' => 'Plantilla no encontrada.'], 404);
        }

        $request->validate([
            'NOMBRE' => 'sometimes|string|max:200',
            'DESCRIPCION' => 'nullable|string',
            'campos' => 'nullable|array',
        ]);

        $update = array_filter([
            'NOMBRE' => $request->NOMBRE,
            'DESCRIPCION' => $request->DESCRIPCION,
        ], fn ($v) => $v !== null);

        if ($update !== []) {
            DB::table('FORMULARIO_PLANTILLA')->where('ID_PLANTILLA', $id)->update($update);
        }

        if ($request->has('campos')) {
            $this->formulario->saveCampos((int) $id, $request->campos);
        }

        return response()->json(['message' => 'Plantilla actualizada correctamente.']);
    }

    public function destroyPlantilla($id)
    {
        DB::table('FORMULARIO_PLANTILLA')->where('ID_PLANTILLA', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Plantilla inactivada correctamente.']);
    }

    public function seedPlantillaDefault(Request $request)
    {
        $def = $this->formulario->plantillaDefaultActualizacion();
        $request->merge([
            'NOMBRE' => $def['NOMBRE'],
            'DESCRIPCION' => $def['DESCRIPCION'],
            'campos' => $def['CAMPOS'],
        ]);

        return $this->storePlantilla($request);
    }

    // ── Campañas ──────────────────────────────────────────────────────────────

    public function indexCampanas(Request $request)
    {
        $query = DB::table('FORMULARIO_CAMPANA')
            ->join('FORMULARIO_PLANTILLA', 'FORMULARIO_CAMPANA.ID_PLANTILLA', '=', 'FORMULARIO_PLANTILLA.ID_PLANTILLA')
            ->where('FORMULARIO_CAMPANA.ESACTIVO', true)
            ->select('FORMULARIO_CAMPANA.*', 'FORMULARIO_PLANTILLA.NOMBRE as PLANTILLA_NOMBRE')
            ->orderByDesc('FORMULARIO_CAMPANA.FECHA_CREACION');

        return $this->paginateQuery($query, $request, ['FORMULARIO_CAMPANA.NOMBRE', 'FORMULARIO_PLANTILLA.NOMBRE']);
    }

    public function storeCampana(Request $request)
    {
        $request->validate([
            'ID_PLANTILLA' => 'required|integer',
            'NOMBRE' => 'required|string|max:200',
            'DESCRIPCION' => 'nullable|string',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
        ]);

        $maxId = DB::table('FORMULARIO_CAMPANA')->max('ID_CAMPANA') ?? 0;
        $id = $maxId + 1;

        DB::table('FORMULARIO_CAMPANA')->insert([
            'ID_CAMPANA' => $id,
            'ID_PLANTILLA' => $request->ID_PLANTILLA,
            'NOMBRE' => $request->NOMBRE,
            'DESCRIPCION' => $request->DESCRIPCION,
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'ESTADO' => 'borrador',
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'FECHA_CREACION' => now(),
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_CAMPANA' => $id, 'message' => 'Campaña creada correctamente.'], 201);
    }

    public function activarCampana($id)
    {
        if (!$this->formulario->activarCampana((int) $id)) {
            return response()->json(['error' => 'Campaña no encontrada.'], 404);
        }
        return response()->json(['message' => 'Campaña activada correctamente.']);
    }

    public function generarInvitaciones(Request $request, $id)
    {
        $request->validate([
            'ID_EMPLEADOS' => 'required|array|min:1',
            'ID_EMPLEADOS.*' => 'integer',
            'FECHA_EXPIRACION' => 'nullable|date',
        ]);

        $links = $this->formulario->generarInvitaciones(
            (int) $id,
            $request->ID_EMPLEADOS,
            $request->FECHA_EXPIRACION
        );

        return response()->json(['invitaciones' => $links, 'message' => 'Invitaciones generadas correctamente.']);
    }

    public function invitacionesCampana($id)
    {
        $rows = DB::table('FORMULARIO_INVITACION')
            ->join('EMPLEADO', 'FORMULARIO_INVITACION.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('FORMULARIO_INVITACION.ID_CAMPANA', $id)
            ->where('FORMULARIO_INVITACION.ESACTIVO', true)
            ->select(
                'FORMULARIO_INVITACION.*',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPLEADO.CODIGOEMPLEADO'
            )
            ->get()
            ->map(function ($r) {
                $r->URL = url("/actualizar-datos/{$r->TOKEN}");
                return $r;
            });

        return response()->json($rows);
    }

    // ── Bandeja aprobación ────────────────────────────────────────────────────

    public function respuestasPendientes(Request $request)
    {
        $query = DB::table('FORMULARIO_RESPUESTA')
            ->join('FORMULARIO_CAMPANA', 'FORMULARIO_RESPUESTA.ID_CAMPANA', '=', 'FORMULARIO_CAMPANA.ID_CAMPANA')
            ->join('EMPLEADO', 'FORMULARIO_RESPUESTA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('FORMULARIO_RESPUESTA.ESACTIVO', true)
            ->where('FORMULARIO_RESPUESTA.ESTADO', 'pendiente_aprobacion')
            ->select(
                'FORMULARIO_RESPUESTA.*',
                'FORMULARIO_CAMPANA.NOMBRE as CAMPANA_NOMBRE',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPLEADO.CODIGOEMPLEADO'
            )
            ->orderByDesc('FORMULARIO_RESPUESTA.FECHA_ENVIO');

        return $this->paginateQuery($query, $request, ['EMPLEADO.NOMBRES', 'FORMULARIO_CAMPANA.NOMBRE']);
    }

    public function showRespuesta($id)
    {
        $resp = DB::table('FORMULARIO_RESPUESTA')
            ->join('EMPLEADO', 'FORMULARIO_RESPUESTA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('FORMULARIO_CAMPANA', 'FORMULARIO_RESPUESTA.ID_CAMPANA', '=', 'FORMULARIO_CAMPANA.ID_CAMPANA')
            ->where('FORMULARIO_RESPUESTA.ID_RESPUESTA', $id)
            ->select(
                'FORMULARIO_RESPUESTA.*',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'FORMULARIO_CAMPANA.NOMBRE as CAMPANA_NOMBRE'
            )
            ->first();

        if (!$resp) {
            return response()->json(['error' => 'Respuesta no encontrada.'], 404);
        }

        $campos = DB::table('FORMULARIO_RESPUESTA_CAMPO')
            ->join('FORMULARIO_CAMPO', 'FORMULARIO_RESPUESTA_CAMPO.ID_CAMPO', '=', 'FORMULARIO_CAMPO.ID_CAMPO')
            ->leftJoin('ADJUNTO', 'FORMULARIO_RESPUESTA_CAMPO.ID_ADJUNTO', '=', 'ADJUNTO.ID_ADJUNTO')
            ->where('FORMULARIO_RESPUESTA_CAMPO.ID_RESPUESTA', $id)
            ->select(
                'FORMULARIO_RESPUESTA_CAMPO.*',
                'FORMULARIO_CAMPO.ETIQUETA',
                'FORMULARIO_CAMPO.TIPO_CAMPO',
                'FORMULARIO_CAMPO.MAPEO_TABLA',
                'FORMULARIO_CAMPO.MAPEO_COLUMNA',
                'ADJUNTO.NOMBRE_ARCHIVO'
            )
            ->orderBy('FORMULARIO_CAMPO.ORDEN')
            ->get();

        return response()->json(['respuesta' => $resp, 'campos' => $campos]);
    }

    public function aprobarRespuesta(Request $request, $id)
    {
        if (!$this->aprobacion->aprobar((int) $id, $request->user()->ID_USUARIO)) {
            return response()->json(['error' => 'No se pudo aprobar la respuesta.'], 422);
        }
        return response()->json(['message' => 'Cambios aplicados al expediente del empleado.']);
    }

    public function rechazarRespuesta(Request $request, $id)
    {
        $request->validate(['MOTIVO_RECHAZO' => 'required|string|max:500']);

        if (!$this->aprobacion->rechazar((int) $id, $request->user()->ID_USUARIO, $request->MOTIVO_RECHAZO)) {
            return response()->json(['error' => 'No se pudo rechazar la respuesta.'], 422);
        }
        return response()->json(['message' => 'Respuesta rechazada.']);
    }
}
