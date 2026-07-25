<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConceptosEmpleadoController extends Controller
{
    public function catalogs()
    {
        return response()->json([
            'empleados' => DB::table('EMPLEADO')
                ->where('ESACTIVO', true)
                ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'APELLIDO_2')
                ->orderBy('NOMBRES')
                ->get()
                ->map(function ($e) {
                    $e->NOMBRE_COMPLETO = trim($e->NOMBRES . ' ' . $e->APELLIDO_1 . ' ' . ($e->APELLIDO_2 ?? ''));
                    return $e;
                }),
            'tiposDescuento' => DB::table('TIPO_DESCUENTO')
                ->where('ESACTIVO', true)
                ->where('CATEGORIA', 'DESCUENTO')
                ->orderBy('NOMBRETIPODESC')
                ->get(),
            'tiposDescuentoPrestamo' => DB::table('TIPO_DESCUENTO')
                ->where('ESACTIVO', true)
                ->where('CATEGORIA', 'PRESTAMO')
                ->orderBy('NOMBRETIPODESC')
                ->get(),
            'tiposIngreso' => DB::table('TIPO_INGRESO')->where('ESACTIVO', true)->orderBy('TIPOINGRESO')->get(),
            'tiposPrestamo' => DB::table('TIPO_PRESTAMO')->orderBy('NOMBREPRESTAMO')->get(),
        ]);
    }
}
