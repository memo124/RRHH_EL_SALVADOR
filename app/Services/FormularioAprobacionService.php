<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FormularioAprobacionService
{
    public function aprobar(int $idRespuesta, int $idUsuario): bool
    {
        $resp = DB::table('FORMULARIO_RESPUESTA')
            ->where('ID_RESPUESTA', $idRespuesta)
            ->where('ESTADO', 'pendiente_aprobacion')
            ->where('ESACTIVO', true)
            ->first();

        if (!$resp) {
            return false;
        }

        $camposResp = DB::table('FORMULARIO_RESPUESTA_CAMPO')
            ->join('FORMULARIO_CAMPO', 'FORMULARIO_RESPUESTA_CAMPO.ID_CAMPO', '=', 'FORMULARIO_CAMPO.ID_CAMPO')
            ->where('FORMULARIO_RESPUESTA_CAMPO.ID_RESPUESTA', $idRespuesta)
            ->select('FORMULARIO_RESPUESTA_CAMPO.*', 'FORMULARIO_CAMPO.*')
            ->get();

        $idEmpleado = $resp->ID_EMPLEADO;

        DB::transaction(function () use ($camposResp, $idEmpleado, $idRespuesta, $idUsuario) {
            foreach ($camposResp as $cr) {
                $this->aplicarCampo($idEmpleado, $cr);
            }

            DB::table('FORMULARIO_RESPUESTA')->where('ID_RESPUESTA', $idRespuesta)->update([
                'ESTADO' => 'aprobada',
                'FECHA_REVISION' => now(),
                'ID_USUARIO_REVISION' => $idUsuario,
            ]);
        });

        return true;
    }

    public function rechazar(int $idRespuesta, int $idUsuario, string $motivo): bool
    {
        return DB::table('FORMULARIO_RESPUESTA')
            ->where('ID_RESPUESTA', $idRespuesta)
            ->where('ESTADO', 'pendiente_aprobacion')
            ->update([
                'ESTADO' => 'rechazada',
                'FECHA_REVISION' => now(),
                'ID_USUARIO_REVISION' => $idUsuario,
                'MOTIVO_RECHAZO' => $motivo,
            ]) > 0;
    }

    private function aplicarCampo(int $idEmpleado, object $cr): void
    {
        $tabla = $cr->MAPEO_TABLA;
        $columna = $cr->MAPEO_COLUMNA;

        if ($tabla === 'EMPLEADO' && $columna && $cr->VALOR_TEXTO !== null) {
            DB::table('EMPLEADO')->where('ID_EMPLEADO', $idEmpleado)->update([
                $columna => $cr->VALOR_TEXTO,
            ]);
            return;
        }

        if ($tabla === 'EMPLEADO_EDUCACION') {
            $json = $cr->VALOR_JSON ? json_decode($cr->VALOR_JSON, true) : null;
            if (is_array($json)) {
                $maxId = DB::table('EMPLEADO_EDUCACION')->max('ID_EMPLEADO_EDUCACION') ?? 0;
                DB::table('EMPLEADO_EDUCACION')->insert([
                    'ID_EMPLEADO_EDUCACION' => $maxId + 1,
                    'ID_EMPLEADO' => $idEmpleado,
                    'ID_EDUCACIONACADEMICA' => $json['ID_EDUCACIONACADEMICA'] ?? null,
                    'TITULO_OBTENIDO' => $json['TITULO_OBTENIDO'] ?? $cr->VALOR_TEXTO,
                    'INSTITUCION' => $json['INSTITUCION'] ?? null,
                    'FECHA_GRADUACION' => $json['FECHA_GRADUACION'] ?? null,
                    'ESACTIVO' => true,
                ]);
            } elseif ($cr->VALOR_TEXTO) {
                $maxId = DB::table('EMPLEADO_EDUCACION')->max('ID_EMPLEADO_EDUCACION') ?? 0;
                DB::table('EMPLEADO_EDUCACION')->insert([
                    'ID_EMPLEADO_EDUCACION' => $maxId + 1,
                    'ID_EMPLEADO' => $idEmpleado,
                    'TITULO_OBTENIDO' => $cr->VALOR_TEXTO,
                    'ESACTIVO' => true,
                ]);
            }
            return;
        }

        if ($tabla === 'EMPLEADO_CERTIFICACION') {
            $json = $cr->VALOR_JSON ? json_decode($cr->VALOR_JSON, true) : null;
            if (is_array($json) && isset($json[0])) {
                foreach ($json as $cert) {
                    $this->insertCertificacion($idEmpleado, $cert);
                }
            } elseif ($cr->VALOR_TEXTO) {
                $this->insertCertificacion($idEmpleado, ['NOMBRE' => $cr->VALOR_TEXTO]);
            }
            return;
        }

        if ($tabla === 'EMPLEADO_DEPENDIENTE') {
            $json = $cr->VALOR_JSON ? json_decode($cr->VALOR_JSON, true) : null;
            if (is_array($json) && isset($json[0])) {
                foreach ($json as $dep) {
                    $this->insertDependiente($idEmpleado, $dep);
                }
            } elseif ($cr->VALOR_TEXTO) {
                $this->insertDependiente($idEmpleado, [
                    'NOMBRES' => $cr->VALOR_TEXTO,
                    'PARENTESCO' => 'OTRO',
                ]);
            }
        }

        // Adjuntos vinculados al empleado
        if ($cr->ID_ADJUNTO) {
            DB::table('ADJUNTO')->where('ID_ADJUNTO', $cr->ID_ADJUNTO)->update([
                'ID_EMPLEADO' => $idEmpleado,
                'ORIGEN' => 'formulario',
                'ID_ORIGEN' => $cr->ID_RESPUESTA,
            ]);
        }
    }

    private function insertCertificacion(int $idEmpleado, array $data): void
    {
        $maxId = DB::table('EMPLEADO_CERTIFICACION')->max('ID_CERTIFICACION') ?? 0;
        DB::table('EMPLEADO_CERTIFICACION')->insert([
            'ID_CERTIFICACION' => $maxId + 1,
            'ID_EMPLEADO' => $idEmpleado,
            'NOMBRE' => $data['NOMBRE'] ?? 'Certificación',
            'INSTITUCION' => $data['INSTITUCION'] ?? null,
            'FECHA_EMISION' => $data['FECHA_EMISION'] ?? null,
            'FECHA_VENCIMIENTO' => $data['FECHA_VENCIMIENTO'] ?? null,
            'ESACTIVO' => true,
        ]);
    }

    private function insertDependiente(int $idEmpleado, array $data): void
    {
        $maxId = DB::table('EMPLEADO_DEPENDIENTE')->max('ID_DEPENDIENTE') ?? 0;
        DB::table('EMPLEADO_DEPENDIENTE')->insert([
            'ID_DEPENDIENTE' => $maxId + 1,
            'ID_EMPLEADO' => $idEmpleado,
            'NOMBRES' => $data['NOMBRES'] ?? '',
            'APELLIDOS' => $data['APELLIDOS'] ?? null,
            'PARENTESCO' => $data['PARENTESCO'] ?? 'OTRO',
            'FECHA_NACIMIENTO' => $data['FECHA_NACIMIENTO'] ?? null,
            'DOCUMENTO_IDENTIDAD' => $data['DOCUMENTO_IDENTIDAD'] ?? null,
            'ESACTIVO' => true,
        ]);
    }
}
