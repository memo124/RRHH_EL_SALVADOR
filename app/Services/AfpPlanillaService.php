<?php

namespace App\Services;

use App\Services\Concerns\BuildsDelimitedFile;
use App\Services\Concerns\ListsClosedPlanillas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reporte/exportación de aportes AFP por planilla cerrada.
 *
 * Decisión: se genera un archivo por AFP (CRECER, CONFIA, etc.) ya que cada
 * administradora recibe su propio reporte de cotizantes; el catálogo indica
 * cuántos empleados y cuánto líquido corresponde a cada una para que RRHH
 * descargue un archivo por administradora seleccionada.
 */
class AfpPlanillaService
{
    use BuildsDelimitedFile;
    use ListsClosedPlanillas;

    public function planillasParaSelect(?int $empresaId = null): Collection
    {
        return $this->closedPlanillasQuery($empresaId)->get();
    }

    public function catalogoAfp(int $planillaId): array
    {
        $this->assertPlanillaCerrada($planillaId);

        $conteos = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('AFP', 'EMPLEADO.ID_AFP', '=', 'AFP.ID_AFP')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->where('DETALLE_PLANILLA.APLICA_AFP', true)
            ->select(
                'EMPLEADO.ID_AFP',
                'AFP.NOMBREAFP',
                DB::raw('COUNT(*) as "TOTAL"'),
                DB::raw('SUM("DETALLE_PLANILLA"."AFP_EMPLEADO") as "APORTE_LABORAL_TOTAL"'),
                DB::raw('SUM("DETALLE_PLANILLA"."AFP_PATRONAL") as "APORTE_PATRONAL_TOTAL"')
            )
            ->groupBy('EMPLEADO.ID_AFP', 'AFP.NOMBREAFP')
            ->orderBy('AFP.NOMBREAFP')
            ->get();

        return $conteos->map(fn ($row) => [
            'ID_AFP' => $row->ID_AFP,
            'NOMBREAFP' => $row->NOMBREAFP ?? 'Sin AFP asignada',
            'TOTAL' => (int) $row->TOTAL,
            'APORTE_LABORAL_TOTAL' => round((float) $row->APORTE_LABORAL_TOTAL, 2),
            'APORTE_PATRONAL_TOTAL' => round((float) $row->APORTE_PATRONAL_TOTAL, 2),
        ])->values()->all();
    }

    public function preview(int $planillaId, ?int $afpId = null): array
    {
        return $this->buildReporte($planillaId, $afpId);
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function export(int $planillaId, ?int $afpId = null): array
    {
        $reporte = $this->buildReporte($planillaId, $afpId);
        if (empty($reporte['filas'])) {
            throw new RuntimeException('No hay empleados cotizantes de AFP para el filtro seleccionado.');
        }

        $headers = ['NUP', 'Nombre completo', 'AFP', 'Aporte laboral', 'Aporte patronal'];
        $rows = array_map(fn ($fila) => [
            $fila['NUP'],
            $fila['NOMBRE'],
            $fila['AFP'],
            $this->formatMonto($fila['APORTE_LABORAL']),
            $this->formatMonto($fila['APORTE_PATRONAL']),
        ], $reporte['filas']);

        $sufijoAfp = $afpId ? 'afp' . $afpId : 'todas';

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'afp_planilla_' . $planillaId . '_' . $sufijoAfp . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function buildReporte(int $planillaId, ?int $afpId): array
    {
        $this->assertPlanillaCerrada($planillaId);

        $query = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('AFP', 'EMPLEADO.ID_AFP', '=', 'AFP.ID_AFP')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->where('DETALLE_PLANILLA.APLICA_AFP', true)
            ->select(
                'DETALLE_PLANILLA.NOM_EMPLEADO',
                'DETALLE_PLANILLA.AFP_EMPLEADO',
                'DETALLE_PLANILLA.AFP_PATRONAL',
                'EMPLEADO.NUP',
                'AFP.NOMBREAFP'
            )
            ->orderBy('DETALLE_PLANILLA.CORRELATIVO');

        if ($afpId) {
            $query->where('EMPLEADO.ID_AFP', $afpId);
        }

        $filas = $query->get()->map(fn ($row) => [
            'NUP' => $row->NUP ?? '',
            'NOMBRE' => $row->NOM_EMPLEADO,
            'AFP' => $row->NOMBREAFP ?? 'Sin AFP asignada',
            'APORTE_LABORAL' => (float) $row->AFP_EMPLEADO,
            'APORTE_PATRONAL' => (float) $row->AFP_PATRONAL,
        ])->values()->all();

        return [
            'filas' => $filas,
            'totales' => [
                'laboral' => round(array_sum(array_column($filas, 'APORTE_LABORAL')), 2),
                'patronal' => round(array_sum(array_column($filas, 'APORTE_PATRONAL')), 2),
                'count' => count($filas),
            ],
        ];
    }

    protected function assertPlanillaCerrada(int $planillaId): void
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new RuntimeException('Planilla no encontrada.');
        }
        if (!$planilla->CERRADA) {
            throw new RuntimeException('Solo se pueden reportar planillas cerradas.');
        }
    }
}
