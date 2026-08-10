<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeePortal
{
    /**
     * Exige que el usuario autenticado tenga un empleado vinculado (USUARIO.ID_EMPLEADO)
     * para poder acceder a las rutas de autoservicio del Portal Empleado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->ID_EMPLEADO) {
            return response()->json(['error' => 'Acceso exclusivo para usuarios con empleado vinculado.'], 403);
        }

        return $next($request);
    }
}
