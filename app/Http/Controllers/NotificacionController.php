<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request)
    {
        $query = DB::table('NOTIFICACION')
            ->where('ID_USUARIO', $request->user()->ID_USUARIO)
            ->where('ESACTIVO', true)
            ->orderBy('FECHA_CREACION', 'desc');

        if ($request->boolean('solo_no_leidas')) {
            $query->where('LEIDA', false);
        }

        return $this->paginateQuery($query, $request, ['TITULO', 'MENSAJE'], 20);
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['count' => $this->notifications->unreadCount($request->user()->ID_USUARIO)]);
    }

    public function markRead(Request $request, $id)
    {
        $this->notifications->markRead((int) $id, $request->user()->ID_USUARIO);
        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    public function markAllRead(Request $request)
    {
        $this->notifications->markAllRead($request->user()->ID_USUARIO);
        return response()->json(['message' => 'Todas las notificaciones fueron marcadas como leídas.']);
    }
}
