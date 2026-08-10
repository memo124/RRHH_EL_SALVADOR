<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeografiaController extends Controller
{
    use PaginatesQueries;

    // —— País ——

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

        $id = (DB::table('PAIS')->max('ID_PAIS') ?? 0) + 1;
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

        DB::table('PAIS')->where('ID_PAIS', $id)->update([
            'NOMBREPAIS' => $request->NOMBREPAIS,
            'CODIGO_MH' => $request->CODIGO_MH,
        ]);

        return response()->json(['ID_PAIS' => $id, 'NOMBREPAIS' => $request->NOMBREPAIS, 'CODIGO_MH' => $request->CODIGO_MH]);
    }

    public function destroy($id)
    {
        if (DB::table('DEPARTAMENTO_PAIS')->where('ID_PAIS', $id)->exists()) {
            return response()->json(['error' => 'No se puede eliminar el país porque tiene departamentos asociados.'], 400);
        }
        DB::table('PAIS')->where('ID_PAIS', $id)->delete();
        return response()->json(['message' => 'País eliminado correctamente.']);
    }

    // —— Departamento país ——

    public function indexDepartamentos(Request $request)
    {
        $query = DB::table('DEPARTAMENTO_PAIS as DP')
            ->leftJoin('PAIS as P', 'DP.ID_PAIS', '=', 'P.ID_PAIS')
            ->select('DP.*', 'P.NOMBREPAIS')
            ->orderBy('DP.NOMBREDEPARTAMENTO');

        if ($request->filled('ID_PAIS')) {
            $query->where('DP.ID_PAIS', $request->ID_PAIS);
        }

        return $this->paginateQuery($query, $request, ['DP.NOMBREDEPARTAMENTO', 'DP.CODIGO_MH']);
    }

    public function storeDepartamento(Request $request)
    {
        $request->validate([
            'ID_PAIS' => 'required|integer',
            'NOMBREDEPARTAMENTO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        $id = (DB::table('DEPARTAMENTO_PAIS')->max('ID_DEPARTAMENTOPAIS') ?? 0) + 1;
        DB::table('DEPARTAMENTO_PAIS')->insert([
            'ID_DEPARTAMENTOPAIS' => $id,
            'ID_PAIS' => $request->ID_PAIS,
            'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['ID_DEPARTAMENTOPAIS' => $id, 'message' => 'Departamento creado.'], 201);
    }

    public function updateDepartamento(Request $request, $id)
    {
        $request->validate([
            'ID_PAIS' => 'required|integer',
            'NOMBREDEPARTAMENTO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        DB::table('DEPARTAMENTO_PAIS')->where('ID_DEPARTAMENTOPAIS', $id)->update([
            'ID_PAIS' => $request->ID_PAIS,
            'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['message' => 'Departamento actualizado.']);
    }

    public function destroyDepartamento($id)
    {
        if (DB::table('MUNICIPIO')->where('ID_DEPARTAMENTOPAIS', $id)->exists()) {
            return response()->json(['error' => 'No se puede eliminar: tiene municipios asociados.'], 400);
        }
        DB::table('DEPARTAMENTO_PAIS')->where('ID_DEPARTAMENTOPAIS', $id)->delete();
        return response()->json(['message' => 'Departamento eliminado.']);
    }

    // —— Municipio ——

    public function indexMunicipios(Request $request)
    {
        $query = DB::table('MUNICIPIO as M')
            ->leftJoin('DEPARTAMENTO_PAIS as DP', 'M.ID_DEPARTAMENTOPAIS', '=', 'DP.ID_DEPARTAMENTOPAIS')
            ->select('M.*', 'DP.NOMBREDEPARTAMENTO', 'DP.ID_PAIS')
            ->orderBy('M.NOMBREMUNICIPIO');

        if ($request->filled('ID_DEPARTAMENTOPAIS')) {
            $query->where('M.ID_DEPARTAMENTOPAIS', $request->ID_DEPARTAMENTOPAIS);
        } elseif ($request->filled('ID_PAIS')) {
            $query->where('DP.ID_PAIS', $request->ID_PAIS);
        }

        return $this->paginateQuery($query, $request, ['M.NOMBREMUNICIPIO', 'M.CODIGO_MH']);
    }

    public function storeMunicipio(Request $request)
    {
        $request->validate([
            'ID_DEPARTAMENTOPAIS' => 'required|integer',
            'NOMBREMUNICIPIO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        $id = (DB::table('MUNICIPIO')->max('ID_MUNICIPIO') ?? 0) + 1;
        DB::table('MUNICIPIO')->insert([
            'ID_MUNICIPIO' => $id,
            'ID_DEPARTAMENTOPAIS' => $request->ID_DEPARTAMENTOPAIS,
            'NOMBREMUNICIPIO' => $request->NOMBREMUNICIPIO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['ID_MUNICIPIO' => $id, 'message' => 'Municipio creado.'], 201);
    }

    public function updateMunicipio(Request $request, $id)
    {
        $request->validate([
            'ID_DEPARTAMENTOPAIS' => 'required|integer',
            'NOMBREMUNICIPIO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        DB::table('MUNICIPIO')->where('ID_MUNICIPIO', $id)->update([
            'ID_DEPARTAMENTOPAIS' => $request->ID_DEPARTAMENTOPAIS,
            'NOMBREMUNICIPIO' => $request->NOMBREMUNICIPIO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['message' => 'Municipio actualizado.']);
    }

    public function destroyMunicipio($id)
    {
        if (DB::table('DISTRITO')->where('ID_MUNICIPIO', $id)->exists()) {
            return response()->json(['error' => 'No se puede eliminar: tiene distritos asociados.'], 400);
        }
        DB::table('MUNICIPIO')->where('ID_MUNICIPIO', $id)->delete();
        return response()->json(['message' => 'Municipio eliminado.']);
    }

    // —— Distrito ——

    public function indexDistritos(Request $request)
    {
        $query = DB::table('DISTRITO as D')
            ->leftJoin('MUNICIPIO as M', 'D.ID_MUNICIPIO', '=', 'M.ID_MUNICIPIO')
            ->leftJoin('DEPARTAMENTO_PAIS as DP', 'M.ID_DEPARTAMENTOPAIS', '=', 'DP.ID_DEPARTAMENTOPAIS')
            ->select('D.*', 'M.NOMBREMUNICIPIO', 'M.ID_DEPARTAMENTOPAIS', 'DP.ID_PAIS')
            ->orderBy('D.NOMBREDISTRITO');

        if ($request->filled('ID_MUNICIPIO')) {
            $query->where('D.ID_MUNICIPIO', $request->ID_MUNICIPIO);
        } elseif ($request->filled('ID_DEPARTAMENTOPAIS')) {
            $query->where('M.ID_DEPARTAMENTOPAIS', $request->ID_DEPARTAMENTOPAIS);
        }

        return $this->paginateQuery($query, $request, ['D.NOMBREDISTRITO', 'D.CODIGO_MH']);
    }

    public function storeDistrito(Request $request)
    {
        $request->validate([
            'ID_MUNICIPIO' => 'required|integer',
            'NOMBREDISTRITO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        $id = (DB::table('DISTRITO')->max('ID_DISTRITO') ?? 0) + 1;
        DB::table('DISTRITO')->insert([
            'ID_DISTRITO' => $id,
            'ID_MUNICIPIO' => $request->ID_MUNICIPIO,
            'NOMBREDISTRITO' => $request->NOMBREDISTRITO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['ID_DISTRITO' => $id, 'message' => 'Distrito creado.'], 201);
    }

    public function updateDistrito(Request $request, $id)
    {
        $request->validate([
            'ID_MUNICIPIO' => 'required|integer',
            'NOMBREDISTRITO' => 'required|string|max:100',
            'CODIGO_MH' => 'nullable|string|max:10',
        ]);

        DB::table('DISTRITO')->where('ID_DISTRITO', $id)->update([
            'ID_MUNICIPIO' => $request->ID_MUNICIPIO,
            'NOMBREDISTRITO' => $request->NOMBREDISTRITO,
            'CODIGO_MH' => $request->CODIGO_MH ?? '01',
        ]);

        return response()->json(['message' => 'Distrito actualizado.']);
    }

    public function destroyDistrito($id)
    {
        if (DB::table('EMPLEADO')->where('ID_DISTRITO', $id)->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay empleados con este distrito.'], 400);
        }
        if (DB::table('ESTABLECIMIENTO')->where('ID_DISTRITO', $id)->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay establecimientos vinculados.'], 400);
        }
        DB::table('DISTRITO')->where('ID_DISTRITO', $id)->delete();
        return response()->json(['message' => 'Distrito eliminado.']);
    }
}
