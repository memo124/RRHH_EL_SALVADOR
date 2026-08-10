<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstablecimientoController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('ESTABLECIMIENTO')
            ->leftJoin('EMPRESA', 'ESTABLECIMIENTO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('DISTRITO', 'ESTABLECIMIENTO.ID_DISTRITO', '=', 'DISTRITO.ID_DISTRITO')
            ->select(
                'ESTABLECIMIENTO.*',
                'EMPRESA.NOMBREEMPRESA as EMPRESA_NOMBRE',
                'DISTRITO.NOMBREDISTRITO as DISTRITO_NOMBRE'
            )
            ->orderBy('ESTABLECIMIENTO.ID_ESTABLECIMIENTO');

        if ($request->filled('ID_EMPRESA')) {
            $query->where('ESTABLECIMIENTO.ID_EMPRESA', $request->input('ID_EMPRESA'));
        }

        return $this->paginateQuery($query, $request, ['ESTABLECIMIENTO.NOMBRE_ESTABLECIMIENTO', 'ESTABLECIMIENTO.CODIGO_PUNTO_VENTA_MH', 'ESTABLECIMIENTO.DIRECCION']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer|exists:EMPRESA,ID_EMPRESA',
            'ID_DISTRITO' => 'nullable|integer|exists:DISTRITO,ID_DISTRITO',
            'CODIGO_MH_TIPO' => 'nullable|string|max:10',
            'CODIGO_PUNTO_VENTA_MH' => 'nullable|string|max:10',
            'NOMBRE_ESTABLECIMIENTO' => 'required|string|max:150',
            'DIRECCION' => 'nullable|string|max:250',
            'TELEFONO' => 'nullable|string|max:25',
        ]);

        $maxId = DB::table('ESTABLECIMIENTO')->max('ID_ESTABLECIMIENTO') ?? 0;
        $id = $maxId + 1;

        DB::table('ESTABLECIMIENTO')->insert([
            'ID_ESTABLECIMIENTO' => $id,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'ID_DISTRITO' => $request->ID_DISTRITO,
            'CODIGO_MH_TIPO' => $request->CODIGO_MH_TIPO ?? '01',
            'CODIGO_PUNTO_VENTA_MH' => $request->CODIGO_PUNTO_VENTA_MH,
            'NOMBRE_ESTABLECIMIENTO' => $request->NOMBRE_ESTABLECIMIENTO,
            'DIRECCION' => $request->DIRECCION,
            'TELEFONO' => $request->TELEFONO,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_ESTABLECIMIENTO' => $id, 'message' => 'Establecimiento creado correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer|exists:EMPRESA,ID_EMPRESA',
            'ID_DISTRITO' => 'nullable|integer|exists:DISTRITO,ID_DISTRITO',
            'CODIGO_MH_TIPO' => 'nullable|string|max:10',
            'CODIGO_PUNTO_VENTA_MH' => 'nullable|string|max:10',
            'NOMBRE_ESTABLECIMIENTO' => 'required|string|max:150',
            'DIRECCION' => 'nullable|string|max:250',
            'TELEFONO' => 'nullable|string|max:25',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('ESTABLECIMIENTO')
            ->where('ID_ESTABLECIMIENTO', $id)
            ->update([
                'ID_EMPRESA' => $request->ID_EMPRESA,
                'ID_DISTRITO' => $request->ID_DISTRITO,
                'CODIGO_MH_TIPO' => $request->CODIGO_MH_TIPO ?? '01',
                'CODIGO_PUNTO_VENTA_MH' => $request->CODIGO_PUNTO_VENTA_MH,
                'NOMBRE_ESTABLECIMIENTO' => $request->NOMBRE_ESTABLECIMIENTO,
                'DIRECCION' => $request->DIRECCION,
                'TELEFONO' => $request->TELEFONO,
                'ESACTIVO' => $request->has('ESACTIVO') ? $request->boolean('ESACTIVO') : true,
            ]);

        return response()->json(['message' => 'Establecimiento actualizado correctamente.']);
    }

    public function destroy($id)
    {
        DB::table('ESTABLECIMIENTO')->where('ID_ESTABLECIMIENTO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Establecimiento inactivado correctamente.']);
    }
}
