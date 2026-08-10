<?php

namespace App\Services;

use App\Models\Planilla;
use App\Services\Concerns\BuildsDelimitedFile;
use App\Services\Concerns\ListsClosedPlanillas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reporte/exportación de cotizaciones ISSS por planilla cerrada.
 *
 * Decisión de formato: archivo plano delimitado por punto y coma (";"), codificado
 * en UTF-8 con BOM, compatible con la carga manual al portal patronal del ISSS
 * (el ISSS no publica un layout XML/API oficial de integración directa).
 */
class IsssPlanillaService
{
    use BuildsDelimitedFile;
    use ListsClosedPlanillas;

    public function __construct(protected PayrollMonthlyCeilingService $ceiling)
    {
    }

    public function planillasParaSelect(?int $empresaId = null): Collection
    {
        return $this->closedPlanillasQuery($empresaId)->get();
    }

    public function preview(int $planillaId): array
    {
        return $this->buildReporte($planillaId);
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function export(int $planillaId): array
    {
        $reporte = $this->buildReporte($planillaId);
        if (empty($reporte['filas'])) {
            throw new RuntimeException('La planilla no tiene empleados cotizantes de ISSS.');
        }

        $headers = ['N° Patronal ISSS', 'N° ISSS Empleado', 'DUI', 'Nombre completo', 'Salario cotizable', 'Cotización laboral (3%)', 'Cotización patronal (7.5%)'];
        $rows = array_map(fn ($fila) => [
            $fila['NUMERO_PATRONAL'],
            $fila['ISSS_EMPLEADO_NUM'],
            $fila['DUI'],
            $fila['NOMBRE'],
            $this->formatMonto($fila['SALARIO_COTIZABLE']),
            $this->formatMonto($fila['COTIZACION_LABORAL']),
            $this->formatMonto($fila['COTIZACION_PATRONAL']),
        ], $reporte['filas']);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'isss_planilla_' . $planillaId . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function buildReporte(int $planillaId): array
    {
        $planilla = Planilla::with(['periodoLaboral', 'empresa'])->find($planillaId);
        if (!$planilla) {
            throw new RuntimeException('Planilla no encontrada.');
        }
        if (!$planilla->CERRADA) {
            throw new RuntimeException('Solo se pueden reportar planillas cerradas.');
        }

        $numeroPatronal = $planilla->empresa->NUMEROPATRONALISSS ?: 'PENDIENTE-ASIGNAR';

        $detalles = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->where('DETALLE_PLANILLA.APLICA_ISSS', true)
            ->select(
                'DETALLE_PLANILLA.ID_EMPLEADO',
                'DETALLE_PLANILLA.NOM_EMPLEADO',
                'DETALLE_PLANILLA.DEVENGADO_GRAVADO',
                'DETALLE_PLANILLA.ISSS_EMPLEADO',
                'DETALLE_PLANILLA.ISSS_PATRONAL',
                'EMPLEADO.ISSS as ISSS_EMPLEADO_NUM',
                'EMPLEADO.DUI'
            )
            ->orderBy('DETALLE_PLANILLA.CORRELATIVO')
            ->get();

        $filas = [];
        $totalLaboral = 0.0;
        $totalPatronal = 0.0;
        $totalCotizable = 0.0;

        foreach ($detalles as $detalle) {
            $acumuladoMes = $this->ceiling->getDevengadoAcumuladoMes((int) $detalle->ID_EMPLEADO, $planilla);
            $calculo = $this->ceiling->calcularIsss((float) $detalle->DEVENGADO_GRAVADO, $acumuladoMes, true);

            $filas[] = [
                'NUMERO_PATRONAL' => $numeroPatronal,
                'ISSS_EMPLEADO_NUM' => $detalle->ISSS_EMPLEADO_NUM ?? '',
                'DUI' => $detalle->DUI ?? '',
                'NOMBRE' => $detalle->NOM_EMPLEADO,
                'SALARIO_COTIZABLE' => $calculo['base'],
                'COTIZACION_LABORAL' => (float) $detalle->ISSS_EMPLEADO,
                'COTIZACION_PATRONAL' => (float) $detalle->ISSS_PATRONAL,
            ];

            $totalCotizable += $calculo['base'];
            $totalLaboral += (float) $detalle->ISSS_EMPLEADO;
            $totalPatronal += (float) $detalle->ISSS_PATRONAL;
        }

        return [
            'planilla' => $planilla,
            'numero_patronal' => $numeroPatronal,
            'filas' => $filas,
            'totales' => [
                'cotizable' => round($totalCotizable, 2),
                'laboral' => round($totalLaboral, 2),
                'patronal' => round($totalPatronal, 2),
                'count' => count($filas),
            ],
        ];
    }
}
