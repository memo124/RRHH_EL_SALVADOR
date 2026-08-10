<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormularioEmpleadoService
{
    public function __construct(
        protected CalendarioEventoService $calendario
    ) {}

    public function findPlantilla(int $id): ?object
    {
        return DB::table('FORMULARIO_PLANTILLA')->where('ID_PLANTILLA', $id)->where('ESACTIVO', true)->first();
    }

    public function getCampos(int $idPlantilla): array
    {
        return DB::table('FORMULARIO_CAMPO')
            ->where('ID_PLANTILLA', $idPlantilla)
            ->where('ESACTIVO', true)
            ->orderBy('ORDEN')
            ->get()
            ->map(function ($c) {
                $c->OPCIONES = $c->OPCIONES ? json_decode($c->OPCIONES, true) : null;
                return $c;
            })
            ->all();
    }

    public function saveCampos(int $idPlantilla, array $campos): void
    {
        DB::table('FORMULARIO_CAMPO')->where('ID_PLANTILLA', $idPlantilla)->update(['ESACTIVO' => false]);

        $maxId = DB::table('FORMULARIO_CAMPO')->max('ID_CAMPO') ?? 0;
        foreach ($campos as $i => $c) {
            $maxId++;
            DB::table('FORMULARIO_CAMPO')->insert([
                'ID_CAMPO' => $maxId,
                'ID_PLANTILLA' => $idPlantilla,
                'ORDEN' => $c['ORDEN'] ?? ($i + 1),
                'ETIQUETA' => $c['ETIQUETA'],
                'TIPO_CAMPO' => $c['TIPO_CAMPO'],
                'MAPEO_TABLA' => $c['MAPEO_TABLA'] ?? null,
                'MAPEO_COLUMNA' => $c['MAPEO_COLUMNA'] ?? null,
                'OPCIONES' => isset($c['OPCIONES']) ? json_encode($c['OPCIONES']) : null,
                'REQUERIDO' => $c['REQUERIDO'] ?? false,
                'ESACTIVO' => true,
            ]);
        }
    }

    public function activarCampana(int $idCampana): bool
    {
        $camp = DB::table('FORMULARIO_CAMPANA')->where('ID_CAMPANA', $idCampana)->first();
        if (!$camp) {
            return false;
        }

        DB::table('FORMULARIO_CAMPANA')->where('ID_CAMPANA', $idCampana)->update(['ESTADO' => 'activa']);

        $this->calendario->syncCampanaEvento($idCampana, $camp->NOMBRE, $camp->FECHA_FIN);

        return true;
    }

    public function generarInvitaciones(int $idCampana, array $idEmpleados, ?string $fechaExpiracion = null): array
    {
        $camp = DB::table('FORMULARIO_CAMPANA')->where('ID_CAMPANA', $idCampana)->first();
        if (!$camp) {
            return [];
        }

        $exp = $fechaExpiracion ?? $camp->FECHA_FIN;
        $maxId = DB::table('FORMULARIO_INVITACION')->max('ID_INVITACION') ?? 0;
        $links = [];

        foreach ($idEmpleados as $idEmp) {
            $exists = DB::table('FORMULARIO_INVITACION')
                ->where('ID_CAMPANA', $idCampana)
                ->where('ID_EMPLEADO', $idEmp)
                ->where('ESACTIVO', true)
                ->first();

            if ($exists) {
                $links[] = [
                    'ID_INVITACION' => $exists->ID_INVITACION,
                    'ID_EMPLEADO' => $idEmp,
                    'TOKEN' => $exists->TOKEN,
                    'URL' => url("/actualizar-datos/{$exists->TOKEN}"),
                ];
                continue;
            }

            $maxId++;
            $token = Str::random(48);
            DB::table('FORMULARIO_INVITACION')->insert([
                'ID_INVITACION' => $maxId,
                'ID_CAMPANA' => $idCampana,
                'ID_EMPLEADO' => $idEmp,
                'TOKEN' => $token,
                'FECHA_EXPIRACION' => $exp,
                'ESTADO' => 'pendiente',
                'ESACTIVO' => true,
            ]);

            $links[] = [
                'ID_INVITACION' => $maxId,
                'ID_EMPLEADO' => $idEmp,
                'TOKEN' => $token,
                'URL' => url("/actualizar-datos/{$token}"),
            ];
        }

        return $links;
    }

    public function invitacionPorToken(string $token): ?object
    {
        $inv = DB::table('FORMULARIO_INVITACION')
            ->join('FORMULARIO_CAMPANA', 'FORMULARIO_INVITACION.ID_CAMPANA', '=', 'FORMULARIO_CAMPANA.ID_CAMPANA')
            ->join('FORMULARIO_PLANTILLA', 'FORMULARIO_CAMPANA.ID_PLANTILLA', '=', 'FORMULARIO_PLANTILLA.ID_PLANTILLA')
            ->join('EMPLEADO', 'FORMULARIO_INVITACION.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('FORMULARIO_INVITACION.TOKEN', $token)
            ->where('FORMULARIO_INVITACION.ESACTIVO', true)
            ->select(
                'FORMULARIO_INVITACION.*',
                'FORMULARIO_CAMPANA.NOMBRE as CAMPANA_NOMBRE',
                'FORMULARIO_CAMPANA.ESTADO as CAMPANA_ESTADO',
                'FORMULARIO_CAMPANA.ID_PLANTILLA',
                'FORMULARIO_PLANTILLA.NOMBRE as PLANTILLA_NOMBRE',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPLEADO.CODIGOEMPLEADO',
                'EMPLEADO.ID_EMPLEADO'
            )
            ->first();

        if (!$inv) {
            return null;
        }

        if ($inv->FECHA_EXPIRACION && now()->gt($inv->FECHA_EXPIRACION)) {
            DB::table('FORMULARIO_INVITACION')->where('ID_INVITACION', $inv->ID_INVITACION)->update(['ESTADO' => 'expirada']);
            return null;
        }

        if ($inv->CAMPANA_ESTADO !== 'activa') {
            return null;
        }

        return $inv;
    }

    public function datosActualesEmpleado(int $idEmpleado, array $campos): array
    {
        $emp = DB::table('EMPLEADO')->where('ID_EMPLEADO', $idEmpleado)->first();
        $data = [];

        foreach ($campos as $campo) {
            $key = 'campo_' . $campo->ID_CAMPO;
            if ($campo->MAPEO_TABLA === 'EMPLEADO' && $campo->MAPEO_COLUMNA && $emp) {
                $data[$key] = $emp->{$campo->MAPEO_COLUMNA} ?? null;
            } elseif ($campo->MAPEO_TABLA === 'EMPLEADO_EDUCACION') {
                $edu = DB::table('EMPLEADO_EDUCACION')
                    ->where('ID_EMPLEADO', $idEmpleado)
                    ->where('ESACTIVO', true)
                    ->orderByDesc('ID_EMPLEADO_EDUCACION')
                    ->first();
                $data[$key] = $edu ? (array) $edu : null;
            } elseif ($campo->MAPEO_TABLA === 'EMPLEADO_CERTIFICACION') {
                $data[$key] = DB::table('EMPLEADO_CERTIFICACION')
                    ->where('ID_EMPLEADO', $idEmpleado)
                    ->where('ESACTIVO', true)
                    ->get()
                    ->all();
            } elseif ($campo->MAPEO_TABLA === 'EMPLEADO_DEPENDIENTE') {
                $data[$key] = DB::table('EMPLEADO_DEPENDIENTE')
                    ->where('ID_EMPLEADO', $idEmpleado)
                    ->where('ESACTIVO', true)
                    ->get()
                    ->all();
            } else {
                $data[$key] = null;
            }
        }

        return $data;
    }

    public function enviarRespuesta(string $token, array $valores, ?int $idUsuario = null): ?int
    {
        $inv = $this->invitacionPorToken($token);
        if (!$inv || $inv->ESTADO === 'completada') {
            return null;
        }

        $campos = $this->getCampos($inv->ID_PLANTILLA);

        $maxResp = DB::table('FORMULARIO_RESPUESTA')->max('ID_RESPUESTA') ?? 0;
        $idRespuesta = $maxResp + 1;

        DB::table('FORMULARIO_RESPUESTA')->insert([
            'ID_RESPUESTA' => $idRespuesta,
            'ID_CAMPANA' => $inv->ID_CAMPANA,
            'ID_INVITACION' => $inv->ID_INVITACION,
            'ID_EMPLEADO' => $inv->ID_EMPLEADO,
            'ESTADO' => 'pendiente_aprobacion',
            'FECHA_ENVIO' => now(),
            'ESACTIVO' => true,
        ]);

        $maxCampo = DB::table('FORMULARIO_RESPUESTA_CAMPO')->max('ID_RESPUESTA_CAMPO') ?? 0;
        foreach ($campos as $campo) {
            $key = 'campo_' . $campo->ID_CAMPO;
            if (!array_key_exists($key, $valores)) {
                continue;
            }
            $val = $valores[$key];
            $maxCampo++;
            DB::table('FORMULARIO_RESPUESTA_CAMPO')->insert([
                'ID_RESPUESTA_CAMPO' => $maxCampo,
                'ID_RESPUESTA' => $idRespuesta,
                'ID_CAMPO' => $campo->ID_CAMPO,
                'VALOR_TEXTO' => is_string($val) ? $val : null,
                'VALOR_JSON' => is_array($val) ? json_encode($val) : null,
                'ID_ADJUNTO' => is_numeric($val) && ($campo->TIPO_CAMPO === 'archivo') ? (int) $val : null,
            ]);
        }

        DB::table('FORMULARIO_INVITACION')->where('ID_INVITACION', $inv->ID_INVITACION)->update([
            'ESTADO' => 'completada',
            'FECHA_COMPLETADA' => now(),
        ]);

        return $idRespuesta;
    }

    public function plantillaDefaultActualizacion(): array
    {
        return [
            'NOMBRE' => 'Actualización de datos del empleado',
            'DESCRIPCION' => 'Plantilla estándar para actualización anual de datos personales, educación, certificaciones y dependientes.',
            'CAMPOS' => [
                ['ORDEN' => 1, 'ETIQUETA' => 'Correo electrónico personal', 'TIPO_CAMPO' => 'texto', 'MAPEO_TABLA' => 'EMPLEADO', 'MAPEO_COLUMNA' => 'CORREOELECTRONICO', 'REQUERIDO' => false],
                ['ORDEN' => 2, 'ETIQUETA' => 'Teléfono celular', 'TIPO_CAMPO' => 'texto', 'MAPEO_TABLA' => 'EMPLEADO', 'MAPEO_COLUMNA' => 'TELEFONOCELULAR', 'REQUERIDO' => false],
                ['ORDEN' => 3, 'ETIQUETA' => 'Dirección', 'TIPO_CAMPO' => 'textarea', 'MAPEO_TABLA' => 'EMPLEADO', 'MAPEO_COLUMNA' => 'DIRECCION', 'REQUERIDO' => false],
                ['ORDEN' => 4, 'ETIQUETA' => 'Nivel educativo / título', 'TIPO_CAMPO' => 'textarea', 'MAPEO_TABLA' => 'EMPLEADO_EDUCACION', 'MAPEO_COLUMNA' => null, 'REQUERIDO' => false],
                ['ORDEN' => 5, 'ETIQUETA' => 'Certificaciones profesionales', 'TIPO_CAMPO' => 'textarea', 'MAPEO_TABLA' => 'EMPLEADO_CERTIFICACION', 'MAPEO_COLUMNA' => null, 'REQUERIDO' => false],
                ['ORDEN' => 6, 'ETIQUETA' => 'Dependientes (nuevo hijo, cónyuge)', 'TIPO_CAMPO' => 'textarea', 'MAPEO_TABLA' => 'EMPLEADO_DEPENDIENTE', 'MAPEO_COLUMNA' => null, 'REQUERIDO' => false],
                ['ORDEN' => 7, 'ETIQUETA' => 'Adjuntar título universitario (PDF)', 'TIPO_CAMPO' => 'archivo', 'MAPEO_TABLA' => 'CUSTOM', 'MAPEO_COLUMNA' => null, 'REQUERIDO' => false],
                ['ORDEN' => 8, 'ETIQUETA' => 'Adjuntar partida de nacimiento (PDF)', 'TIPO_CAMPO' => 'archivo', 'MAPEO_TABLA' => 'CUSTOM', 'MAPEO_COLUMNA' => null, 'REQUERIDO' => false],
            ],
        ];
    }
}
