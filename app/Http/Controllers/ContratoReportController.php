<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\EmpresaFirmante;
use App\Services\EmpresaLogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ContratoReportController extends Controller
{
    private const MAX_LOTE = 500;

    public function __construct(protected EmpresaLogoService $logos)
    {
    }

    public function imprimir(Request $request, int $id): StreamedResponse
    {
        $this->authenticatePrint($request);
        $data = $this->getContratoData($id);

        return $this->responderContratoPdf($data, $id, $request);
    }

    public function pdf(Request $request, int $id): StreamedResponse
    {
        $this->authenticatePrint($request);
        $data = $this->getContratoData($id);

        return $this->responderContratoPdf($data, $id, $request);
    }

    public function imprimirLote(Request $request): StreamedResponse
    {
        return $this->pdfLote($request);
    }

    public function pdfLote(Request $request): StreamedResponse
    {
        $this->authenticatePrint($request);
        $items = $this->getContratosLoteData($this->parseIds($request));

        $pdf = Pdf::loadView('reportes.contratos-lote-document', compact('items'))
            ->setPaper('letter');

        $filename = 'contratos_lote_' . now()->format('Ymd_His') . '.pdf';

        return $this->responderPdf($pdf, $filename, $request);
    }

    public function zipLote(Request $request): Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authenticatePrint($request);
        $items = $this->getContratosLoteData($this->parseIds($request));

        $zipPath = tempnam(sys_get_temp_dir(), 'contratos_lote_');
        if ($zipPath === false) {
            abort(500, 'No se pudo crear el archivo temporal.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($items as $item) {
            $contrato = $item['contrato'];
            $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $contrato->NUMERO_CONTRATO ?? ('contrato_' . $contrato->ID_CONTRATO));
            $pdf = Pdf::loadView('reportes.contrato-document', $item)->setPaper('letter');
            $zip->addFromString("contrato_{$slug}.pdf", $pdf->output());
        }

        $zip->close();

        $filename = 'contratos_lote_' . now()->format('Ymd_His') . '.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getContratosLoteData(array $ids): array
    {
        $contratos = Contrato::with(['empleado.cargo', 'empleado.departamento', 'empresa', 'plantilla'])
            ->whereIn('ID_CONTRATO', $ids)
            ->orderBy('ID_CONTRATO')
            ->get()
            ->keyBy('ID_CONTRATO');

        if ($contratos->isEmpty()) {
            abort(404, 'No se encontraron contratos para imprimir.');
        }

        $items = [];
        foreach ($ids as $id) {
            if (!$contratos->has($id)) {
                continue;
            }
            $items[] = $this->buildContratoViewData($contratos->get($id));
        }

        if ($items === []) {
            abort(404, 'No se encontraron contratos para imprimir.');
        }

        return $items;
    }

    private function getContratoData(int $id): array
    {
        $contrato = Contrato::with(['empleado.cargo', 'empleado.departamento', 'empresa', 'plantilla'])
            ->findOrFail($id);

        return $this->buildContratoViewData($contrato);
    }

    private function buildContratoViewData(Contrato $contrato): array
    {
        if (empty($contrato->CONTENIDO_GENERADO)) {
            abort(422, "El contrato {$contrato->NUMERO_CONTRATO} no tiene contenido generado.");
        }

        $empresaLogo = $this->logos->resolveDataUri((int) $contrato->ID_EMPRESA);
        $empresa = $contrato->empresa;
        $firmantes = EmpresaFirmante::where('ID_EMPRESA', $contrato->ID_EMPRESA)
            ->where('ESACTIVO', true)
            ->orderBy('ORDEN')
            ->get();

        $empleado = $contrato->empleado;
        $nombreEmpleado = trim(
            ($empleado->NOMBRES ?? '') . ' ' .
            ($empleado->APELLIDO_1 ?? '') . ' ' .
            ($empleado->APELLIDO_2 ?? '')
        );

        return compact('contrato', 'empresa', 'empresaLogo', 'firmantes', 'nombreEmpleado');
    }

    private function responderContratoPdf(array $data, int $id, Request $request): StreamedResponse
    {
        $pdf = Pdf::loadView('reportes.contrato-document', $data)->setPaper('letter');
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['contrato']->NUMERO_CONTRATO ?? 'contrato');

        return $this->responderPdf($pdf, "contrato_{$id}_{$slug}.pdf", $request);
    }

    private function responderPdf(DomPdfDocument $pdf, string $filename, Request $request): StreamedResponse
    {
        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * @return array<int, int>
     */
    private function parseIds(Request $request): array
    {
        $raw = (string) $request->query('ids', '');
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));

        if ($ids === []) {
            abort(422, 'Debe indicar al menos un contrato.');
        }
        if (count($ids) > self::MAX_LOTE) {
            abort(422, 'Demasiados contratos en una sola solicitud (máximo ' . self::MAX_LOTE . ').');
        }

        return $ids;
    }

    private function authenticatePrint(Request $request): void
    {
        if (Auth::check()) {
            return;
        }
        $token = $request->query('token');
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
