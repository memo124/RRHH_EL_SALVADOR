<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CapacitacionService
{
    public function __construct(protected CalendarioEventoService $calendario) {}

    public function find(int $id): ?object
    {
        return DB::table('CAPACITACION')->where('ID_CAPACITACION', $id)->where('ESACTIVO', true)->first();
    }

    public function publicar(int $idCapacitacion): bool
    {
        $cap = $this->find($idCapacitacion);
        if (!$cap) {
            return false;
        }

        DB::table('CAPACITACION')->where('ID_CAPACITACION', $idCapacitacion)->update(['ESTADO' => 'publicada']);

        $this->calendario->syncCapacitacionEvento(
            $idCapacitacion,
            $cap->TITULO,
            $cap->FECHA_INICIO,
            $cap->FECHA_FIN,
            $cap->ID_EMPRESA,
            $cap->LUGAR
        );

        return true;
    }

    public function cerrar(int $idCapacitacion): bool
    {
        return DB::table('CAPACITACION')->where('ID_CAPACITACION', $idCapacitacion)->update(['ESTADO' => 'cerrada']) > 0;
    }

    public function inscribir(int $idCapacitacion, array $idEmpleados): array
    {
        $cap = $this->find($idCapacitacion);
        if (!$cap || $cap->ESTADO !== 'publicada') {
            throw new \InvalidArgumentException('Capacitación no disponible para inscripción.');
        }

        $inscritos = DB::table('CAPACITACION_INSCRIPCION')
            ->where('ID_CAPACITACION', $idCapacitacion)
            ->where('ESACTIVO', true)
            ->count();

        $cupo = $cap->CUPO_MAX;
        if ($cupo && ($inscritos + count($idEmpleados)) > $cupo) {
            throw new \InvalidArgumentException("Cupo excedido. Disponible: " . max(0, $cupo - $inscritos));
        }

        $maxId = DB::table('CAPACITACION_INSCRIPCION')->max('ID_INSCRIPCION') ?? 0;
        $result = [];

        foreach ($idEmpleados as $idEmp) {
            $exists = DB::table('CAPACITACION_INSCRIPCION')
                ->where('ID_CAPACITACION', $idCapacitacion)
                ->where('ID_EMPLEADO', $idEmp)
                ->where('ESACTIVO', true)
                ->exists();

            if ($exists) {
                continue;
            }

            $maxId++;
            DB::table('CAPACITACION_INSCRIPCION')->insert([
                'ID_INSCRIPCION' => $maxId,
                'ID_CAPACITACION' => $idCapacitacion,
                'ID_EMPLEADO' => $idEmp,
                'ESTADO' => 'inscrito',
                'FECHA_INSCRIPCION' => now(),
                'ESACTIVO' => true,
            ]);
            $result[] = $maxId;
        }

        return $result;
    }

    public function getInscripciones(int $idCapacitacion): array
    {
        return DB::table('CAPACITACION_INSCRIPCION')
            ->join('EMPLEADO', 'CAPACITACION_INSCRIPCION.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('ADJUNTO as CERT', 'CAPACITACION_INSCRIPCION.ID_ADJUNTO_CERTIFICADO', '=', 'CERT.ID_ADJUNTO')
            ->where('CAPACITACION_INSCRIPCION.ID_CAPACITACION', $idCapacitacion)
            ->where('CAPACITACION_INSCRIPCION.ESACTIVO', true)
            ->select(
                'CAPACITACION_INSCRIPCION.*',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPLEADO.CODIGOEMPLEADO',
                'CERT.NOMBRE_ARCHIVO as CERTIFICADO_NOMBRE'
            )
            ->orderBy('CAPACITACION_INSCRIPCION.FECHA_INSCRIPCION')
            ->get()
            ->all();
    }

    public function registrarAsistencia(int $idInscripcion, string $fecha, bool $asistio, ?string $obs = null): void
    {
        $existing = DB::table('CAPACITACION_ASISTENCIA')
            ->where('ID_INSCRIPCION', $idInscripcion)
            ->where('FECHA', $fecha)
            ->first();

        if ($existing) {
            DB::table('CAPACITACION_ASISTENCIA')->where('ID_ASISTENCIA', $existing->ID_ASISTENCIA)->update([
                'ASISTIO' => $asistio,
                'OBSERVACIONES' => $obs,
            ]);
            return;
        }

        $maxId = DB::table('CAPACITACION_ASISTENCIA')->max('ID_ASISTENCIA') ?? 0;
        DB::table('CAPACITACION_ASISTENCIA')->insert([
            'ID_ASISTENCIA' => $maxId + 1,
            'ID_INSCRIPCION' => $idInscripcion,
            'FECHA' => $fecha,
            'ASISTIO' => $asistio,
            'OBSERVACIONES' => $obs,
        ]);
    }

    public function getAsistencias(int $idInscripcion): array
    {
        return DB::table('CAPACITACION_ASISTENCIA')
            ->where('ID_INSCRIPCION', $idInscripcion)
            ->orderBy('FECHA')
            ->get()
            ->all();
    }

    public function completarInscripcion(int $idInscripcion, ?float $calificacion = null, ?int $idAdjunto = null): bool
    {
        return DB::table('CAPACITACION_INSCRIPCION')
            ->where('ID_INSCRIPCION', $idInscripcion)
            ->update([
                'ESTADO' => 'completado',
                'CALIFICACION' => $calificacion,
                'ID_ADJUNTO_CERTIFICADO' => $idAdjunto,
            ]) > 0;
    }

    public function cuposDisponibles(int $idCapacitacion): ?int
    {
        $cap = $this->find($idCapacitacion);
        if (!$cap || !$cap->CUPO_MAX) {
            return null;
        }
        $inscritos = DB::table('CAPACITACION_INSCRIPCION')
            ->where('ID_CAPACITACION', $idCapacitacion)
            ->where('ESACTIVO', true)
            ->count();

        return max(0, $cap->CUPO_MAX - $inscritos);
    }
}
