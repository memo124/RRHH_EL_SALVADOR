<?php

namespace App\Http\Controllers;

use App\Services\IsssPlanillaService;
use Illuminate\Http\Request;
use RuntimeException;

class IsssPlanillaController extends Controller
{
    public function __construct(protected IsssPlanillaService $isss)
    {
    }

    public function planillas(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA') ? (int) $request->input('ID_EMPRESA') : null;

        return response()->json(['data' => $this->isss->planillasParaSelect($empresaId)]);
    }

    public function preview(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            return response()->json($this->isss->preview((int) $request->ID_PLANILLA));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            $file = $this->isss->export((int) $request->ID_PLANILLA);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }
}
