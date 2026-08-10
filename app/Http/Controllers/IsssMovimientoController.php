<?php

namespace App\Http\Controllers;

use App\Services\IsssMovimientoService;
use Illuminate\Http\Request;
use RuntimeException;

class IsssMovimientoController extends Controller
{
    public function __construct(protected IsssMovimientoService $movimientos)
    {
    }

    public function index(Request $request)
    {
        $movimientos = $this->movimientos->listar(
            $request->input('tipo') ?: null,
            $request->input('estado') ?: null
        );

        return response()->json(['data' => $movimientos->values()]);
    }

    public function marcarEnviado(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        try {
            $actualizados = $this->movimientos->marcarEnviado($request->ids, $request->user()?->USUARIO);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "{$actualizados} movimiento(s) marcado(s) como enviado(s)."]);
    }

    public function export(Request $request)
    {
        try {
            $file = $this->movimientos->exportarCsv(
                $request->input('tipo') ?: null,
                $request->input('estado', 'pendiente') ?: null
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }
}
