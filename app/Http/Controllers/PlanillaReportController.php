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

        try {
            $data = $this->reports->getPlanillaData($id);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        return view('reportes.planilla', $data);
    }

    public function imprimirBoletas(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getPlanillaData($id);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        return view('reportes.boletas-todas', $data);
    }

    public function imprimirBoleta(Request $request, int $id, int $detalleId)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getBoletaData($id, $detalleId);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        return view('reportes.boleta', $data);
    }

    public function pdfPlanilla(Request $request, int $id)
    {
        $this->authenticatePrint($request);

        try {
            $data = $this->reports->getPlanillaData($id);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['planilla']->TITULO ?? 'planilla');
        $pdf = Pdf::loadView('reportes.planilla', $data)
            ->setPaper('legal', 'landscape');

        return $pdf->download("planilla_{$id}_{$slug}.pdf");
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
        $pdf = Pdf::loadView('reportes.boletas-todas', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download("boletas_{$id}_{$slug}.pdf");
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
        $pdf = Pdf::loadView('reportes.boleta', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download("boleta_{$id}_{$nombre}.pdf");
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
}
