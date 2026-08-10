<?php

namespace App\Http\Controllers;

use App\Services\AfpPlanillaService;
use Illuminate\Http\Request;
use RuntimeException;

class AfpPlanillaController extends Controller
{
    public function __construct(protected AfpPlanillaService $afp)
    {
    }

    public function planillas(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA') ? (int) $request->input('ID_EMPRESA') : null;

        return response()->json(['data' => $this->afp->planillasParaSelect($empresaId)]);
    }

    public function catalogo(Request $request)
    {
        $request->validate(['ID_PLANILLA' => 'required|integer']);

        try {
            return response()->json(['data' => $this->afp->catalogoAfp((int) $request->ID_PLANILLA)]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function preview(Request $request)
    {
        $request->validate([
            'ID_PLANILLA' => 'required|integer',
            'ID_AFP' => 'nullable|integer',
        ]);

        try {
            return response()->json($this->afp->preview(
                (int) $request->ID_PLANILLA,
                $request->filled('ID_AFP') ? (int) $request->ID_AFP : null
            ));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'ID_PLANILLA' => 'required|integer',
            'ID_AFP' => 'nullable|integer',
        ]);

        try {
            $file = $this->afp->export(
                (int) $request->ID_PLANILLA,
                $request->filled('ID_AFP') ? (int) $request->ID_AFP : null
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
