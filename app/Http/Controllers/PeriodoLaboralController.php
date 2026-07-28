<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoLaboralController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('PERIODO_LABORAL')->orderBy('FECHAINICIO', 'desc');

        return $this->paginateQuery($query, $request, ['CALPERIODO']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'required|date|after_or_equal:FECHAINICIO',
            'CALPERIODO' => 'required|string|max:50',
            'ESACTIVO' => 'boolean',
        ]);

        $inicio = $request->FECHAINICIO;
        $fin = $request->FECHAFIN;
        $dias = (int) ((strtotime($fin) - strtotime($inicio)) / 86400) + 1;

        $maxId = DB::table('PERIODO_LABORAL')->max('ID_PERIODO') ?? 0;

        DB::table('PERIODO_LABORAL')->insert([
            'ID_PERIODO' => $maxId + 1,
            'FECHAINICIO' => $inicio,
            'FECHAFIN' => $fin,
            'DIAS' => $dias,
            'CALPERIODO' => $request->CALPERIODO,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_PERIODO' => $maxId + 1], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'FECHAINICIO' => 'required|date',
            'FECHAFIN' => 'required|date',
            'CALPERIODO' => 'required|string|max:50',
            'ESACTIVO' => 'boolean',
        ]);

        $dias = (int) ((strtotime($request->FECHAFIN) - strtotime($request->FECHAINICIO)) / 86400) + 1;

        DB::table('PERIODO_LABORAL')->where('ID_PERIODO', $id)->update([
            'FECHAINICIO' => $request->FECHAINICIO,
            'FECHAFIN' => $request->FECHAFIN,
            'DIAS' => $dias,
            'CALPERIODO' => $request->CALPERIODO,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['message' => 'Periodo actualizado.']);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
        ]);

        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $maxId = DB::table('PERIODO_LABORAL')->max('ID_PERIODO') ?? 0;
        $creados = 0;

        for ($m = 1; $m <= 12; $m++) {
            $inicio = sprintf('%04d-%02d-01', $request->anio, $m);
            $fin = date('Y-m-t', strtotime($inicio));
            $cal = $meses[$m - 1] . ' ' . $request->anio;

            $exists = DB::table('PERIODO_LABORAL')->where('CALPERIODO', $cal)->exists();
            if ($exists) {
                continue;
            }

            $maxId++;
            DB::table('PERIODO_LABORAL')->insert([
                'ID_PERIODO' => $maxId,
                'FECHAINICIO' => $inicio,
                'FECHAFIN' => $fin,
                'DIAS' => (int) date('t', strtotime($inicio)),
                'CALPERIODO' => $cal,
                'ESACTIVO' => true,
            ]);
            $creados++;
        }

        return response()->json(['message' => "Se generaron {$creados} periodos para {$request->anio}."]);
    }
}
