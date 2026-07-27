<?php

namespace Database\Seeders\Concerns;

use App\Models\Empleado;
use App\Models\Planilla;
use App\Services\PayrollCalculatorService;
use App\Services\PayrollPostingService;
use Illuminate\Support\Facades\DB;

trait CalculatesDemoPayroll
{
    protected function calcularPlanillaDemo(int $planillaId, ?array $empleadoIds = null): void
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $calculator = app(PayrollCalculatorService::class);
        $posting = app(PayrollPostingService::class);

        $planilla = Planilla::with('periodoLaboral')->find($planillaId);
        if (!$planilla) {
            return;
        }

        DB::table('DETALLE_DESCUENTO_PLANILLA')
            ->whereIn('ID_DETALLEPLANILLA', function ($q) use ($planillaId) {
                $q->select('ID_DETALLEPLANILLA')->from('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId);
            })
            ->delete();
        DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId)->delete();

        $query = Empleado::with(['tipoContratacion', 'departamento.area', 'cargo', 'centroCosto'])
            ->where('ID_EMPRESA', $planilla->ID_EMPRESA)
            ->where('ESACTIVO', true);

        if ($empleadoIds !== null) {
            $query->whereIn('ID_EMPLEADO', $empleadoIds);
        }

        $empleados = $query->orderBy('ID_EMPLEADO')->get();

        $maxDetailId = DB::table('DETALLE_PLANILLA')->max('ID_DETALLEPLANILLA') ?? 0;
        $maxDescId = DB::table('DETALLE_DESCUENTO_PLANILLA')->max('ID_DETALLEDESCPLANILLA') ?? 0;
        $correlativo = 0;

        foreach ($empleados as $emp) {
            $correlativo++;
            $dias = $calculator->getDiasTrabajados($emp, $planilla);
            $line = $calculator->calculatePayrollLine($emp, $planilla, $dias);
            $descuentosDetalle = $line['DESCUENTOS_DETALLE'] ?? [];
            unset($line['DESCUENTOS_DETALLE']);

            $maxDetailId++;
            DB::table('DETALLE_PLANILLA')->insert(array_merge([
                'ID_DETALLEPLANILLA' => $maxDetailId,
                'ID_PLANILLA' => $planillaId,
                'ID_EMPLEADO' => $emp->ID_EMPLEADO,
                'CORRELATIVO' => $correlativo,
            ], $line));

            foreach ($descuentosDetalle as $desc) {
                $maxDescId++;
                DB::table('DETALLE_DESCUENTO_PLANILLA')->insert([
                    'ID_DETALLEDESCPLANILLA' => $maxDescId,
                    'ID_DETALLEPLANILLA' => $maxDetailId,
                    'ID_TIPODESCUENTO' => $desc['ID_TIPODESCUENTO'],
                    'CONCEPTO' => $desc['CONCEPTO'],
                    'CATEGORIA' => $desc['CATEGORIA'],
                    'MONTO' => $desc['MONTO'],
                ]);
            }

            if ($line['PRESTAMOS'] > 0) {
                $posting->postLoanPayments($emp->ID_EMPLEADO, $maxDetailId, $planilla);
            }
        }

        DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update(['RECALCULADA' => true]);
    }
}
