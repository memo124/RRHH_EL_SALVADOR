<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActividadEconomicaController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('ACTIVIDAD_ECONOMICA')->orderBy('CODIGO_MH');

        if ($request->boolean('solo_activos', true)) {
            $query->where('ESACTIVO', true);
        }

        return $this->paginateQuery($query, $request, ['CODIGO_MH', 'DESCRIPCION']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'CODIGO_MH' => 'required|string|max:10|unique:ACTIVIDAD_ECONOMICA,CODIGO_MH',
            'DESCRIPCION' => 'required|string|max:500',
        ]);

        $maxId = DB::table('ACTIVIDAD_ECONOMICA')->max('ID_ACTIVIDAD_ECONOMICA') ?? 0;

        DB::table('ACTIVIDAD_ECONOMICA')->insert([
            'ID_ACTIVIDAD_ECONOMICA' => $maxId + 1,
            'CODIGO_MH' => $request->CODIGO_MH,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_ACTIVIDAD_ECONOMICA' => $maxId + 1, 'message' => 'Actividad económica creada.'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'CODIGO_MH' => 'required|string|max:10|unique:ACTIVIDAD_ECONOMICA,CODIGO_MH,' . $id . ',ID_ACTIVIDAD_ECONOMICA',
            'DESCRIPCION' => 'required|string|max:500',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('ACTIVIDAD_ECONOMICA')->where('ID_ACTIVIDAD_ECONOMICA', $id)->update([
            'CODIGO_MH' => $request->CODIGO_MH,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ESACTIVO' => $request->has('ESACTIVO') ? $request->boolean('ESACTIVO') : true,
        ]);

        return response()->json(['message' => 'Actividad económica actualizada.']);
    }

    public function destroy($id)
    {
        DB::table('ACTIVIDAD_ECONOMICA')->where('ID_ACTIVIDAD_ECONOMICA', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Actividad económica inactivada.']);
    }
}
