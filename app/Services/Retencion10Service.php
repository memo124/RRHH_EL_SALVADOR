<?php

namespace App\Services;

use App\Services\Concerns\BuildsDelimitedFile;
use App\Services\Concerns\ListsClosedPlanillas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Retención del 10% sobre servicios profesionales / honorarios (Art. 156 Código
 * Tributario). El sistema ya retiene este 10% al calcular una planilla, mediante
 * `TIPO_CONTRATACION.APLICA_RENTA_FIJA` + `PORCENTAJE_RENTA_FIJA` (ver
 * `PayrollCalculatorService::calculatePayrollLine`), quedando almacenado en
 * `DETALLE_PLANILLA.RENTA_EMPLEADO` para cada empleado del grupo HONORARIOS.
 *
 * Este servicio solo consolida y exporta esa retención ya calculada; si aún no
 * existe una planilla calculada para el periodo, ofrece una estimación a partir
 * del salario mensual vigente del empleado.
 */
class Retencion10Service
{
    use BuildsDelimitedFile;
    use ListsClosedPlanillas;

    /**
     * Identifica tipos de contratación de servicios profesionales/independientes:
     * por bandera explícita (APLICA_RENTA_FIJA) o, como respaldo, por nombre.
     */
    protected function tiposProfesionalesQuery()
    {
        return DB::table('TIPO_CONTRATACION')
            ->where(function ($q) {
                $q->where('APLICA_RENTA_FIJA', true)
                    ->orWhere('TIPOCONTRATACION', 'ILIKE', '%profesional%')
                    ->orWhere('TIPOCONTRATACION', 'ILIKE', '%servicios%')
                    ->orWhere('TIPOCONTRATACION', 'ILIKE', '%honorario%');
            });
    }

    public function planillasParaSelect(?int $empresaId = null): Collection
    {
        $tipoIds = $this->tiposProfesionalesQuery()->pluck('ID_TIPOCONTRATACION');

        return $this->closedPlanillasQuery($empresaId)
            ->where(function ($q) use ($tipoIds) {
                $q->where('TIPO_PLANILLA.GRUPO_NOMINA', 'HONORARIOS')
                    ->orWhereIn('PLANILLA.ID_PLANILLA', function ($sub) use ($tipoIds) {
                        $sub->select('DETALLE_PLANILLA.ID_PLANILLA')
                            ->from('DETALLE_PLANILLA')
                            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
                            ->whereIn('EMPLEADO.ID_TIPOCONTRATACION', $tipoIds);
                    });
            })
            ->get();
    }

