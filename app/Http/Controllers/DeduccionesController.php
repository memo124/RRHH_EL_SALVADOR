<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeduccionesController extends Controller
{
    use PaginatesQueries;

    // Descuentos
    public function indexDescuentos(Request $request)
    {
        $query = DB::table('TIPO_DESCUENTO')->orderBy('ID_TIPODESCUENTO');

        return $this->paginateQuery($query, $request, ['NOMBRETIPODESC', 'DESCRIPCIONTIPODESC', 'CATEGORIA']);
    }

    public function storeDescuento(Request $request)
    {
        $request->validate([
            'NOMBRETIPODESC' => 'required|string|max:100',
            'DESCRIPCIONTIPODESC' => 'nullable|string|max:200',
            'CATEGORIA' => 'required|string|in:LEY,PRESTAMO,DESCUENTO',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = DB::table('TIPO_DESCUENTO')->max('ID_TIPODESCUENTO') ?? 0;
        $id = $maxId + 1;

        DB::table('TIPO_DESCUENTO')->insert([
            'ID_TIPODESCUENTO' => $id,
            'NOMBRETIPODESC' => $request->NOMBRETIPODESC,
            'DESCRIPCIONTIPODESC' => $request->DESCRIPCIONTIPODESC,
            'CATEGORIA' => $request->CATEGORIA,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_TIPODESCUENTO' => $id, 'NOMBRETIPODESC' => $request->NOMBRETIPODESC], 201);
    }

    public function updateDescuento(Request $request, $id)
    {
        $request->validate([
            'NOMBRETIPODESC' => 'required|string|max:100',
            'DESCRIPCIONTIPODESC' => 'nullable|string|max:200',
            'CATEGORIA' => 'required|string|in:LEY,PRESTAMO,DESCUENTO',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('TIPO_DESCUENTO')
            ->where('ID_TIPODESCUENTO', $id)
            ->update([
                'NOMBRETIPODESC' => $request->NOMBRETIPODESC,
                'DESCRIPCIONTIPODESC' => $request->DESCRIPCIONTIPODESC,
                'CATEGORIA' => $request->CATEGORIA,
                'ESACTIVO' => $request->ESACTIVO ?? true,
            ]);

        return response()->json(['ID_TIPODESCUENTO' => $id, 'NOMBRETIPODESC' => $request->NOMBRETIPODESC]);
    }

    public function destroyDescuento($id)
    {
        DB::table('TIPO_DESCUENTO')
            ->where('ID_TIPODESCUENTO', $id)
            ->update(['ESACTIVO' => false]);

        return response()->json(['message' => 'Tipo de descuento inactivado correctamente.']);
    }

    // Ingresos
    public function indexIngresos(Request $request)
    {
        $query = DB::table('TIPO_INGRESO')->orderBy('ID_TIPOINGRESO');

        return $this->paginateQuery($query, $request, ['TIPOINGRESO']);
    }

    public function storeIngreso(Request $request)
    {
        $request->validate([
            'TIPOINGRESO' => 'required|string|max:150',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = DB::table('TIPO_INGRESO')->max('ID_TIPOINGRESO') ?? 0;
        $id = $maxId + 1;

        DB::table('TIPO_INGRESO')->insert([
            'ID_TIPOINGRESO' => $id,
            'TIPOINGRESO' => $request->TIPOINGRESO,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_TIPOINGRESO' => $id, 'TIPOINGRESO' => $request->TIPOINGRESO], 201);
    }

    public function updateIngreso(Request $request, $id)
    {
        $request->validate([
            'TIPOINGRESO' => 'required|string|max:150',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('TIPO_INGRESO')
            ->where('ID_TIPOINGRESO', $id)
            ->update([
                'TIPOINGRESO' => $request->TIPOINGRESO,
                'ESACTIVO' => $request->ESACTIVO ?? true,
            ]);

        return response()->json(['ID_TIPOINGRESO' => $id, 'TIPOINGRESO' => $request->TIPOINGRESO]);
    }

    public function destroyIngreso($id)
    {
        DB::table('TIPO_INGRESO')
            ->where('ID_TIPOINGRESO', $id)
            ->update(['ESACTIVO' => false]);

        return response()->json(['message' => 'Tipo de ingreso inactivado correctamente.']);
    }
}
