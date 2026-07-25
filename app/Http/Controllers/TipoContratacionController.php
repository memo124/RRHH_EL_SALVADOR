<?php

namespace App\Http\Controllers;

use App\Models\TipoContratacion;
use Illuminate\Http\Request;

class TipoContratacionController extends Controller
{
    public function index()
    {
        return response()->json(TipoContratacion::orderBy('ID_TIPOCONTRATACION')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'TIPOCONTRATACION' => 'required|string|max:100',
            'DESCRIPCION' => 'nullable|string|max:250',
            'ES_EVENTUAL' => 'boolean',
            'APLICA_ISSS' => 'boolean',
            'APLICA_AFP' => 'boolean',
            'APLICA_RENTA_TABLA' => 'boolean',
            'APLICA_RENTA_FIJA' => 'boolean',
            'PORCENTAJE_RENTA_FIJA' => 'required_if:APLICA_RENTA_FIJA,true|numeric|min:0|max:100',
            'APLICA_INSAFORP' => 'boolean',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = TipoContratacion::max('ID_TIPOCONTRATACION') ?? 0;

        $tipo = new TipoContratacion();
        $tipo->ID_TIPOCONTRATACION = $maxId + 1;
        $tipo->TIPOCONTRATACION = $request->TIPOCONTRATACION;
        $tipo->DESCRIPCION = $request->DESCRIPCION;
        $tipo->ES_EVENTUAL = $request->ES_EVENTUAL ?? false;
        $tipo->APLICA_ISSS = $request->APLICA_ISSS ?? true;
        $tipo->APLICA_AFP = $request->APLICA_AFP ?? true;
        $tipo->APLICA_RENTA_TABLA = $request->APLICA_RENTA_TABLA ?? true;
        $tipo->APLICA_RENTA_FIJA = $request->APLICA_RENTA_FIJA ?? false;
        $tipo->PORCENTAJE_RENTA_FIJA = $request->PORCENTAJE_RENTA_FIJA ?? 0.00;
        $tipo->APLICA_INSAFORP = $request->APLICA_INSAFORP ?? true;
        $tipo->ESACTIVO = $request->ESACTIVO ?? true;
        $tipo->save();

        return response()->json($tipo, 201);
    }

    public function update(Request $request, $id)
    {
        $tipo = TipoContratacion::findOrFail($id);

        $request->validate([
            'TIPOCONTRATACION' => 'required|string|max:100',
            'DESCRIPCION' => 'nullable|string|max:250',
            'ES_EVENTUAL' => 'boolean',
            'APLICA_ISSS' => 'boolean',
            'APLICA_AFP' => 'boolean',
            'APLICA_RENTA_TABLA' => 'boolean',
            'APLICA_RENTA_FIJA' => 'boolean',
            'PORCENTAJE_RENTA_FIJA' => 'required_if:APLICA_RENTA_FIJA,true|numeric|min:0|max:100',
            'APLICA_INSAFORP' => 'boolean',
            'ESACTIVO' => 'boolean',
        ]);

        $tipo->TIPOCONTRATACION = $request->TIPOCONTRATACION;
        $tipo->DESCRIPCION = $request->DESCRIPCION;
        $tipo->ES_EVENTUAL = $request->ES_EVENTUAL ?? false;
        $tipo->APLICA_ISSS = $request->APLICA_ISSS ?? true;
        $tipo->APLICA_AFP = $request->APLICA_AFP ?? true;
        $tipo->APLICA_RENTA_TABLA = $request->APLICA_RENTA_TABLA ?? true;
        $tipo->APLICA_RENTA_FIJA = $request->APLICA_RENTA_FIJA ?? false;
        $tipo->PORCENTAJE_RENTA_FIJA = $request->PORCENTAJE_RENTA_FIJA ?? 0.00;
        $tipo->APLICA_INSAFORP = $request->APLICA_INSAFORP ?? true;
        $tipo->ESACTIVO = $request->ESACTIVO ?? true;
        $tipo->save();

        return response()->json($tipo);
    }

    public function destroy($id)
    {
        $tipo = TipoContratacion::findOrFail($id);
        
        // Soft delete / Inactivate instead of DDL drop to maintain relational integrity
        $tipo->ESACTIVO = false;
        $tipo->save();

        return response()->json(['message' => 'Tipo de contratación inactivado correctamente.']);
    }
}
