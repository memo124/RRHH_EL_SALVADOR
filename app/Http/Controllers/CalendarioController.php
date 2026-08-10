<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\CalendarioEventoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected CalendarioEventoService $calendario) {}

    public function index(Request $request)
    {
        $events = $this->calendario->listForRange(
            $request->input('start'),
            $request->input('end'),
            $request->only(['tipo', 'ID_EMPRESA', 'ID_DEPARTAMENTO'])
        );

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'TIPO' => 'required|string|max:40',
            'TITULO' => 'required|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'FECHA_INICIO' => 'required|date',
            'FECHA_FIN' => 'nullable|date',
            'TODO_DIA' => 'boolean',
            'COLOR' => 'nullable|string|max:20',
            'ID_EMPLEADO' => 'nullable|integer',
            'ID_EMPRESA' => 'nullable|integer',
            'ID_DEPARTAMENTO' => 'nullable|integer',
        ]);

        $row = $this->calendario->create($request->all(), $request->user()->ID_USUARIO);

        return response()->json($row, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'TIPO' => 'sometimes|string|max:40',
            'TITULO' => 'sometimes|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'FECHA_INICIO' => 'sometimes|date',
            'FECHA_FIN' => 'nullable|date',
            'TODO_DIA' => 'boolean',
            'COLOR' => 'nullable|string|max:20',
            'ID_EMPLEADO' => 'nullable|integer',
            'ID_EMPRESA' => 'nullable|integer',
            'ID_DEPARTAMENTO' => 'nullable|integer',
        ]);

        $row = $this->calendario->update((int) $id, $request->all());
        if (!$row) {
            return response()->json(['error' => 'Evento no encontrado.'], 404);
        }

        return response()->json($row);
    }

    public function destroy($id)
    {
        if (!$this->calendario->softDelete((int) $id)) {
            return response()->json(['error' => 'Evento no encontrado.'], 404);
        }

        return response()->json(['message' => 'Evento eliminado correctamente.']);
    }

    public function tipos()
    {
        return response()->json([
            ['value' => 'manual', 'label' => 'Manual'],
            ['value' => 'capacitacion', 'label' => 'Capacitación'],
            ['value' => 'reunion_rrhh', 'label' => 'Reunión RRHH'],
            ['value' => 'vencimiento_contrato', 'label' => 'Vencimiento contrato'],
            ['value' => 'encuesta', 'label' => 'Encuesta activa'],
            ['value' => 'formulario', 'label' => 'Formulario / actualización'],
            ['value' => 'feriado', 'label' => 'Feriado'],
            ['value' => 'permiso', 'label' => 'Permiso / vacación'],
        ]);
    }
}
