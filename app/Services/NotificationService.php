<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notificaciones in-app. Todas las escrituras son "best-effort": nunca lanzan
 * excepciones para no interrumpir el flujo de negocio que las origina
 * (aprobaciones, publicaciones, asignaciones, etc.).
 */
class NotificationService
{
    public function notifyUser(int $idUsuario, string $titulo, string $mensaje, string $tipo = 'info', ?string $link = null): ?int
    {
        try {
            $id = (DB::table('NOTIFICACION')->max('ID_NOTIFICACION') ?? 0) + 1;

            DB::table('NOTIFICACION')->insert([
                'ID_NOTIFICACION' => $id,
                'ID_USUARIO' => $idUsuario,
                'TITULO' => $titulo,
                'MENSAJE' => $mensaje,
                'TIPO' => $tipo,
                'LEIDA' => false,
                'LINK' => $link,
                'FECHA_CREACION' => now(),
                'ESACTIVO' => true,
            ]);

            return $id;
        } catch (Throwable $e) {
            Log::warning('No se pudo crear la notificación.', ['ID_USUARIO' => $idUsuario, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Notifica a todos los usuarios de sistema asociados a un empleado.
     *
     * @return list<int> IDs de las notificaciones creadas
     */
    public function notifyByEmpleado(int $idEmpleado, string $titulo, string $mensaje, string $tipo = 'info', ?string $link = null): array
    {
        try {
            $idsUsuarios = DB::table('USUARIO')
                ->where('ID_EMPLEADO', $idEmpleado)
                ->where('ESACTIVO', true)
                ->pluck('ID_USUARIO');
        } catch (Throwable $e) {
            Log::warning('No se pudieron resolver usuarios del empleado para notificar.', ['ID_EMPLEADO' => $idEmpleado, 'error' => $e->getMessage()]);
            return [];
        }

        $creadas = [];
        foreach ($idsUsuarios as $idUsuario) {
            $id = $this->notifyUser((int) $idUsuario, $titulo, $mensaje, $tipo, $link);
            if ($id !== null) {
                $creadas[] = $id;
            }
        }

        return $creadas;
    }

    public function unreadCount(int $idUsuario): int
    {
        return DB::table('NOTIFICACION')
            ->where('ID_USUARIO', $idUsuario)
            ->where('ESACTIVO', true)
            ->where('LEIDA', false)
            ->count();
    }

    public function markRead(int $idNotificacion, int $idUsuario): bool
    {
        return DB::table('NOTIFICACION')
            ->where('ID_NOTIFICACION', $idNotificacion)
            ->where('ID_USUARIO', $idUsuario)
            ->update(['LEIDA' => true]) > 0;
    }

    public function markAllRead(int $idUsuario): int
    {
        return DB::table('NOTIFICACION')
            ->where('ID_USUARIO', $idUsuario)
            ->where('LEIDA', false)
            ->update(['LEIDA' => true]);
    }
}
