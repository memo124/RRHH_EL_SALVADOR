<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametrosAguinaldosController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('PARAMETROS_AGUINALDOS')
            ->join('EMPRESA', 'PARAMETROS_AGUINALDOS.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('PARAMETROS_AGUINALDOS.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('PARAMETROS_AGUINALDOS.ID_EMPRESA')
            ->orderBy('PARAMETROS_AGUINALDOS.DESDE_ANOS');

        if ($request->filled('ID_EMPRESA')) {
            $query->where('PARAMETROS_AGUINALDOS.ID_EMPRESA', $request->ID_EMPRESA);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'DESDE_ANOS' => 'required|integer|min:0',
            'HASTA_ANOS' => 'required|integer|gte:DESDE_ANOS',
            'NUMERO_DIAS' => 'required|integer|min:1',
        ]);

        $maxId = DB::table('PARAMETROS_AGUINALDOS')->max('ID_PARAMETRO_AGUINALDO') ?? 0;

        DB::table('PARAMETROS_AGUINALDOS')->insert([
            'ID_PARAMETRO_AGUINALDO' => $maxId + 1,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'DESDE_ANOS' => $request->DESDE_ANOS,
            'HASTA_ANOS' => $request->HASTA_ANOS,
            'NUMERO_DIAS' => $request->NUMERO_DIAS,
            'SOBRE_EXCEDENTE' => $request->SOBRE_EXCEDENTE ?? 0,
        ]);

        return response()->json(['ID_PARAMETRO_AGUINALDO' => $maxId + 1], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'DESDE_ANOS' => 'required|integer|min:0',
            'HASTA_ANOS' => 'required|integer',
            'NUMERO_DIAS' => 'required|integer|min:1',
        ]);

        DB::table('PARAMETROS_AGUINALDOS')->where('ID_PARAMETRO_AGUINALDO', $id)->update([
            'DESDE_ANOS' => $request->DESDE_ANOS,
            'HASTA_ANOS' => $request->HASTA_ANOS,
            'NUMERO_DIAS' => $request->NUMERO_DIAS,
            'SOBRE_EXCEDENTE' => $request->SOBRE_EXCEDENTE ?? 0,
        ]);

        return response()->json(['message' => 'Parámetro actualizado.']);
    }

    public function destroy($id)
    {
        DB::table('PARAMETROS_AGUINALDOS')->where('ID_PARAMETRO_AGUINALDO', $id)->delete();
        return response()->json(['message' => 'Parámetro eliminado.']);
    }

    public function seedDefault($empresaId)
    {
        $defaults = [
            ['DESDE_ANOS' => 0, 'HASTA_ANOS' => 0, 'NUMERO_DIAS' => 15],
            ['DESDE_ANOS' => 1, 'HASTA_ANOS' => 2, 'NUMERO_DIAS' => 19],
            ['DESDE_ANOS' => 3, 'HASTA_ANOS' => 9, 'NUMERO_DIAS' => 21],
            ['DESDE_ANOS' => 10, 'HASTA_ANOS' => 99, 'NUMERO_DIAS' => 30],
        ];

        $maxId = DB::table('PARAMETROS_AGUINALDOS')->max('ID_PARAMETRO_AGUINALDO') ?? 0;
        foreach ($defaults as $d) {
            $exists = DB::table('PARAMETROS_AGUINALDOS')
                ->where('ID_EMPRESA', $empresaId)
                ->where('DESDE_ANOS', $d['DESDE_ANOS'])
                ->exists();
            if ($exists) {
                continue;
            }
            $maxId++;
            DB::table('PARAMETROS_AGUINALDOS')->insert(array_merge($d, [
                'ID_PARAMETRO_AGUINALDO' => $maxId,
                'ID_EMPRESA' => $empresaId,
                'SOBRE_EXCEDENTE' => 0,
            ]));
        }

        return response()->json(['message' => 'Parámetros default de aguinaldo cargados.']);
    }
}
