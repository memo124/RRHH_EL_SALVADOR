<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanillaBankExportService
{
    public function getCatalog(int $planillaId): array
    {
        $this->assertPlanillaWithDetails($planillaId);

        $config = config('planilla_banco_export');
        $banks = $this->getBanksWithCounts($planillaId);

        $columns = collect($config['columns'])->map(function ($meta, $key) {
            return array_merge(['key' => $key], $meta);
        })->values();

        $defaultColumns = $columns->filter(fn ($c) => $c['default'] ?? false)->pluck('key')->values()->all();

        return [
            'formats' => collect($config['formats'])->map(fn ($f, $k) => array_merge(['key' => $k], $f))->values(),
            'delimiters' => collect($config['delimiters'])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'amount_formats' => collect($config['amount_formats'])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'column_groups' => $config['column_groups'],
            'columns' => $columns,
            'default_columns' => $defaultColumns,
            'bank_presets' => $config['bank_presets'],
            'banks' => $banks,
        ];
    }

    public function preview(int $planillaId, array $options, int $defaultLimit = 5): array
    {
        $rows = $this->buildRows($planillaId, $options);
        $columns = $this->resolveColumns($options);
        $limit = array_key_exists('limit', $options) ? (int) $options['limit'] : $defaultLimit;

        $previewRows = $limit <= 0
            ? $rows
            : $rows->take($limit);

        return [
            'count' => $rows->count(),
            'total_liquido' => round($rows->sum(fn ($r) => (float) ($r['LIQUIDO_A_RECIBIR'] ?? 0)), 2),
            'columns' => $this->columnLabels($columns),
            'preview' => $previewRows->map(fn ($row) => $this->projectRow($row, $columns, $options))->values(),
        ];
    }

    public function generate(int $planillaId, array $options): array
    {
        $rows = $this->buildRows($planillaId, $options);
        if ($rows->isEmpty()) {
            throw new RuntimeException('No hay empleados para el banco seleccionado en esta planilla.');
        }

        $columns = $this->resolveColumns($options);
        $format = $options['format'] ?? 'csv';
        $filename = $this->buildFilename($planillaId, $options, $format);

        if ($format === 'xlsx') {
            throw new RuntimeException('El formato Excel se genera en el navegador. Use la opción Excel (.xlsx) desde la pantalla de planilla.');
        }

        $content = match ($format) {
            'json' => $this->toJson($rows, $columns, $options),
            'txt_fixed' => $this->toFixedWidth($rows, $columns, $options),
            'txt_csv' => $this->toDelimited($rows, $columns, $options, $options['delimiter'] ?? ';'),
            default => $this->toDelimited($rows, $columns, $options, $options['delimiter'] ?? ','),
        };

        $mime = config("planilla_banco_export.formats.{$format}.mime", 'text/plain');

        return compact('content', 'filename', 'mime');
    }

    protected function buildRows(int $planillaId, array $options): Collection
    {
        $this->assertPlanillaWithDetails($planillaId);

        $query = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('BANCO', 'EMPLEADO.ID_BANCO', '=', 'BANCO.ID_BANCO')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->leftJoin('CUENTA', 'PLANILLA.ID_CUENTA', '=', 'CUENTA.ID_CUENTA')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->select(
                'DETALLE_PLANILLA.*',
                'EMPLEADO.CODIGOEMPLEADO',
                'EMPLEADO.DUI',
                'EMPLEADO.NIT as NIT_EMPLEADO',
                'EMPLEADO.NUMEROCUENTA',
                'EMPLEADO.ID_BANCO',
                'BANCO.NOMBREBANCO',
                'BANCO.ALIAS as BANCO_ALIAS',
                'PLANILLA.TITULO',
                'PLANILLA.FECHAPAGO',
                'PLANILLA.FORMAPAGO',
                'PERIODO_LABORAL.CALPERIODO',
                'CUENTA.NUMEROCUENTA as CUENTA_EMPRESA_NUM',
                'CUENTA.CONCEPTOCUENTA'
            )
            ->orderBy('BANCO.NOMBREBANCO')
            ->orderBy('DETALLE_PLANILLA.NOM_EMPLEADO');

        if (!empty($options['ID_BANCO'])) {
            $query->where('EMPLEADO.ID_BANCO', (int) $options['ID_BANCO']);
        }

        if (!empty($options['solo_con_cuenta'])) {
            $query->whereNotNull('EMPLEADO.NUMEROCUENTA')
                ->where('EMPLEADO.NUMEROCUENTA', '<>', '');
        }

        return $query->get()->map(function ($row) {
            return [
                'NUMERO_CUENTA' => preg_replace('/\s+/', '', (string) ($row->NUMEROCUENTA ?? '')),
                'BANCO_NOMBRE' => $row->NOMBREBANCO ?? '',
                'BANCO_ALIAS' => $row->BANCO_ALIAS ?? '',
                'CODIGO_EMPLEADO' => $row->CODIGOEMPLEADO ?? '',
                'NOM_EMPLEADO' => $row->NOM_EMPLEADO ?? '',
                'DUI' => $row->DUI ?? '',
                'NIT' => $row->NIT_EMPLEADO ?? '',
                'TITULO_PLANILLA' => $row->TITULO ?? '',
                'PERIODO' => $row->CALPERIODO ?? '',
                'FECHA_PAGO' => $row->FECHAPAGO ? date('d/m/Y', strtotime($row->FECHAPAGO)) : '',
                'FORMA_PAGO' => $row->FORMAPAGO ?? '',
                'CUENTA_EMPRESA' => $row->CUENTA_EMPRESA_NUM ?? '',
                'CONCEPTO_EMPRESA' => $row->CONCEPTOCUENTA ?? '',
                'TOTAL_DEVENGADO' => (float) ($row->TOTAL_DEVENGADO ?? 0),
                'TOTAL_DEDUCCIONES' => (float) ($row->TOTAL_DEDUCCIONES ?? 0),
                'LIQUIDO_A_RECIBIR' => (float) ($row->LIQUIDO_A_RECIBIR ?? 0),
                'AFP_EMPLEADO' => (float) ($row->AFP_EMPLEADO ?? 0),
                'ISSS_EMPLEADO' => (float) ($row->ISSS_EMPLEADO ?? 0),
                'RENTA_EMPLEADO' => (float) ($row->RENTA_EMPLEADO ?? 0),
                'PRESTAMOS' => (float) ($row->PRESTAMOS ?? 0),
                'OTRO_DESCUENTOS' => (float) ($row->OTRO_DESCUENTOS ?? 0),
                'SALARIO_BASE' => (float) ($row->SALARIO_BASE ?? 0),
            ];
        });
    }

    protected function getBanksWithCounts(int $planillaId): array
    {
        $counts = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('BANCO', 'EMPLEADO.ID_BANCO', '=', 'BANCO.ID_BANCO')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->select(
                'EMPLEADO.ID_BANCO',
                'BANCO.NOMBREBANCO',
                'BANCO.ALIAS',
                DB::raw('COUNT(*) as "TOTAL"'),
                DB::raw('SUM("DETALLE_PLANILLA"."LIQUIDO_A_RECIBIR") as "LIQUIDO_TOTAL"')
            )
            ->groupBy('EMPLEADO.ID_BANCO', 'BANCO.NOMBREBANCO', 'BANCO.ALIAS')
            ->orderBy('BANCO.NOMBREBANCO')
            ->get();

        $banks = [
            [
                'ID_BANCO' => null,
                'NOMBREBANCO' => 'Todos los bancos',
                'ALIAS' => 'ALL',
                'TOTAL' => $counts->sum('TOTAL'),
                'LIQUIDO_TOTAL' => round($counts->sum('LIQUIDO_TOTAL'), 2),
            ],
        ];

        foreach ($counts as $c) {
            $banks[] = [
                'ID_BANCO' => $c->ID_BANCO,
                'NOMBREBANCO' => $c->NOMBREBANCO ?? 'Sin banco asignado',
                'ALIAS' => $c->ALIAS,
                'TOTAL' => (int) $c->TOTAL,
                'LIQUIDO_TOTAL' => round((float) $c->LIQUIDO_TOTAL, 2),
            ];
        }

        return $banks;
    }

    protected function resolveColumns(array $options): array
    {
        $columns = $options['columns'] ?? [];
        if (empty($columns)) {
            $columns = collect(config('planilla_banco_export.columns'))
                ->filter(fn ($c) => $c['default'] ?? false)
                ->keys()
                ->all();
        }

        $valid = array_keys(config('planilla_banco_export.columns'));

        return array_values(array_filter($columns, fn ($c) => in_array($c, $valid, true)));
    }

    protected function columnLabels(array $columns): array
    {
        $config = config('planilla_banco_export.columns');

        return collect($columns)->map(fn ($key) => [
            'key' => $key,
            'label' => $config[$key]['label'] ?? $key,
        ])->all();
    }

    protected function projectRow(array $row, array $columns, array $options): array
    {
        $projected = [];
        foreach ($columns as $col) {
            $value = $row[$col] ?? '';
            if ($this->isNumericColumn($col)) {
                $value = $this->formatAmount((float) $value, $options['amount_format'] ?? 'decimal');
            }
            $projected[$col] = $value;
        }

        return $projected;
    }

    protected function toDelimited(Collection $rows, array $columns, array $options, string $delimiter): string
    {
        $lines = [];
        $includeHeader = $options['include_header'] ?? true;
        $labels = $this->columnLabels($columns);

        if ($includeHeader) {
            $lines[] = $this->csvLine(array_column($labels, 'label'), $delimiter);
        }

        foreach ($rows as $row) {
            $lines[] = $this->csvLine(array_values($this->projectRow($row, $columns, $options)), $delimiter);
        }

        return "\xEF\xBB\xBF" . implode("\r\n", $lines);
    }

    protected function toFixedWidth(Collection $rows, array $columns, array $options): string
    {
        $config = config('planilla_banco_export.columns');
        $lines = [];
        $includeHeader = $options['include_header'] ?? false;

        if ($includeHeader) {
            $header = '';
            foreach ($columns as $col) {
                $width = $config[$col]['width'] ?? 15;
                $label = $config[$col]['label'] ?? $col;
                $header .= $this->pad($label, $width);
            }
            $lines[] = rtrim($header);
        }

        foreach ($rows as $row) {
            $line = '';
            $projected = $this->projectRow($row, $columns, $options);
            foreach ($columns as $col) {
                $width = $config[$col]['width'] ?? 15;
                $line .= $this->pad((string) ($projected[$col] ?? ''), $width);
            }
            $lines[] = rtrim($line);
        }

        return implode("\r\n", $lines);
    }

    protected function toJson(Collection $rows, array $columns, array $options): string
    {
        $data = $rows->map(fn ($row) => $this->projectRow($row, $columns, $options))->values();

        return json_encode([
            'registros' => $data->count(),
            'columnas' => $this->columnLabels($columns),
            'datos' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function csvLine(array $fields, string $delimiter): string
    {
        $escaped = array_map(function ($field) {
            $field = (string) $field;
            if (str_contains($field, '"') || str_contains($field, ',') || str_contains($field, ';') || str_contains($field, "\t") || str_contains($field, '|')) {
                return '"' . str_replace('"', '""', $field) . '"';
            }

            return $field;
        }, $fields);

        return implode($delimiter, $escaped);
    }

    protected function pad(string $value, int $width): string
    {
        $value = mb_substr($value, 0, $width);

        return str_pad($value, $width, ' ', STR_PAD_RIGHT);
    }

    protected function formatAmount(float $amount, string $format): string
    {
        if ($format === 'cents') {
            return (string) (int) round($amount * 100);
        }

        return number_format($amount, 2, '.', '');
    }

    protected function isNumericColumn(string $key): bool
    {
        return (bool) (config("planilla_banco_export.columns.{$key}.numeric") ?? false);
    }

    protected function buildFilename(int $planillaId, array $options, string $format): string
    {
        $ext = config("planilla_banco_export.formats.{$format}.extension", 'txt');
        $bank = !empty($options['ID_BANCO']) ? 'banco_' . $options['ID_BANCO'] : 'todos';
        $date = date('Ymd_His');

        return "planilla_{$planillaId}_{$bank}_{$date}.{$ext}";
    }

    protected function assertPlanillaWithDetails(int $planillaId): void
    {
        $exists = DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId)->exists();
        if (!$exists) {
            throw new RuntimeException('La planilla no tiene detalles calculados.');
        }
    }
}
