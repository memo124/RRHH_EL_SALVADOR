<?php

namespace App\Http\Responses;

use App\Services\ErrorJournalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    public function __construct(protected ErrorJournalService $journal)
    {
    }

    public function render(Throwable $exception, Request $request): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Debe iniciar sesión para continuar.',
            ], 401);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'El recurso solicitado no existe.',
            ], 404);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'Solicitud no válida.';

            if ($status >= 500) {
                $reference = $this->journal->record($exception, $request);

                return response()->json([
                    'message' => "Ocurrió un error inesperado. Referencia: {$reference}",
                    'reference' => $reference,
                ], $status);
            }

            return response()->json(['message' => $message], $status);
        }

        $reference = $this->journal->record($exception, $request);

        return response()->json([
            'message' => "Ocurrió un error inesperado. Referencia: {$reference}",
            'reference' => $reference,
        ], 500);
    }
}
