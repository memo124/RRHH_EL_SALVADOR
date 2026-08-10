<?php

namespace App\Http\Controllers;

use App\Services\Retencion10Service;
use Illuminate\Http\Request;
use RuntimeException;

class Retencion10Controller extends Controller
{
    public function __construct(protected Retencion10Service $retencion)
    {
    }

    public function planillas(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA') ? (int) $request->input('ID_EMPRESA') : null;

        return response()->json(['data' => $this->retencion->planillasParaSelect($empresaId)]);
    }

    public function estimacion(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA') ? (int) $request->input('ID_EMPRESA') : null;

        return response()->json($this->retencion->estimacionActual($empresaId));
    }

    public function preview(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            return response()->json($this->retencion->preview((int) $request->ID_PLANILLA));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'ID_PLANILLA' => 'nullable|integer',
            'ID_EMPRESA' => 'nullable|integer',
        ]);

        try {
            $file = $this->retencion->export(
                $request->filled('ID_PLANILLA') ? (int) $request->ID_PLANILLA : null,
                $request->filled('ID_EMPRESA') ? (int) $request->ID_EMPRESA : null
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
