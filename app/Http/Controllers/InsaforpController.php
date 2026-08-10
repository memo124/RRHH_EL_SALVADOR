<?php

namespace App\Http\Controllers;

use App\Services\InsaforpService;
use Illuminate\Http\Request;
use RuntimeException;

class InsaforpController extends Controller
{
    public function __construct(protected InsaforpService $insaforp)
    {
    }

    public function planillas(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA') ? (int) $request->input('ID_EMPRESA') : null;

        return response()->json(['data' => $this->insaforp->planillasParaSelect($empresaId)]);
    }

    public function preview(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            return response()->json($this->insaforp->preview((int) $request->ID_PLANILLA));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            $file = $this->insaforp->export((int) $request->ID_PLANILLA);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }
}
