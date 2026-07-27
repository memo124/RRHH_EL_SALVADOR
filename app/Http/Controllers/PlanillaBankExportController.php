<?php

namespace App\Http\Controllers;

use App\Services\PlanillaBankExportService;
use Illuminate\Http\Request;
use RuntimeException;

class PlanillaBankExportController extends Controller
{
    public function __construct(protected PlanillaBankExportService $export)
    {
    }

    public function catalog(int $id)
    {
        try {
            return response()->json($this->export->getCatalog($id));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function preview(Request $request, int $id)
    {
        $options = $this->validatedOptions($request);

        try {
            return response()->json($this->export->preview($id, $options));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function generate(Request $request, int $id)
    {
        $options = $this->validatedOptions($request);

        try {
            $file = $this->export->generate($id, $options);

            return response($file['content'], 200, [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    protected function validatedOptions(Request $request): array
    {
        $validColumns = array_keys(config('planilla_banco_export.columns'));
        $validFormats = array_keys(config('planilla_banco_export.formats'));

        $request->validate([
            'ID_BANCO' => 'nullable|integer',
            'format' => 'required|in:' . implode(',', $validFormats),
            'delimiter' => 'nullable|string|max:3',
            'amount_format' => 'nullable|in:decimal,cents',
            'columns' => 'required|array|min:1',
            'columns.*' => 'in:' . implode(',', $validColumns),
            'include_header' => 'nullable|boolean',
            'solo_con_cuenta' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:0|max:10000',
        ]);

        return [
            'ID_BANCO' => $request->input('ID_BANCO') ?: null,
            'format' => $request->input('format'),
            'delimiter' => $request->input('delimiter', ','),
            'amount_format' => $request->input('amount_format', 'decimal'),
            'columns' => array_values($request->input('columns', [])),
            'include_header' => $request->boolean('include_header', true),
            'solo_con_cuenta' => $request->boolean('solo_con_cuenta', true),
            'limit' => $request->has('limit') ? (int) $request->input('limit') : null,
        ];
    }
}
