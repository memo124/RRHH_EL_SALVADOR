<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bitácora de auditoría de cambios sensibles (crear/editar/eliminar).
 * El registro es "best-effort": nunca debe romper el flujo que audita.
 */
class AuditService
{
    public function log(
        string $tabla,
        int|string|null $idRegistro,
        string $accion,
        ?array $before = null,
        ?array $after = null,
        ?int $idUsuario = null,
        ?string $ip = null
    ): void {
        try {
            DB::table('AUDITORIA')->insert([
                'ID_USUARIO' => $idUsuario,
                'TABLA' => $tabla,
                'ID_REGISTRO' => $idRegistro !== null ? (string) $idRegistro : null,
                'ACCION' => $accion,
                'BEFORE_JSON' => $before !== null ? json_encode($before) : null,
                'AFTER_JSON' => $after !== null ? json_encode($after) : null,
                'IP' => $ip,
                'FECHA' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('No se pudo registrar auditoría.', [
                'TABLA' => $tabla,
                'ID_REGISTRO' => $idRegistro,
                'ACCION' => $accion,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Convierte una fila de DB (objeto o array) a array plano, ocultando campos sensibles. */
    public function sanitize(object|array|null $row, array $hide = []): ?array
    {
        if ($row === null) {
            return null;
        }

        $data = (array) $row;
        foreach ($hide as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}
