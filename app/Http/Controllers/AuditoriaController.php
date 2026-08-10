<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('AUDITORIA')
            ->leftJoin('USUARIO', 'AUDITORIA.ID_USUARIO', '=', 'USUARIO.ID_USUARIO')
            ->select('AUDITORIA.*', 'USUARIO.USUARIO as USUARIO_NOMBRE')
            ->orderBy('AUDITORIA.FECHA', 'desc');

        if ($request->filled('TABLA')) {
            $query->where('AUDITORIA.TABLA', $request->input('TABLA'));
        }
        if ($request->filled('ACCION')) {
            $query->where('AUDITORIA.ACCION', $request->input('ACCION'));
        }
        if ($request->filled('fecha_inicio')) {
            $query->where('AUDITORIA.FECHA', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->where('AUDITORIA.FECHA', '<=', $request->input('fecha_fin') . ' 23:59:59');
        }

        return $this->paginateQuery($query, $request, ['AUDITORIA.TABLA', 'AUDITORIA.ID_REGISTRO', 'USUARIO.USUARIO']);
    }

    public function tablas()
    {
        return response()->json(
            DB::table('AUDITORIA')->select('TABLA')->distinct()->orderBy('TABLA')->pluck('TABLA')
        );
    }
}
