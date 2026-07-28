<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeografiaController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('PAIS')->orderBy('ID_PAIS');

        return $this->paginateQuery($query, $request, ['NOMBREPAIS', 'CODIGO_MH']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'NOMBREPAIS' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        $maxId = DB::table('PAIS')->max('ID_PAIS') ?? 0;
        $id = $maxId + 1;

        DB::table('PAIS')->insert([
            'ID_PAIS' => $id,
            'NOMBREPAIS' => $request->NOMBREPAIS,
            'CODIGO_MH' => $request->CODIGO_MH,
        ]);

        return response()->json(['ID_PAIS' => $id, 'NOMBREPAIS' => $request->NOMBREPAIS, 'CODIGO_MH' => $request->CODIGO_MH], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'NOMBREPAIS' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        DB::table('PAIS')
            ->where('ID_PAIS', $id)
            ->update([
                'NOMBREPAIS' => $request->NOMBREPAIS,
                'CODIGO_MH' => $request->CODIGO_MH,
            ]);

        return response()->json(['ID_PAIS' => $id, 'NOMBREPAIS' => $request->NOMBREPAIS, 'CODIGO_MH' => $request->CODIGO_MH]);
    }

    public function destroy($id)
    {
        // Check relationships to preserve integrity
        $hasDeps = DB::table('DEPARTAMENTO_PAIS')->where('ID_PAIS', $id)->exists();
        if ($hasDeps) {
            return response()->json(['error' => 'No se puede eliminar el país porque tiene departamentos asociados.'], 400);
        }

        DB::table('PAIS')->where('ID_PAIS', $id)->delete();
        return response()->json(['message' => 'País eliminado correctamente.']);
    }
}
