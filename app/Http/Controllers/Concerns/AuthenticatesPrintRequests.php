<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autenticación para rutas de impresión/descarga (web) que abren en pestaña nueva
 * y no pueden enviar el header Authorization: acepta el token Sanctum por query string.
 */
trait AuthenticatesPrintRequests
{
    protected function authenticatePrint(Request $request): void
    {
        if (Auth::check()) {
            return;
        }

        $token = $request->query('token') ?? $request->bearerToken();
        if (!$token) {
            abort(401, 'No autenticado.');
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            abort(401, 'Token inválido.');
        }

        Auth::login($accessToken->tokenable);
    }
}
