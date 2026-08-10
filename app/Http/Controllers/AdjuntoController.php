<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\AdjuntoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdjuntoController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected AdjuntoService $adjunto) {}

    public function index(Request $request)
    {
        $query = DB::table('ADJUNTO')
            ->leftJoin('TIPO_DOCUMENTO_ADJUNTO', 'ADJUNTO.ID_TIPO_DOCUMENTO_ADJUNTO', '=', 'TIPO_DOCUMENTO_ADJUNTO.ID_TIPO_DOCUMENTO_ADJUNTO')
            ->leftJoin('EMPLEADO', 'ADJUNTO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('ADJUNTO.ESACTIVO', true)
            ->select(
                'ADJUNTO.*',
                'TIPO_DOCUMENTO_ADJUNTO.NOMBRE as TIPO_NOMBRE',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE")
            )
            ->orderByDesc('ADJUNTO.FECHA_SUBIDA');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('ADJUNTO.ID_EMPLEADO', (int) $request->ID_EMPLEADO);
        }
        if ($request->filled('ORIGEN')) {
            $query->where('ADJUNTO.ORIGEN', $request->ORIGEN);
        }

        return $this->paginateQuery($query, $request, ['ADJUNTO.NOMBRE_ARCHIVO', 'EMPLEADO.NOMBRES']);
    }

    public function tipos()
    {
        return response()->json($this->adjunto->tiposDocumento());
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240',
            'ID_EMPLEADO' => 'nullable|integer',
            'ID_TIPO_DOCUMENTO_ADJUNTO' => 'nullable|integer',
            'ORIGEN' => 'nullable|string|max:30',
            'ID_ORIGEN' => 'nullable|integer',
        ]);

        try {
            $row = $this->adjunto->store(
                $request->file('archivo'),
                $request->ID_EMPLEADO ? (int) $request->ID_EMPLEADO : null,
                $request->ID_TIPO_DOCUMENTO_ADJUNTO ? (int) $request->ID_TIPO_DOCUMENTO_ADJUNTO : null,
                $request->ORIGEN ?? 'manual',
                $request->ID_ORIGEN ? (int) $request->ID_ORIGEN : null,
                $request->user()?->ID_USUARIO
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($row, 201);
    }

    public function download($id)
    {
        return $this->adjunto->download((int) $id);
    }

    public function destroy($id)
    {
        $this->adjunto->softDelete((int) $id);
        return response()->json(['message' => 'Documento inactivado correctamente.']);
    }
}
