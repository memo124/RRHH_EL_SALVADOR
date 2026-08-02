<?php

namespace App\Http\Controllers;

use App\Services\PlanillaReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class PlanillaReportController extends Controller
{
    protected PlanillaReportService $reports;

    public function __construct(PlanillaReportService $reports)
    {
        $this->reports = $reports;
    }

    public function imprimirPlanilla(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        $query = http_build_query(array_filter([
            'token' => $request->query('token'),
            'tamano' => $request->query('tamano', 'legal'),
            'orientacion' => $request->query('orientacion', 'landscape'),
        ]));

        return redirect("/reportes/planillas/{$id}/pdf?{$query}");
    }

    public function imprimirBoletas(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        $query = http_build_query(array_filter([
            'token' => $request->query('token'),
        ]));

        return redirect("/reportes/planillas/{$id}/boletas/pdf?{$query}");
    }

    public function imprimirBoleta(Request $request, int $id, int $detalleId)
    {
        $this->authenticatePrint($request);

        $query = http_build_query(array_filter([
            'token' => $request->query('token'),
        ]));

        return redirect("/reportes/planillas/{$id}/boletas/{$detalleId}/pdf?{$query}");
    }

    public function pdfPlanilla(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getPlanillaData($id);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $formato = $this->resolvePrintFormat($request);
        $data = array_merge($data, $formato);

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['planilla']->TITULO ?? 'planilla');
        $pdf = Pdf::loadView('reportes.planilla-document', $data)
            ->setPaper($formato['tamano'], $formato['orientacion']);

        $filename = "planilla_{$id}_{$slug}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function pdfBoletas(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getPlanillaData($id);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['planilla']->TITULO ?? 'boletas');
        $pdf = Pdf::loadView('reportes.boletas-document', $data)
            ->setPaper('letter', 'portrait');

        $filename = "boletas_{$id}_{$slug}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function pdfBoleta(Request $request, int $id, int $detalleId)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getBoletaData($id, $detalleId);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $nombre = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['detalle']->NOM_EMPLEADO ?? 'empleado');
        $pdf = Pdf::loadView('reportes.boleta-document', $data)
            ->setPaper('letter', 'portrait');

        $filename = "boleta_{$id}_{$nombre}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    protected function authenticatePrint(Request $request): void
    {
        if (Auth::check()) {
            return;
        }

        $token = $request->query('token') ?? $request->bearerToken();
        if (!$token) {
            abort(401, 'No autenticado.');
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            abort(401, 'Token inválido.');
        }

        Auth::login($accessToken->tokenable);
    }

    /**
     * @return array{tamano: string, orientacion: string, paperSize: string, paperOrientation: string}
     */
    protected function resolvePrintFormat(Request $request): array
    {
        $tamano = strtolower((string) $request->query('tamano', 'legal'));
        $permitidos = ['letter', 'legal', 'a4', 'tabloid'];
        if (!in_array($tamano, $permitidos, true)) {
            $tamano = 'legal';
        }

        $orientacion = strtolower((string) $request->query('orientacion', 'landscape'));
        if (!in_array($orientacion, ['landscape', 'portrait'], true)) {
            $orientacion = 'landscape';
        }

        return [
            'tamano' => $tamano,
            'orientacion' => $orientacion,
            'paperSize' => $tamano,
            'paperOrientation' => $orientacion,
        ];
    }
}
