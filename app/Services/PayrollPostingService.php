<?php

namespace App\Services;

use App\Models\Planilla;
use Illuminate\Support\Facades\DB;

class PayrollPostingService
{
    /**
     * Revierte abonos de préstamos vinculados a una planilla antes de recalcular.
     */
    public function reverseLoanPayments(int $planillaId): void
    {
        $detalleIds = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $planillaId)
            ->pluck('ID_DETALLEPLANILLA');

        if ($detalleIds->isEmpty()) {
            return;
        }

        $abonos = DB::table('PRESTAMO_ABONO')
            ->whereIn('ID_DETALLEPLANILLA', $detalleIds)
            ->get();

        foreach ($abonos as $abono) {
            DB::table('PRESTAMOS')
                ->where('ID_PRESTAMO', $abono->ID_PRESTAMO)
                ->increment('SALDO_ACTUAL', $abono->MONTOABONADO);

            DB::table('PRESTAMOS')
                ->where('ID_PRESTAMO', $abono->ID_PRESTAMO)
                ->update([
                    'PRESTAMOESTADO' => true,
                    'FECHAFINALIZACION' => null,
                ]);
        }

        DB::table('PRESTAMO_ABONO')
            ->whereIn('ID_DETALLEPLANILLA', $detalleIds)
            ->delete();
    }

    /**
     * Registra abonos de cuotas de préstamo y actualiza saldos.
     */
    public function postLoanPayments(int $empleadoId, int $detallePlanillaId, Planilla $planilla): void
    {
        $prestamos = DB::table('PRESTAMOS')
            ->where('ID_EMPLEADO', $empleadoId)
            ->where('PRESTAMOESTADO', true)
            ->where('SALDO_ACTUAL', '>', 0)
            ->get();

        $maxAbonoId = DB::table('PRESTAMO_ABONO')->max('ID_PRESTAMOABONO') ?? 0;
        $fechaAbono = $planilla->FECHAPAGO ?? now();

        foreach ($prestamos as $prestamo) {
            $cuota = min((float) $prestamo->CUOTA, (float) $prestamo->SALDO_ACTUAL);
            if ($cuota <= 0) {
                continue;
            }

            $maxAbonoId++;
            DB::table('PRESTAMO_ABONO')->insert([
                'ID_PRESTAMOABONO' => $maxAbonoId,
                'ID_PRESTAMO' => $prestamo->ID_PRESTAMO,
                'ID_DETALLEPLANILLA' => $detallePlanillaId,
                'FECHAABONO' => $fechaAbono,
                'MONTOABONADO' => $cuota,
                'CONCEPTO' => 'Cuota planilla #' . $planilla->ID_PLANILLA,
                'FUERA_PLANILLA' => false,
            ]);

            $nuevoSaldo = round((float) $prestamo->SALDO_ACTUAL - $cuota, 2);
            DB::table('PRESTAMOS')
                ->where('ID_PRESTAMO', $prestamo->ID_PRESTAMO)
                ->update([
                    'SALDO_ACTUAL' => max(0, $nuevoSaldo),
                    'PRESTAMOESTADO' => $nuevoSaldo > 0,
                    'FECHAFINALIZACION' => $nuevoSaldo <= 0 ? now() : null,
                ]);
        }
    }
}
