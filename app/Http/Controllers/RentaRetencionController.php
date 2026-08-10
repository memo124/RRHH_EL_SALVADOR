<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthenticatesPrintRequests;
use App\Services\RentaRetencionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use RuntimeException;

class RentaRetencionController extends Controller
{
    use AuthenticatesPrintRequests;

    public function __construct(protected RentaRetencionService $renta)
    {
    }

    public function preview(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ANIO' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            return response()->json($this->renta->preview((int) $request->ID_EMPRESA, (int) $request->ANIO));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ANIO' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $file = $this->renta->export((int) $request->ID_EMPRESA, (int) $request->ANIO);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }

    public function pdf(Request $request)
    {
        $this->authenticatePrint($request);

        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ANIO' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $data = $this->renta->preview((int) $request->ID_EMPRESA, (int) $request->ANIO);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $pdf = Pdf::loadView('reportes.renta-anual', $data)->setPaper('letter', 'landscape');
        $filename = 'f14_renta_' . $request->ID_EMPRESA . '_' . $request->ANIO . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
