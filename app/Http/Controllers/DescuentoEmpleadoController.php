<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\TipoDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DescuentoEmpleadoController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('DESCUENTO_EMPLEADO')
            ->join('EMPLEADO', 'DESCUENTO_EMPLEADO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_DESCUENTO', 'DESCUENTO_EMPLEADO.ID_TIPODESCUENTO', '=', 'TIPO_DESCUENTO.ID_TIPODESCUENTO')
            ->select(
                'DESCUENTO_EMPLEADO.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'TIPO_DESCUENTO.NOMBRETIPODESC'
            )
            ->orderBy('DESCUENTO_EMPLEADO.ID_DESCUENTOEMPLEADO', 'desc');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('DESCUENTO_EMPLEADO.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        if ($request->boolean('solo_activos')) {
            $query->where('DESCUENTO_EMPLEADO.ESACTIVO', true);
        }

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRETIPODESC']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer|exists:EMPLEADO,ID_EMPLEADO',
            'ID_TIPODESCUENTO' => 'required|integer|exists:TIPO_DESCUENTO,ID_TIPODESCUENTO',
            'MONTO' => 'required_without:ES_PORCENTAJE|nullable|numeric|min:0',
            'PORCENTAJE' => 'required_if:ES_PORCENTAJE,true|nullable|numeric|min:0|max:100',
            'ES_PORCENTAJE' => 'boolean',
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'nullable|date|after_or_equal:FECHAINICIO',
            'ES_RECURRENTE' => 'boolean',
            'OBSERVACIONES' => 'nullable|string|max:250',
        ]);

        $tipoDescuento = DB::table('TIPO_DESCUENTO')->where('ID_TIPODESCUENTO', $request->ID_TIPODESCUENTO)->first();
        if (!$tipoDescuento || $tipoDescuento->CATEGORIA !== TipoDescuento::CATEGORIA_DESCUENTO) {
            return response()->json(['message' => 'El tipo seleccionado no corresponde a un descuento voluntario.'], 422);
        }

        $maxId = DB::table('DESCUENTO_EMPLEADO')->max('ID_DESCUENTOEMPLEADO') ?? 0;

        DB::table('DESCUENTO_EMPLEADO')->insert([
            'ID_DESCUENTOEMPLEADO' => $maxId + 1,
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'ID_TIPODESCUENTO' => $request->ID_TIPODESCUENTO,
            'MONTO' => round((float) ($request->MONTO ?? 0), 2),
            'PORCENTAJE' => $request->PORCENTAJE,
            'ES_PORCENTAJE' => $request->ES_PORCENTAJE ?? false,
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFIN' => $request->FECHAFIN,
            'ESACTIVO' => true,
            'ES_RECURRENTE' => $request->ES_RECURRENTE ?? true,
            'OBSERVACIONES' => $request->OBSERVACIONES,
        ]);

        return response()->json(['ID_DESCUENTOEMPLEADO' => $maxId + 1, 'message' => 'Descuento registrado correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ID_TIPODESCUENTO' => 'required|integer|exists:TIPO_DESCUENTO,ID_TIPODESCUENTO',
            'MONTO' => 'nullable|numeric|min:0',
            'PORCENTAJE' => 'nullable|numeric|min:0|max:100',
            'ES_PORCENTAJE' => 'boolean',
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'nullable|date',
            'ES_RECURRENTE' => 'boolean',
            'ESACTIVO' => 'boolean',
            'OBSERVACIONES' => 'nullable|string|max:250',
        ]);

        $tipoDescuento = DB::table('TIPO_DESCUENTO')->where('ID_TIPODESCUENTO', $request->ID_TIPODESCUENTO)->first();
        if (!$tipoDescuento || $tipoDescuento->CATEGORIA !== TipoDescuento::CATEGORIA_DESCUENTO) {
            return response()->json(['message' => 'El tipo seleccionado no corresponde a un descuento voluntario.'], 422);
        }

        DB::table('DESCUENTO_EMPLEADO')->where('ID_DESCUENTOEMPLEADO', $id)->update([
            'ID_TIPODESCUENTO' => $request->ID_TIPODESCUENTO,
            'MONTO' => round((float) ($request->MONTO ?? 0), 2),
            'PORCENTAJE' => $request->PORCENTAJE,
            'ES_PORCENTAJE' => $request->ES_PORCENTAJE ?? false,
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFIN' => $request->FECHAFIN,
            'ES_RECURRENTE' => $request->ES_RECURRENTE ?? true,
            'ESACTIVO' => $request->ESACTIVO ?? true,
            'OBSERVACIONES' => $request->OBSERVACIONES,
        ]);

        return response()->json(['message' => 'Descuento actualizado correctamente.']);
    }

    public function historial($id)
    {
        $descuento = DB::table('DESCUENTO_EMPLEADO')->where('ID_DESCUENTOEMPLEADO', $id)->first();
        if (!$descuento) {
            return response()->json(['error' => 'Descuento no encontrado.'], 404);
        }

        $aplicaciones = DB::table('DETALLE_DESCUENTO_PLANILLA')
            ->join('DETALLE_PLANILLA', 'DETALLE_DESCUENTO_PLANILLA.ID_DETALLEPLANILLA', '=', 'DETALLE_PLANILLA.ID_DETALLEPLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $descuento->ID_EMPLEADO)
            ->where('DETALLE_DESCUENTO_PLANILLA.ID_TIPODESCUENTO', $descuento->ID_TIPODESCUENTO)
            ->where('DETALLE_DESCUENTO_PLANILLA.CATEGORIA', 'DESCUENTO')
            ->orderBy('PLANILLA.FECHAPAGO', 'desc')
            ->select(
                'DETALLE_DESCUENTO_PLANILLA.CONCEPTO',
                'DETALLE_DESCUENTO_PLANILLA.MONTO',
                'PLANILLA.ID_PLANILLA',
                'PLANILLA.TITULO',
                'PLANILLA.FECHAPAGO'
            )
            ->get();

        return response()->json([
            'descuento' => $descuento,
            'aplicaciones' => $aplicaciones,
            'resumen' => [
                'total_aplicado' => round((float) $aplicaciones->sum('MONTO'), 2),
                'veces_aplicado' => $aplicaciones->count(),
            ],
        ]);
    }

    public function destroy($id)
    {
        DB::table('DESCUENTO_EMPLEADO')->where('ID_DESCUENTOEMPLEADO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Descuento inactivado correctamente.']);
    }
}
