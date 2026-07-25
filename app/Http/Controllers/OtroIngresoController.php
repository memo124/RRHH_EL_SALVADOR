<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtroIngresoController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('OTRO_INGRESO')
            ->join('EMPLEADO', 'OTRO_INGRESO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_INGRESO', 'OTRO_INGRESO.ID_TIPOINGRESO', '=', 'TIPO_INGRESO.ID_TIPOINGRESO')
            ->select(
                'OTRO_INGRESO.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'TIPO_INGRESO.TIPOINGRESO'
            )
            ->orderBy('OTRO_INGRESO.ID_OTROINGRESO', 'desc');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('OTRO_INGRESO.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        if ($request->boolean('solo_activos')) {
            $query->where('OTRO_INGRESO.ESACTIVO', true);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer|exists:EMPLEADO,ID_EMPLEADO',
            'ID_TIPOINGRESO' => 'required|integer|exists:TIPO_INGRESO,ID_TIPOINGRESO',
            'MONTOINGRESO' => 'required|numeric|min:0.01',
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'nullable|date|after_or_equal:FECHAINICIO',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = DB::table('OTRO_INGRESO')->max('ID_OTROINGRESO') ?? 0;

        DB::table('OTRO_INGRESO')->insert([
            'ID_OTROINGRESO' => $maxId + 1,
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'ID_TIPOINGRESO' => $request->ID_TIPOINGRESO,
            'MONTOINGRESO' => round((float) $request->MONTOINGRESO, 2),
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFIN' => $request->FECHAFIN,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_OTROINGRESO' => $maxId + 1, 'message' => 'Ingreso adicional registrado.'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ID_TIPOINGRESO' => 'required|integer|exists:TIPO_INGRESO,ID_TIPOINGRESO',
            'MONTOINGRESO' => 'required|numeric|min:0.01',
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'nullable|date',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('OTRO_INGRESO')->where('ID_OTROINGRESO', $id)->update([
            'ID_TIPOINGRESO' => $request->ID_TIPOINGRESO,
            'MONTOINGRESO' => round((float) $request->MONTOINGRESO, 2),
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFIN' => $request->FECHAFIN,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['message' => 'Ingreso adicional actualizado.']);
    }

    public function destroy($id)
    {
        DB::table('OTRO_INGRESO')->where('ID_OTROINGRESO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Ingreso adicional inactivado.']);
    }
}
