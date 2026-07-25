<?php

namespace App\Http\Controllers;

use App\Models\TipoDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamosController extends Controller
{
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

        return response()->json($query->get());
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
