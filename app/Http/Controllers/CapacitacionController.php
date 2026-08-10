<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\CapacitacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapacitacionController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected CapacitacionService $capacitacion) {}

    public function index(Request $request)
    {
        $query = DB::table('CAPACITACION')
            ->leftJoin('EMPRESA', 'CAPACITACION.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->where('CAPACITACION.ESACTIVO', true)
            ->select('CAPACITACION.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderByDesc('CAPACITACION.FECHA_CREACION');

        if ($request->filled('estado')) {
            $query->where('CAPACITACION.ESTADO', $request->estado);
        }

        return $this->paginateQuery($query, $request, ['CAPACITACION.TITULO', 'CAPACITACION.DESCRIPCION']);
    }

    public function show($id)
    {
        $cap = DB::table('CAPACITACION')
            ->leftJoin('EMPRESA', 'CAPACITACION.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->where('CAPACITACION.ID_CAPACITACION', $id)
            ->where('CAPACITACION.ESACTIVO', true)
            ->select('CAPACITACION.*', 'EMPRESA.NOMBREEMPRESA')
            ->first();

        if (!$cap) {
            return response()->json(['error' => 'Capacitación no encontrada.'], 404);
        }

        return response()->json([
            'capacitacion' => $cap,
            'inscripciones' => $this->capacitacion->getInscripciones((int) $id),
            'cupos_disponibles' => $this->capacitacion->cuposDisponibles((int) $id),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'TITULO' => 'required|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'MODALIDAD' => 'nullable|string|max:30',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
            'CUPO_MAX' => 'nullable|integer|min:1',
            'ID_EMPRESA' => 'nullable|integer',
            'LUGAR' => 'nullable|string|max:250',
        ]);

        $maxId = DB::table('CAPACITACION')->max('ID_CAPACITACION') ?? 0;
        $id = $maxId + 1;

        DB::table('CAPACITACION')->insert([
            'ID_CAPACITACION' => $id,
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'MODALIDAD' => $request->MODALIDAD ?? 'presencial',
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'CUPO_MAX' => $request->CUPO_MAX,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'LUGAR' => $request->LUGAR,
            'ESTADO' => 'borrador',
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'FECHA_CREACION' => now(),
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_CAPACITACION' => $id, 'message' => 'Capacitación creada correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->capacitacion->find((int) $id)) {
            return response()->json(['error' => 'Capacitación no encontrada.'], 404);
        }

        $request->validate([
            'TITULO' => 'sometimes|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'MODALIDAD' => 'nullable|string|max:30',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
            'CUPO_MAX' => 'nullable|integer|min:1',
            'ID_EMPRESA' => 'nullable|integer',
            'LUGAR' => 'nullable|string|max:250',
        ]);

        $update = array_filter([
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'MODALIDAD' => $request->MODALIDAD,
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'CUPO_MAX' => $request->CUPO_MAX,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'LUGAR' => $request->LUGAR,
        ], fn ($v) => $v !== null);

        if ($update !== []) {
            DB::table('CAPACITACION')->where('ID_CAPACITACION', $id)->update($update);
        }

        return response()->json(['message' => 'Capacitación actualizada correctamente.']);
    }

    public function destroy($id)
    {
        DB::table('CAPACITACION')->where('ID_CAPACITACION', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Capacitación inactivada correctamente.']);
    }

    public function publicar($id)
    {
        if (!$this->capacitacion->publicar((int) $id)) {
            return response()->json(['error' => 'Capacitación no encontrada.'], 404);
        }
        return response()->json(['message' => 'Capacitación publicada. Evento sincronizado en calendario.']);
    }

    public function cerrar($id)
    {
        $this->capacitacion->cerrar((int) $id);
        return response()->json(['message' => 'Capacitación cerrada.']);
    }

    public function inscribir(Request $request, $id)
    {
        $request->validate([
            'ID_EMPLEADOS' => 'required|array|min:1',
            'ID_EMPLEADOS.*' => 'integer',
        ]);

        try {
            $ids = $this->capacitacion->inscribir((int) $id, $request->ID_EMPLEADOS);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['inscripciones' => $ids, 'message' => 'Inscripciones registradas.']);
    }

    public function asistencia(Request $request, $idInscripcion)
    {
        $request->validate([
            'FECHA' => 'required|date',
            'ASISTIO' => 'required|boolean',
            'OBSERVACIONES' => 'nullable|string|max:250',
        ]);

        $this->capacitacion->registrarAsistencia(
            (int) $idInscripcion,
            $request->FECHA,
            $request->boolean('ASISTIO'),
            $request->OBSERVACIONES
        );

        return response()->json(['message' => 'Asistencia registrada.']);
    }

    public function asistencias($idInscripcion)
    {
        return response()->json($this->capacitacion->getAsistencias((int) $idInscripcion));
    }

    public function completar(Request $request, $idInscripcion)
    {
        $request->validate([
            'CALIFICACION' => 'nullable|numeric|min:0|max:100',
            'ID_ADJUNTO_CERTIFICADO' => 'nullable|integer',
        ]);

        $this->capacitacion->completarInscripcion(
            (int) $idInscripcion,
            $request->CALIFICACION,
            $request->ID_ADJUNTO_CERTIFICADO
        );

        return response()->json(['message' => 'Inscripción marcada como completada.']);
    }
}
