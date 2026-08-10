<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\EvaluacionDesempenoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluacionDesempenoController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected EvaluacionDesempenoService $evaluacion) {}

    public function indexPeriodos(Request $request)
    {
        $query = DB::table('EVALUACION_PERIODO')
            ->where('ESACTIVO', true)
            ->orderByDesc('ANIO');

        return $this->paginateQuery($query, $request, ['NOMBRE']);
    }

    public function showPeriodo($id)
    {
        $periodo = $this->evaluacion->findPeriodo((int) $id);
        if (!$periodo) {
            return response()->json(['error' => 'Periodo no encontrado.'], 404);
        }

        return response()->json([
            'periodo' => $periodo,
            'resultados' => $this->evaluacion->resultadosPeriodo((int) $id),
        ]);
    }

    public function storePeriodo(Request $request)
    {
        $request->validate([
            'NOMBRE' => 'required|string|max:200',
            'ANIO' => 'required|integer',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date',
        ]);

        $maxId = DB::table('EVALUACION_PERIODO')->max('ID_PERIODO') ?? 0;
        $id = $maxId + 1;

        DB::table('EVALUACION_PERIODO')->insert([
            'ID_PERIODO' => $id,
            'NOMBRE' => $request->NOMBRE,
            'ANIO' => $request->ANIO,
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->FECHA_FIN,
            'ESTADO' => 'borrador',
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_PERIODO' => $id, 'message' => 'Periodo creado correctamente.'], 201);
    }

    public function activarPeriodo($id)
    {
        $this->evaluacion->activarPeriodo((int) $id);
        return response()->json(['message' => 'Periodo activado.']);
    }

    public function cerrarPeriodo($id)
    {
        $this->evaluacion->cerrarPeriodo((int) $id);
        return response()->json(['message' => 'Periodo cerrado.']);
    }

    public function asignarEvaluaciones(Request $request, $idPeriodo)
    {
        $request->validate([
            'asignaciones' => 'required|array|min:1',
            'asignaciones.*.ID_EMPLEADO' => 'required|integer',
            'asignaciones.*.ID_EVALUADOR' => 'required|integer',
        ]);

        try {
            $ids = $this->evaluacion->crearEvaluaciones((int) $idPeriodo, $request->asignaciones);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['evaluaciones' => $ids, 'message' => 'Evaluaciones asignadas.']);
    }

    public function showEvaluacion($id)
    {
        $eval = DB::table('EVALUACION_DESEMPENO')
            ->join('EMPLEADO as EVALUADO', 'EVALUACION_DESEMPENO.ID_EMPLEADO', '=', 'EVALUADO.ID_EMPLEADO')
            ->join('EMPLEADO as EVALUADOR', 'EVALUACION_DESEMPENO.ID_EVALUADOR', '=', 'EVALUADOR.ID_EMPLEADO')
            ->join('EVALUACION_PERIODO', 'EVALUACION_DESEMPENO.ID_PERIODO', '=', 'EVALUACION_PERIODO.ID_PERIODO')
            ->where('EVALUACION_DESEMPENO.ID_EVALUACION', $id)
            ->select(
                'EVALUACION_DESEMPENO.*',
                'EVALUACION_PERIODO.NOMBRE as PERIODO_NOMBRE',
                DB::raw("EVALUADO.NOMBRES || ' ' || EVALUADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                DB::raw("EVALUADOR.NOMBRES || ' ' || EVALUADOR.APELLIDO_1 as EVALUADOR_NOMBRE")
            )
            ->first();

        if (!$eval) {
            return response()->json(['error' => 'Evaluación no encontrada.'], 404);
        }

        return response()->json([
            'evaluacion' => $eval,
            'metas' => $this->evaluacion->getMetas((int) $id),
        ]);
    }

    public function saveMetas(Request $request, $id)
    {
        $request->validate(['metas' => 'required|array']);
        $this->evaluacion->saveMetas((int) $id, $request->metas);
        return response()->json(['message' => 'Metas guardadas.']);
    }

    public function completar(Request $request, $id)
    {
        $request->validate([
            'PUNTUACION_GLOBAL' => 'nullable|numeric|min:0|max:100',
            'COMENTARIOS_EVALUADOR' => 'nullable|string',
        ]);

        $this->evaluacion->completarEvaluacion(
            (int) $id,
            $request->PUNTUACION_GLOBAL,
            $request->COMENTARIOS_EVALUADOR
        );

        return response()->json(['message' => 'Evaluación completada.']);
    }

    public function resultados($idPeriodo)
    {
        return response()->json($this->evaluacion->resultadosPeriodo((int) $idPeriodo));
    }
}
