<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'empleados_activos' => DB::table('EMPLEADO')->where('ESACTIVO', true)->count(),
            'planillas_pendientes' => DB::table('PLANILLA')->where('RECALCULADA', false)->where('ANULADA', false)->count(),
            'incapacidades_activas' => DB::table('INCAPACIDAD')
                ->where('ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
                ->where('FECHA_FIN', '>=', now()->toDateString())
                ->count(),
            'prestamos_activos' => DB::table('PRESTAMOS')->where('PRESTAMOESTADO', true)->where('SALDO_ACTUAL', '>', 0)->count(),
            'ultima_planilla' => DB::table('PLANILLA')
                ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
                ->select('PLANILLA.*', 'PERIODO_LABORAL.CALPERIODO')
                ->orderBy('PLANILLA.ID_PLANILLA', 'desc')
                ->first(),
        ]);
    }
}
