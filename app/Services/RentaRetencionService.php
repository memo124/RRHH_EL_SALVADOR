<?php

namespace App\Services;

use App\Services\Concerns\BuildsDelimitedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Acumulado anual de renta retenida (ISR) por empresa, como insumo para el
 * Formulario F-14 (Declaración e Informe Anual de Retenciones) del Ministerio
 * de Hacienda.
 *
 * Decisión: no se genera el XML oficial del Ministerio de Hacienda (formato
 * propietario no documentado públicamente); se produce un acumulado CSV/Excel
 * por empleado que sirve de insumo para transcribir la declaración en el
 * portal del MH o a un contador externo.
 */
class RentaRetencionService
{
    use BuildsDelimitedFile;

    public function preview(int $empresaId, int $anio): array
    {
        return $this->buildReporte($empresaId, $anio);
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function export(int $empresaId, int $anio): array
    {
        $reporte = $this->buildReporte($empresaId, $anio);
        if (empty($reporte['filas'])) {
            throw new RuntimeException('No hay planillas cerradas con renta retenida para la empresa y año seleccionados.');
        }

        $headers = ['NIT', 'DUI', 'Nombre completo', 'Total devengado anual', 'Total renta retenida', 'Periodos con retención'];
        $rows = array_map(fn ($fila) => [
            $fila['NIT'],
            $fila['DUI'],
            $fila['NOMBRE'],
            $this->formatMonto($fila['TOTAL_DEVENGADO']),
            $this->formatMonto($fila['TOTAL_RENTA']),
            $fila['PERIODOS'],
        ], $reporte['filas']);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'f14_renta_' . $empresaId . '_' . $anio . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function buildReporte(int $empresaId, int $anio): array
    {
        $empresa = DB::table('EMPRESA')->where('ID_EMPRESA', $empresaId)->first();
        if (!$empresa) {
            throw new RuntimeException('Empresa no encontrada.');
        }

        $detalles = DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('PLANILLA.ID_EMPRESA', $empresaId)
            ->where('PLANILLA.CERRADA', true)
            ->where('PLANILLA.ANULADA', false)
            ->whereRaw('EXTRACT(YEAR FROM "PLANILLA"."FECHAPAGO") = ?', [$anio])
            ->select(
                'DETALLE_PLANILLA.ID_EMPLEADO',
                'DETALLE_PLANILLA.NOM_EMPLEADO',
                'DETALLE_PLANILLA.TOTAL_DEVENGADO',
                'DETALLE_PLANILLA.RENTA_EMPLEADO',
                'EMPLEADO.NIT',
                'EMPLEADO.DUI'
            )
            ->get();

        $porEmpleado = $detalles->groupBy('ID_EMPLEADO');

        $filas = $porEmpleado->map(function ($lineas) {
            $primero = $lineas->first();

            return [
                'NIT' => $primero->NIT ?? '',
                'DUI' => $primero->DUI ?? '',
                'NOMBRE' => $primero->NOM_EMPLEADO,
                'TOTAL_DEVENGADO' => round((float) $lineas->sum('TOTAL_DEVENGADO'), 2),
                'TOTAL_RENTA' => round((float) $lineas->sum('RENTA_EMPLEADO'), 2),
                'PERIODOS' => $lineas->where('RENTA_EMPLEADO', '>', 0)->count(),
            ];
        })->sortByDesc('TOTAL_RENTA')->values()->all();

        return [
            'empresa' => $empresa,
            'anio' => $anio,
            'filas' => $filas,
            'totales' => [
                'devengado' => round(array_sum(array_column($filas, 'TOTAL_DEVENGADO')), 2),
                'renta' => round(array_sum(array_column($filas, 'TOTAL_RENTA')), 2),
                'count' => count($filas),
            ],
        ];
    }
}
