<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\TipoDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamosController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('PRESTAMOS')
            ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
            ->join('TIPO_DESCUENTO', 'PRESTAMOS.ID_TIPODESCUENTO', '=', 'TIPO_DESCUENTO.ID_TIPODESCUENTO')
            ->select(
                'PRESTAMOS.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'TIPO_PRESTAMO.NOMBREPRESTAMO',
                'TIPO_DESCUENTO.NOMBRETIPODESC'
            )
            ->orderBy('PRESTAMOS.ID_PRESTAMO', 'desc');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('PRESTAMOS.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        if ($request->boolean('solo_activos')) {
            $query->where('PRESTAMOS.PRESTAMOESTADO', true);
        }

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBREPRESTAMO', 'NOMBRETIPODESC']);
    }

    public function show($id)
    {
        $prestamo = DB::table('PRESTAMOS')
            ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
            ->join('TIPO_DESCUENTO', 'PRESTAMOS.ID_TIPODESCUENTO', '=', 'TIPO_DESCUENTO.ID_TIPODESCUENTO')
            ->where('PRESTAMOS.ID_PRESTAMO', $id)
            ->select(
                'PRESTAMOS.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'TIPO_PRESTAMO.NOMBREPRESTAMO',
                'TIPO_DESCUENTO.NOMBRETIPODESC'
            )
            ->first();

        if (!$prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado.'], 404);
        }

        $abonos = DB::table('PRESTAMO_ABONO')
            ->leftJoin('DETALLE_PLANILLA', 'PRESTAMO_ABONO.ID_DETALLEPLANILLA', '=', 'DETALLE_PLANILLA.ID_DETALLEPLANILLA')
            ->leftJoin('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->where('PRESTAMO_ABONO.ID_PRESTAMO', $id)
            ->orderBy('PRESTAMO_ABONO.FECHAABONO', 'desc')
            ->select(
                'PRESTAMO_ABONO.ID_PRESTAMOABONO',
                'PRESTAMO_ABONO.FECHAABONO',
                'PRESTAMO_ABONO.MONTOABONADO',
                'PRESTAMO_ABONO.CONCEPTO',
                'PRESTAMO_ABONO.FUERA_PLANILLA',
                'PLANILLA.ID_PLANILLA',
                'PLANILLA.TITULO AS TITULO_PLANILLA',
                'PLANILLA.FECHAPAGO'
            )
            ->get();

        $totalAbonado = round((float) $abonos->sum('MONTOABONADO'), 2);
        $resumen = $this->syncPrestamoSaldo((int) $id);

        return response()->json([
            'prestamo' => DB::table('PRESTAMOS')
                ->join('EMPLEADO', 'PRESTAMOS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
                ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
                ->where('PRESTAMOS.ID_PRESTAMO', $id)
                ->select(
                    'PRESTAMOS.*',
                    'EMPLEADO.CODIGOEMPLEADO',
                    DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                    'TIPO_PRESTAMO.NOMBREPRESTAMO'
                )
                ->first(),
            'abonos' => $abonos,
            'resumen' => $resumen,
        ]);
    }

    public function destroyAbono($prestamoId, $abonoId)
    {
        $abono = DB::table('PRESTAMO_ABONO')
            ->where('ID_PRESTAMOABONO', $abonoId)
            ->where('ID_PRESTAMO', $prestamoId)
            ->first();

        if (!$abono) {
            return response()->json(['error' => 'Abono no encontrado.'], 404);
        }

        DB::transaction(function () use ($abono) {
            DB::table('PRESTAMO_ABONO')->where('ID_PRESTAMOABONO', $abono->ID_PRESTAMOABONO)->delete();
            $this->syncPrestamoSaldo((int) $abono->ID_PRESTAMO, true);
        });

        $prestamo = DB::table('PRESTAMOS')->where('ID_PRESTAMO', $prestamoId)->first();

        return response()->json([
            'message' => 'Cuota/abono eliminado. Saldo y estado del préstamo actualizados.',
            'resumen' => $this->buildResumenFromPrestamo($prestamo),
        ]);
    }

    /**
     * Recalcula saldo y estado del préstamo según abonos registrados.
     */
    private function syncPrestamoSaldo(int $prestamoId, bool $reactivateIfSaldo = false): array
    {
        $prestamo = DB::table('PRESTAMOS')->where('ID_PRESTAMO', $prestamoId)->first();
        if (!$prestamo) {
            return [];
        }

        $totalAbonado = round((float) DB::table('PRESTAMO_ABONO')
            ->where('ID_PRESTAMO', $prestamoId)
            ->sum('MONTOABONADO'), 2);

        $saldo = max(0, round((float) $prestamo->MONTOPRESTAMO - $totalAbonado, 2));
        $cuotasPagadas = (int) DB::table('PRESTAMO_ABONO')->where('ID_PRESTAMO', $prestamoId)->count();

        $updates = ['SALDO_ACTUAL' => $saldo];

        if ($saldo <= 0) {
            $updates['PRESTAMOESTADO'] = false;
            $updates['FECHAFINALIZACION'] = $prestamo->FECHAFINALIZACION ?? now();
        } elseif ($reactivateIfSaldo) {
            $updates['PRESTAMOESTADO'] = true;
            $updates['FECHAFINALIZACION'] = null;
        }

        DB::table('PRESTAMOS')->where('ID_PRESTAMO', $prestamoId)->update($updates);

        return [
            'total_abonado' => $totalAbonado,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_pendientes' => max(0, (int) $prestamo->NUMCUOTAS - $cuotasPagadas),
            'saldo_actual' => $saldo,
        ];
    }

    private function buildResumenFromPrestamo($prestamo): array
    {
        if (!$prestamo) {
            return [];
        }

        $totalAbonado = round((float) DB::table('PRESTAMO_ABONO')
            ->where('ID_PRESTAMO', $prestamo->ID_PRESTAMO)
            ->sum('MONTOABONADO'), 2);
        $cuotasPagadas = (int) DB::table('PRESTAMO_ABONO')->where('ID_PRESTAMO', $prestamo->ID_PRESTAMO)->count();

        return [
            'total_abonado' => $totalAbonado,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_pendientes' => max(0, (int) $prestamo->NUMCUOTAS - $cuotasPagadas),
            'saldo_actual' => round((float) $prestamo->SALDO_ACTUAL, 2),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer|exists:EMPLEADO,ID_EMPLEADO',
            'ID_TIPODESCUENTO' => 'required|integer|exists:TIPO_DESCUENTO,ID_TIPODESCUENTO',
            'ID_TIPOPRESTAMO' => 'required|integer|exists:TIPO_PRESTAMO,ID_TIPOPRESTAMO',
            'MONTOPRESTAMO' => 'required|numeric|min:0.01',
            'NUMCUOTAS' => 'required|integer|min:1',
            'FECHAINICIO' => 'required|date',
            'OBSERVACIONES' => 'nullable|string|max:500',
        ]);

        $tipoDescuento = DB::table('TIPO_DESCUENTO')->where('ID_TIPODESCUENTO', $request->ID_TIPODESCUENTO)->first();
        if (!$tipoDescuento || $tipoDescuento->CATEGORIA !== TipoDescuento::CATEGORIA_PRESTAMO) {
            return response()->json(['message' => 'El tipo de descuento seleccionado no corresponde a un préstamo.'], 422);
        }

        $monto = round((float) $request->MONTOPRESTAMO, 2);
        $numCuotas = (int) $request->NUMCUOTAS;
        $cuota = round($monto / $numCuotas, 2);

        $maxId = DB::table('PRESTAMOS')->max('ID_PRESTAMO') ?? 0;

        DB::table('PRESTAMOS')->insert([
            'ID_PRESTAMO' => $maxId + 1,
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'ID_TIPODESCUENTO' => $request->ID_TIPODESCUENTO,
            'ID_TIPOPRESTAMO' => $request->ID_TIPOPRESTAMO,
            'MONTOPRESTAMO' => $monto,
            'CUOTA' => $cuota,
            'NUMCUOTAS' => $numCuotas,
            'SALDO_ACTUAL' => $monto,
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFINALIZACION' => null,
            'PRESTAMOESTADO' => true,
            'OBSERVACIONES' => $request->OBSERVACIONES,
        ]);

        return response()->json(['ID_PRESTAMO' => $maxId + 1, 'message' => 'Préstamo registrado correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        $prestamo = DB::table('PRESTAMOS')->where('ID_PRESTAMO', $id)->first();
        if (!$prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado.'], 404);
        }

        $request->validate([
            'CUOTA' => 'nullable|numeric|min:0.01',
            'OBSERVACIONES' => 'nullable|string|max:500',
            'PRESTAMOESTADO' => 'nullable|boolean',
        ]);

        $updates = [];
        if ($request->has('CUOTA')) {
            $updates['CUOTA'] = round((float) $request->CUOTA, 2);
        }
        if ($request->has('OBSERVACIONES')) {
            $updates['OBSERVACIONES'] = $request->OBSERVACIONES;
        }
        if ($request->has('PRESTAMOESTADO')) {
            $updates['PRESTAMOESTADO'] = $request->PRESTAMOESTADO;
            if (!$request->PRESTAMOESTADO) {
                $updates['FECHAFINALIZACION'] = now();
            }
        }

        if (!empty($updates)) {
            DB::table('PRESTAMOS')->where('ID_PRESTAMO', $id)->update($updates);
        }

        return response()->json(['message' => 'Préstamo actualizado correctamente.']);
    }

    public function destroy($id)
    {
        $prestamo = DB::table('PRESTAMOS')->where('ID_PRESTAMO', $id)->first();
        if (!$prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado.'], 404);
        }

        DB::table('PRESTAMOS')->where('ID_PRESTAMO', $id)->update([
            'PRESTAMOESTADO' => false,
            'FECHAFINALIZACION' => now(),
        ]);

        return response()->json(['message' => 'Préstamo cancelado correctamente.']);
    }
}
