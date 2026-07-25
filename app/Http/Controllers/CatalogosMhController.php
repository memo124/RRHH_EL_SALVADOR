<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogosMhController extends Controller
{
    public function index()
    {
        $documentos = DB::table('TIPO_DOCUMENTO_IDENTIDAD')->orderBy('ID_TIPODOCUMENTO')->get();
        return response()->json($documentos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'CODIGO_MH' => 'required|string|max:10',
            'NOMBREDOCUMENTO' => 'required|string|max:100',
            'MASCARA_FORMATO' => 'nullable|string|max:50',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = DB::table('TIPO_DOCUMENTO_IDENTIDAD')->max('ID_TIPODOCUMENTO') ?? 0;
        $id = $maxId + 1;

        DB::table('TIPO_DOCUMENTO_IDENTIDAD')->insert([
            'ID_TIPODOCUMENTO' => $id,
            'CODIGO_MH' => $request->CODIGO_MH,
            'NOMBREDOCUMENTO' => $request->NOMBREDOCUMENTO,
            'MASCARA_FORMATO' => $request->MASCARA_FORMATO,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_TIPODOCUMENTO' => $id, 'CODIGO_MH' => $request->CODIGO_MH, 'NOMBREDOCUMENTO' => $request->NOMBREDOCUMENTO, 'MASCARA_FORMATO' => $request->MASCARA_FORMATO, 'ESACTIVO' => $request->ESACTIVO ?? true], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'CODIGO_MH' => 'required|string|max:10',
            'NOMBREDOCUMENTO' => 'required|string|max:100',
            'MASCARA_FORMATO' => 'nullable|string|max:50',
            'ESACTIVO' => 'boolean',
        ]);

        DB::table('TIPO_DOCUMENTO_IDENTIDAD')
            ->where('ID_TIPODOCUMENTO', $id)
            ->update([
                'CODIGO_MH' => $request->CODIGO_MH,
                'NOMBREDOCUMENTO' => $request->NOMBREDOCUMENTO,
                'MASCARA_FORMATO' => $request->MASCARA_FORMATO,
                'ESACTIVO' => $request->ESACTIVO ?? true,
            ]);

        return response()->json(['ID_TIPODOCUMENTO' => $id, 'CODIGO_MH' => $request->CODIGO_MH, 'NOMBREDOCUMENTO' => $request->NOMBREDOCUMENTO, 'MASCARA_FORMATO' => $request->MASCARA_FORMATO, 'ESACTIVO' => $request->ESACTIVO ?? true]);
    }

    public function destroy($id)
    {
        // soft-delete to maintain references
        DB::table('TIPO_DOCUMENTO_IDENTIDAD')
            ->where('ID_TIPODOCUMENTO', $id)
            ->update(['ESACTIVO' => false]);

        return response()->json(['message' => 'Tipo de documento inactivado correctamente.']);
    }
}
