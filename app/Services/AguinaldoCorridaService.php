<?php

namespace App\Services;

use App\Models\Empleado;
use App\Services\Concerns\BuildsDelimitedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Corrida de aguinaldo: previsualiza y exporta el cálculo de aguinaldo de fin de
 * año para todos los empleados activos de una empresa cuyo tipo de contratación
 * aplica aguinaldo (`TIPO_CONTRATACION.APLICA_AGUINALDO`).
 *
 * Decisión de integración: este servicio solo previsualiza/exporta y crea el
 * encabezado de la planilla tipo "Aguinaldo" (`TIPO_PLANILLA` = 3). El cálculo de
 * detalle reutiliza el flujo existente `PlanillaController::calculate()`, que ya
 * detecta planillas tipo Aguinaldo y usa `PayrollCalculatorService::calculateAguinaldoLine()`
 * — evita duplicar esa lógica transaccional ya probada.
 */
class AguinaldoCorridaService
{
    use BuildsDelimitedFile;

    public function __construct(protected AguinaldoCalculatorService $calculator)
    {
    }

    public function preview(int $empresaId, string $fechaCorte): array
    {
        $empleados = Empleado::with(['tipoContratacion', 'departamento', 'cargo'])
            ->where('ID_EMPRESA', $empresaId)
            ->where('ESACTIVO', true)
            ->whereHas('tipoContratacion', fn ($q) => $q->where('APLICA_AGUINALDO', true))
            ->orderBy('NOMBRES')
            ->get();

        $filas = $empleados->map(function (Empleado $empleado) {
            $calculo = $this->calculator->calcularMontoAguinaldo($empleado);

            return [
                'ID_EMPLEADO' => $empleado->ID_EMPLEADO,
                'CODIGOEMPLEADO' => $empleado->CODIGOEMPLEADO,
                'NOMBRE' => trim($empleado->NOMBRES . ' ' . $empleado->APELLIDO_1 . ' ' . ($empleado->APELLIDO_2 ?? '')),
                'FECHAINGRESO' => $empleado->FECHAINGRESO?->format('Y-m-d'),
                'DIAS_AGUINALDO' => $calculo['DIAS_AGUINALDO'],
                'SALARIO_BASE' => $calculo['SALARIO_BASE'],
                'MONTO_AGUINALDO' => $calculo['MONTO_AGUINALDO'],
            ];
        })->values()->all();

        return [
            'fecha_corte' => $fechaCorte,
            'filas' => $filas,
            'totales' => [
                'monto' => round(array_sum(array_column($filas, 'MONTO_AGUINALDO')), 2),
                'count' => count($filas),
            ],
        ];
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function export(int $empresaId, string $fechaCorte): array
    {
        $reporte = $this->preview($empresaId, $fechaCorte);
        if (empty($reporte['filas'])) {
            throw new RuntimeException('No hay empleados con derecho a aguinaldo para esta empresa.');
        }

        $headers = ['Código', 'Nombre completo', 'Fecha ingreso', 'Días aguinaldo', 'Salario base', 'Monto aguinaldo'];
        $rows = array_map(fn ($fila) => [
            $fila['CODIGOEMPLEADO'],
            $fila['NOMBRE'],
            $fila['FECHAINGRESO'],
            $fila['DIAS_AGUINALDO'],
            $this->formatMonto($fila['SALARIO_BASE']),
            $this->formatMonto($fila['MONTO_AGUINALDO']),
        ], $reporte['filas']);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'aguinaldo_' . $empresaId . '_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    /**
     * Crea únicamente el encabezado de la planilla tipo Aguinaldo. El detalle se
     * calcula luego con `POST /api/planillas/{id}/calcular` (flujo estándar).
     */
    public function crearPlanilla(array $data): int
    {
        $tipoAguinaldo = DB::table('TIPO_PLANILLA')->where('TIPOPLANILLA', 'Aguinaldo')->first()
            ?? DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', 3)->first();

        if (!$tipoAguinaldo) {
            throw new RuntimeException('No existe el tipo de planilla "Aguinaldo" en el catálogo TIPO_PLANILLA.');
        }

        $maxId = DB::table('PLANILLA')->max('ID_PLANILLA') ?? 0;
        $id = $maxId + 1;

        DB::table('PLANILLA')->insert([
            'ID_PLANILLA' => $id,
            'ID_EMPRESA' => $data['ID_EMPRESA'],
            'ID_TIPOPLANILLA' => $tipoAguinaldo->ID_TIPOPLANILLA,
            'ID_PERIODO' => $data['ID_PERIODO'],
            'ID_FRECUENCIAPAGO' => $data['ID_FRECUENCIAPAGO'],
            'ID_CUENTA' => $data['ID_CUENTA'],
            'TITULO' => $data['TITULO'],
            'FECHAPAGO' => $data['FECHAPAGO'],
            'FORMAPAGO' => $data['FORMAPAGO'],
            'OBSERVACION' => $data['OBSERVACION'] ?? 'Corrida de aguinaldo generada desde Cumplimiento SV.',
            'ESACTIVA' => true,
            'CERRADA' => false,
            'ANULADA' => false,
            'CONTABILIZADA' => false,
        ]);

        return $id;
    }
}
