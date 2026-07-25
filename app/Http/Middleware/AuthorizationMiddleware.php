<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationMiddleware
{
    /**
     * Valida el acceso del usuario activo bajo el siguiente orden de prioridad:
     * 1. Buscar en USUARIO_PERMISO. Si ES_CONCEDIDO = 1 -> Permite; si ES_CONCEDIDO = 0 -> Deniega.
     * 2. Si no hay registro directo, evaluar si el permiso existe a través de los roles asignados en USUARIO_ROL -> ROL_PERMISO.
     */
    public function handle(Request $request, Closure $next, string $codigoPermiso): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Buscar el permiso
        $permiso = DB::table('PERMISO')
            ->where('CODIGO_PERMISO', $codigoPermiso)
            ->first();

        if (!$permiso) {
            return response()->json(['error' => 'Permiso no definido'], 403);
        }

        // 1. Evaluar override directo en USUARIO_PERMISO
        $override = DB::table('USUARIO_PERMISO')
            ->where('ID_USUARIO', $user->ID_USUARIO)
            ->where('ID_PERMISO', $permiso->ID_PERMISO)
            ->first();

        if ($override !== null) {
            if ((int)$override->ES_CONCEDIDO === 1) {
                return $next($request);
            } else {
                return response()->json(['error' => 'No autorizado por revocación directa'], 403);
            }
        }

        // 2. Evaluar mediante Roles (USUARIO_ROL -> ROL_PERMISO)
        $hasRolePermiso = DB::table('USUARIO_ROL')
            ->join('ROL_PERMISO', 'USUARIO_ROL.ID_ROL', '=', 'ROL_PERMISO.ID_ROL')
            ->where('USUARIO_ROL.ID_USUARIO', $user->ID_USUARIO)
            ->where('ROL_PERMISO.ID_PERMISO', $permiso->ID_PERMISO)
            ->exists();

        if ($hasRolePermiso) {
            return $next($request);
        }

        return response()->json(['error' => 'No autorizado'], 403);
    }
}
