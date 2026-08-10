<?php

namespace App\Http\Controllers;

use App\Services\AdjuntoService;
use App\Services\FormularioEmpleadoService;
use Illuminate\Http\Request;

class FormularioPublicoController extends Controller
{
    public function __construct(
        protected FormularioEmpleadoService $formulario,
        protected AdjuntoService $adjunto
    ) {}

    public function show(string $token)
    {
        $inv = $this->formulario->invitacionPorToken($token);
        if (!$inv) {
            return response()->json(['error' => 'Enlace inválido o expirado.'], 404);
        }

        $campos = $this->formulario->getCampos($inv->ID_PLANTILLA);
        $datosActuales = $this->formulario->datosActualesEmpleado($inv->ID_EMPLEADO, $campos);

        return response()->json([
            'invitacion' => [
                'CAMPANA_NOMBRE' => $inv->CAMPANA_NOMBRE,
                'PLANTILLA_NOMBRE' => $inv->PLANTILLA_NOMBRE,
                'EMPLEADO_NOMBRE' => $inv->EMPLEADO_NOMBRE,
                'CODIGOEMPLEADO' => $inv->CODIGOEMPLEADO,
                'FECHA_EXPIRACION' => $inv->FECHA_EXPIRACION,
            ],
            'campos' => $campos,
            'datos_actuales' => $datosActuales,
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $inv = $this->formulario->invitacionPorToken($token);
        if (!$inv) {
            return response()->json(['error' => 'Enlace inválido o expirado.'], 404);
        }

        $request->validate([
            'valores' => 'required|array',
        ]);

        $idRespuesta = $this->formulario->enviarRespuesta($token, $request->valores);
        if (!$idRespuesta) {
            return response()->json(['error' => 'No se pudo enviar la respuesta.'], 422);
        }

        return response()->json([
            'ID_RESPUESTA' => $idRespuesta,
            'message' => 'Sus datos fueron enviados y están pendientes de aprobación por RRHH.',
        ], 201);
    }

    public function uploadAdjunto(Request $request, string $token)
    {
        $inv = $this->formulario->invitacionPorToken($token);
        if (!$inv) {
            return response()->json(['error' => 'Enlace inválido o expirado.'], 404);
        }

        $request->validate([
            'archivo' => 'required|file|max:10240',
            'ID_TIPO_DOCUMENTO_ADJUNTO' => 'nullable|integer',
        ]);

        try {
            $row = $this->adjunto->store(
                $request->file('archivo'),
                $inv->ID_EMPLEADO,
                $request->ID_TIPO_DOCUMENTO_ADJUNTO ? (int) $request->ID_TIPO_DOCUMENTO_ADJUNTO : null,
                'formulario',
                null,
                null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($row, 201);
    }
}
