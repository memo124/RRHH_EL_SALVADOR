<?php

namespace App\Services;

use App\Services\Concerns\BuildsDelimitedFile;
use App\Services\Concerns\ListsClosedPlanillas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reporte/exportación de la cotización INSAFORP (1% patronal) por planilla cerrada.
 *
 * Ley de Formación Profesional: aplica únicamente a empresas con más de 10
 * empleados (`PayrollMonthlyCeilingService::empresaAplicaInsaforp`), con techo
 * mensual de $1,000 por empleado cotizante — ya aplicado al momento del cálculo
 * de la planilla y almacenado en `DETALLE_PLANILLA.INSAFORP_PATRONAL`.
 */
class InsaforpService
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
            throw new RuntimeException('La planilla no tiene empleados cotizantes de INSAFORP.');
        }

        $headers = ['Código empleado', 'Nombre completo', 'Salario base cotizable', 'INSAFORP patronal (1%)'];
        $rows = array_map(fn ($fila) => [
            $fila['CODIGOEMPLEADO'],
            $fila['NOMBRE'],
            $this->formatMonto($fila['SALARIO_COTIZABLE']),
            $this->formatMonto($fila['INSAFORP_PATRONAL']),
        ], $reporte['filas']);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'insaforp_planilla_' . $planillaId . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function buildReporte(int $planillaId): array
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new RuntimeException('Planilla no encontrada.');
        }
        if (!$planilla->CERRADA) {
            throw new RuntimeException('Solo se pueden reportar planillas cerradas.');
        }

        $aplicaActualmente = $this->ceiling->empresaAplicaInsaforp((int) $planilla->ID_EMPRESA);

        $detalles = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->where('DETALLE_PLANILLA.APLICA_INSAFORP', true)
            ->where('DETALLE_PLANILLA.INSAFORP_PATRONAL', '>', 0)
            ->select(
                'DETALLE_PLANILLA.NOM_EMPLEADO',
                'DETALLE_PLANILLA.DEVENGADO_GRAVADO',
                'DETALLE_PLANILLA.INSAFORP_PATRONAL',
                'EMPLEADO.CODIGOEMPLEADO'
            )
            ->orderBy('DETALLE_PLANILLA.CORRELATIVO')
            ->get();

        $filas = $detalles->map(fn ($row) => [
            'CODIGOEMPLEADO' => $row->CODIGOEMPLEADO ?? '',
            'NOMBRE' => $row->NOM_EMPLEADO,
            'SALARIO_COTIZABLE' => min((float) $row->DEVENGADO_GRAVADO, PayrollMonthlyCeilingService::TECHO_INSAFORP),
            'INSAFORP_PATRONAL' => (float) $row->INSAFORP_PATRONAL,
        ])->values()->all();

        return [
            'planilla' => $planilla,
            'aplica_actualmente' => $aplicaActualmente,
            'filas' => $filas,
            'totales' => [
                'insaforp' => round(array_sum(array_column($filas, 'INSAFORP_PATRONAL')), 2),
                'count' => count($filas),
            ],
        ];
    }
}