    /**
     * Estimación vigente (sin planilla): empleados activos bajo un tipo de
     * contratación de servicios profesionales, con su retención mensual teórica.
     */
    public function estimacionActual(?int $empresaId = null): array
    {
        $tipoIds = $this->tiposProfesionalesQuery()->pluck('ID_TIPOCONTRATACION');

        $query = DB::table('EMPLEADO')
            ->join('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->where('EMPLEADO.ESACTIVO', true)
            ->whereIn('EMPLEADO.ID_TIPOCONTRATACION', $tipoIds)
            ->select(
                'EMPLEADO.ID_EMPLEADO',
                'EMPLEADO.CODIGOEMPLEADO',
                'EMPLEADO.NOMBRES',
                'EMPLEADO.APELLIDO_1',
                'EMPLEADO.APELLIDO_2',
                'EMPLEADO.NIT',
                'EMPLEADO.SALARIOMENSUAL',
                'TIPO_CONTRATACION.TIPOCONTRATACION',
                'TIPO_CONTRATACION.PORCENTAJE_RENTA_FIJA'
            );

        if ($empresaId) {
            $query->where('EMPLEADO.ID_EMPRESA', $empresaId);
        }

        $filas = $query->get()->map(function ($row) {
            $porcentaje = (float) ($row->PORCENTAJE_RENTA_FIJA ?: 10.00);
            $retencion = round((float) $row->SALARIOMENSUAL * ($porcentaje / 100), 2);

            return [
                'CODIGOEMPLEADO' => $row->CODIGOEMPLEADO,
                'NOMBRE' => trim($row->NOMBRES . ' ' . $row->APELLIDO_1 . ' ' . ($row->APELLIDO_2 ?? '')),
                'NIT' => $row->NIT ?? '',
                'TIPO_CONTRATACION' => $row->TIPOCONTRATACION,
                'HONORARIO_MENSUAL' => (float) $row->SALARIOMENSUAL,
                'PORCENTAJE' => $porcentaje,
                'RETENCION_ESTIMADA' => $retencion,
            ];
        })->values()->all();

        return [
            'filas' => $filas,
            'totales' => [
                'retencion' => round(array_sum(array_column($filas, 'RETENCION_ESTIMADA')), 2),
                'count' => count($filas),
            ],
        ];
    }

    public function preview(int $planillaId): array
    {
        return $this->buildReportePlanilla($planillaId);
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function export(?int $planillaId = null, ?int $empresaId = null): array
    {
        if ($planillaId) {
            $reporte = $this->buildReportePlanilla($planillaId);
            $sufijo = 'planilla_' . $planillaId;
        } else {
            $reporte = $this->estimacionActual($empresaId);
            $sufijo = 'estimado';
        }

        if (empty($reporte['filas'])) {
            throw new RuntimeException('No hay empleados con retención del 10% para el filtro seleccionado.');
        }

        $headers = ['Código/NIT', 'Nombre completo', 'Tipo de contratación', 'Honorario / Salario', '% Retención', 'Retención del 10%'];
        $rows = array_map(fn ($fila) => [
            $fila['NIT'] ?? ($fila['CODIGOEMPLEADO'] ?? ''),
            $fila['NOMBRE'],
            $fila['TIPO_CONTRATACION'] ?? '',
            $this->formatMonto($fila['HONORARIO_MENSUAL'] ?? ($fila['DEVENGADO_GRAVADO'] ?? 0)),
            number_format($fila['PORCENTAJE'] ?? 10, 2),
            $this->formatMonto($fila['RETENCION_ESTIMADA'] ?? ($fila['RETENCION'] ?? 0)),
        ], $reporte['filas']);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'retencion10_' . $sufijo . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function buildReportePlanilla(int $planillaId): array
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new RuntimeException('Planilla no encontrada.');
        }
        if (!$planilla->CERRADA) {
            throw new RuntimeException('Solo se pueden reportar planillas cerradas.');
        }

        $detalles = DB::table('DETALLE_PLANILLA')
            ->join('EMPLEADO', 'DETALLE_PLANILLA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('DETALLE_PLANILLA.ID_PLANILLA', $planillaId)
            ->where('DETALLE_PLANILLA.APLICA_RENTA_FIJA', true)
            ->select(
                'DETALLE_PLANILLA.NOM_EMPLEADO',
                'DETALLE_PLANILLA.TIPO_CONTRATACION_NOM',
                'DETALLE_PLANILLA.DEVENGADO_GRAVADO',
                'DETALLE_PLANILLA.PORCENTAJE_RENTA_FIJA',
                'DETALLE_PLANILLA.RENTA_EMPLEADO',
                'EMPLEADO.NIT'
            )
            ->orderBy('DETALLE_PLANILLA.CORRELATIVO')
            ->get();

        $filas = $detalles->map(fn ($row) => [
            'NIT' => $row->NIT ?? '',
            'NOMBRE' => $row->NOM_EMPLEADO,
            'TIPO_CONTRATACION' => $row->TIPO_CONTRATACION_NOM,
            'DEVENGADO_GRAVADO' => (float) $row->DEVENGADO_GRAVADO,
            'PORCENTAJE' => (float) $row->PORCENTAJE_RENTA_FIJA,
            'RETENCION' => (float) $row->RENTA_EMPLEADO,
        ])->values()->all();

        return [
            'planilla' => $planilla,
            'filas' => $filas,
            'totales' => [
                'retencion' => round(array_sum(array_column($filas, 'RETENCION')), 2),
                'count' => count($filas),
            ],
        ];
    }
}
