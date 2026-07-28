<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtroIngresoController extends Controller
{
    use PaginatesQueries;

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

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGOEMPLEADO', 'TIPOINGRESO']);
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

    public function historial($id)
    {
        $ingreso = DB::table('OTRO_INGRESO')
            ->join('TIPO_INGRESO', 'OTRO_INGRESO.ID_TIPOINGRESO', '=', 'TIPO_INGRESO.ID_TIPOINGRESO')
            ->where('OTRO_INGRESO.ID_OTROINGRESO', $id)
            ->select('OTRO_INGRESO.*', 'TIPO_INGRESO.TIPOINGRESO')
            ->first();

        if (!$ingreso) {
            return response()->json(['error' => 'Ingreso no encontrado.'], 404);
        }

        $query = DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $ingreso->ID_EMPLEADO)
            ->where('PLANILLA.FECHAPAGO', '>=', $ingreso->FECHAINICIO)
            ->where(function ($q) {
                $q->where('DETALLE_PLANILLA.PRODUCTIVIDAD', '>', 0)
                    ->orWhere('DETALLE_PLANILLA.COMISION', '>', 0)
                    ->orWhere('DETALLE_PLANILLA.OTROS_INGRESOS', '>', 0);
            })
            ->orderBy('PLANILLA.FECHAPAGO', 'desc')
            ->select(
                'PLANILLA.ID_PLANILLA',
                'PLANILLA.TITULO',
                'PLANILLA.FECHAPAGO',
                'DETALLE_PLANILLA.PRODUCTIVIDAD',
                'DETALLE_PLANILLA.COMISION',
                'DETALLE_PLANILLA.OTROS_INGRESOS'
            );

        if ($ingreso->FECHAFIN) {
            $query->where('PLANILLA.FECHAPAGO', '<=', $ingreso->FECHAFIN);
        }

        $aplicaciones = $query->get()->map(function ($row) use ($ingreso) {
            $monto = (float) ($row->PRODUCTIVIDAD ?? 0)
                + (float) ($row->COMISION ?? 0)
                + (float) ($row->OTROS_INGRESOS ?? 0);

            return [
                'ID_PLANILLA' => $row->ID_PLANILLA,
                'TITULO' => $row->TITULO,
                'FECHAPAGO' => $row->FECHAPAGO,
                'MONTO' => round($monto, 2),
                'MONTO_CONFIGURADO' => round((float) $ingreso->MONTOINGRESO, 2),
            ];
        });

        return response()->json([
            'ingreso' => $ingreso,
            'aplicaciones' => $aplicaciones,
            'resumen' => [
                'total_aplicado' => round((float) $aplicaciones->sum('MONTO'), 2),
                'veces_aplicado' => $aplicaciones->count(),
            ],
        ]);
    }

    public function destroy($id)
    {
        DB::table('OTRO_INGRESO')->where('ID_OTROINGRESO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Ingreso adicional inactivado.']);
    }
}
