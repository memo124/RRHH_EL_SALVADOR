<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\EncuestaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncuestaController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected EncuestaService $encuesta) {}

    public function index(Request $request)
    {
        $query = DB::table('ENCUESTA')
            ->where('ESACTIVO', true)
            ->orderByDesc('FECHA_CREACION');

        return $this->paginateQuery($query, $request, ['TITULO', 'DESCRIPCION']);
    }

    public function show($id)
    {
        $enc = $this->encuesta->find((int) $id);
        if (!$enc) {
            return response()->json(['error' => 'Encuesta no encontrada.'], 404);
        }

        $preguntas = $this->encuesta->getPreguntas((int) $id);
        $asignaciones = DB::table('ENCUESTA_ASIGNACION')
            ->where('ID_ENCUESTA', $id)
            ->where('ESACTIVO', true)
            ->get();

        return response()->json([
            'encuesta' => $enc,
            'preguntas' => $preguntas,
            'asignaciones' => $asignaciones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'TITULO' => 'required|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
            'ANONIMA' => 'boolean',
            'ENVIAR_RECORDATORIOS' => 'boolean',
            'preguntas' => 'nullable|array',
            'asignaciones' => 'nullable|array',
        ]);

        $maxId = DB::table('ENCUESTA')->max('ID_ENCUESTA') ?? 0;
        $id = $maxId + 1;

        DB::table('ENCUESTA')->insert([
            'ID_ENCUESTA' => $id,
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ESTADO' => 'borrador',
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'ANONIMA' => $request->boolean('ANONIMA'),
            'ENVIAR_RECORDATORIOS' => $request->boolean('ENVIAR_RECORDATORIOS'),
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'FECHA_CREACION' => now(),
            'ESACTIVO' => true,
        ]);

        if ($request->has('preguntas')) {
            $this->encuesta->savePreguntas($id, $request->preguntas);
        }
        if ($request->has('asignaciones')) {
            $this->encuesta->saveAsignaciones($id, $request->asignaciones);
        }

        return response()->json(['ID_ENCUESTA' => $id, 'message' => 'Encuesta creada correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        $enc = $this->encuesta->find((int) $id);
        if (!$enc) {
            return response()->json(['error' => 'Encuesta no encontrada.'], 404);
        }

        $request->validate([
            'TITULO' => 'sometimes|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
            'ANONIMA' => 'boolean',
            'ENVIAR_RECORDATORIOS' => 'boolean',
            'preguntas' => 'nullable|array',
            'asignaciones' => 'nullable|array',
        ]);

        $update = array_filter([
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'ANONIMA' => $request->has('ANONIMA') ? $request->boolean('ANONIMA') : null,
            'ENVIAR_RECORDATORIOS' => $request->has('ENVIAR_RECORDATORIOS') ? $request->boolean('ENVIAR_RECORDATORIOS') : null,
        ], fn ($v) => $v !== null);

        if ($update !== []) {
            DB::table('ENCUESTA')->where('ID_ENCUESTA', $id)->update($update);
        }

        if ($request->has('preguntas')) {
            $this->encuesta->savePreguntas((int) $id, $request->preguntas);
        }
        if ($request->has('asignaciones')) {
            $this->encuesta->saveAsignaciones((int) $id, $request->asignaciones);
        }

        return response()->json(['message' => 'Encuesta actualizada correctamente.']);
    }

    public function destroy($id)
    {
        DB::table('ENCUESTA')->where('ID_ENCUESTA', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Encuesta inactivada correctamente.']);
    }

    public function publicar($id)
    {
        if (!$this->encuesta->publicar((int) $id)) {
            return response()->json(['error' => 'Encuesta no encontrada.'], 404);
        }
        return response()->json(['message' => 'Encuesta publicada correctamente.']);
    }

    public function cerrar($id)
    {
        $this->encuesta->cerrar((int) $id);
        return response()->json(['message' => 'Encuesta cerrada correctamente.']);
    }

    public function resultados($id)
    {
        if (!$this->encuesta->find((int) $id)) {
            return response()->json(['error' => 'Encuesta no encontrada.'], 404);
        }
        return response()->json($this->encuesta->resultados((int) $id));
    }

    public function misEncuestas(Request $request)
    {
        $idEmpleado = $request->user()->ID_EMPLEADO;
        $lista = $this->encuesta->encuestasParaEmpleado($idEmpleado);

        $result = [];
        foreach ($lista as $enc) {
            $enc = (object) $enc;
            $result[] = [
                'encuesta' => $enc,
                'preguntas' => $this->encuesta->getPreguntas($enc->ID_ENCUESTA),
                'respondida' => $idEmpleado ? $this->encuesta->yaRespondio($enc->ID_ENCUESTA, $idEmpleado) : false,
            ];
        }

        return response()->json($result);
    }

    public function responder(Request $request, $id)
    {
        $enc = $this->encuesta->find((int) $id);
        if (!$enc || $enc->ESTADO !== 'publicada') {
            return response()->json(['error' => 'Encuesta no disponible.'], 404);
        }

        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.ID_PREGUNTA' => 'required|integer',
        ]);

        $idEmpleado = $request->user()->ID_EMPLEADO;

        if ($idEmpleado && $this->encuesta->yaRespondio((int) $id, $idEmpleado)) {
            return response()->json(['error' => 'Ya respondió esta encuesta.'], 422);
        }

        $idRespuesta = $this->encuesta->guardarRespuesta((int) $id, $idEmpleado, $request->detalles);

        return response()->json(['ID_RESPUESTA' => $idRespuesta, 'message' => 'Respuesta registrada correctamente.'], 201);
    }
}
