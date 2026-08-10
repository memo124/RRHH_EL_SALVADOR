<?php

namespace App\Http\Controllers;

use App\Services\AguinaldoCorridaService;
use Illuminate\Http\Request;
use RuntimeException;

class AguinaldoCorridaController extends Controller
{
    public function __construct(protected AguinaldoCorridaService $aguinaldo)
    {
    }

    public function preview(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'FECHA_CORTE' => 'required|date',
        ]);

        return response()->json($this->aguinaldo->preview((int) $request->ID_EMPRESA, $request->FECHA_CORTE));
    }

    public function export(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'FECHA_CORTE' => 'required|date',
        ]);

        try {
            $file = $this->aguinaldo->export((int) $request->ID_EMPRESA, $request->FECHA_CORTE);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }

    public function crearPlanilla(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ID_PERIODO' => 'required|integer',
            'ID_FRECUENCIAPAGO' => 'required|integer',
            'ID_CUENTA' => 'required|integer',
            'TITULO' => 'required|string|max:250',
            'FECHAPAGO' => 'required|date',
            'FORMAPAGO' => 'required|string|max:50',
            'OBSERVACION' => 'nullable|string|max:500',
        ]);

        try {
            $id = $this->aguinaldo->crearPlanilla($request->all());
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ID_PLANILLA' => $id,
            'message' => 'Planilla de aguinaldo creada. Calcule el detalle desde el módulo de Planilla.',
        ], 201);
    }
}
