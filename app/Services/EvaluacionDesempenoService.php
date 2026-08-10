<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EvaluacionDesempenoService
{
    public function __construct(protected NotificationService $notifications) {}

    public function findPeriodo(int $id): ?object
    {
        return DB::table('EVALUACION_PERIODO')->where('ID_PERIODO', $id)->where('ESACTIVO', true)->first();
    }

    public function activarPeriodo(int $id): bool
    {
        return DB::table('EVALUACION_PERIODO')->where('ID_PERIODO', $id)->update(['ESTADO' => 'activo']) > 0;
    }

    public function cerrarPeriodo(int $id): bool
    {
        return DB::table('EVALUACION_PERIODO')->where('ID_PERIODO', $id)->update(['ESTADO' => 'cerrado']) > 0;
    }

    public function crearEvaluaciones(int $idPeriodo, array $asignaciones): array
    {
        $periodo = $this->findPeriodo($idPeriodo);
        if (!$periodo || $periodo->ESTADO !== 'activo') {
            throw new \InvalidArgumentException('El periodo no está activo.');
        }

        $maxId = DB::table('EVALUACION_DESEMPENO')->max('ID_EVALUACION') ?? 0;
        $ids = [];

        foreach ($asignaciones as $a) {
            $exists = DB::table('EVALUACION_DESEMPENO')
                ->where('ID_PERIODO', $idPeriodo)
                ->where('ID_EMPLEADO', $a['ID_EMPLEADO'])
                ->where('ID_EVALUADOR', $a['ID_EVALUADOR'])
                ->where('ESACTIVO', true)
                ->exists();

            if ($exists) {
                continue;
            }

            $maxId++;
            DB::table('EVALUACION_DESEMPENO')->insert([
                'ID_EVALUACION' => $maxId,
                'ID_PERIODO' => $idPeriodo,
                'ID_EMPLEADO' => $a['ID_EMPLEADO'],
                'ID_EVALUADOR' => $a['ID_EVALUADOR'],
                'ESTADO' => 'pendiente',
                'ESACTIVO' => true,
            ]);
            $ids[] = $maxId;

            $this->notifications->notifyByEmpleado(
                (int) $a['ID_EVALUADOR'],
                'Evaluación de desempeño asignada',
                'Se le asignó una evaluación de desempeño pendiente de completar.',
                'info',
                '/evaluaciones'
            );
        }

        return $ids;
    }

    public function getMetas(int $idEvaluacion): array
    {
        return DB::table('EVALUACION_META')
            ->where('ID_EVALUACION', $idEvaluacion)
            ->where('ESACTIVO', true)
            ->orderBy('ID_META')
            ->get()
            ->all();
    }

    public function saveMetas(int $idEvaluacion, array $metas): void
    {
        DB::table('EVALUACION_META')->where('ID_EVALUACION', $idEvaluacion)->update(['ESACTIVO' => false]);

        $maxId = DB::table('EVALUACION_META')->max('ID_META') ?? 0;
        foreach ($metas as $m) {
            $maxId++;
            $obj = $m['VALOR_OBJETIVO'] ?? null;
            $alc = $m['VALOR_ALCANZADO'] ?? null;
            $pct = null;
            if ($obj && $obj > 0 && $alc !== null) {
                $pct = round(min(100, ($alc / $obj) * 100), 1);
            }

            DB::table('EVALUACION_META')->insert([
                'ID_META' => $maxId,
                'ID_EVALUACION' => $idEvaluacion,
                'DESCRIPCION' => $m['DESCRIPCION'],
                'PESO' => $m['PESO'] ?? 1,
                'VALOR_OBJETIVO' => $obj,
                'VALOR_ALCANZADO' => $alc,
                'PORCENTAJE_CUMPLIMIENTO' => $pct,
                'ESACTIVO' => true,
            ]);
        }
    }

    public function completarEvaluacion(int $idEvaluacion, ?float $puntuacion, ?string $comentarios): bool
    {
        $metas = $this->getMetas($idEvaluacion);
        if ($puntuacion === null && count($metas) > 0) {
            $totalPeso = array_sum(array_map(fn ($m) => (float) $m->PESO, $metas));
            if ($totalPeso > 0) {
                $sum = 0;
                foreach ($metas as $m) {
                    $sum += ((float) ($m->PORCENTAJE_CUMPLIMIENTO ?? 0)) * ((float) $m->PESO);
                }
                $puntuacion = round($sum / $totalPeso, 2);
            }
        }

        return DB::table('EVALUACION_DESEMPENO')->where('ID_EVALUACION', $idEvaluacion)->update([
            'ESTADO' => 'completada',
            'PUNTUACION_GLOBAL' => $puntuacion,
            'COMENTARIOS_EVALUADOR' => $comentarios,
            'FECHA_COMPLETADA' => now(),
        ]) > 0;
    }

    public function resultadosPeriodo(int $idPeriodo): array
    {
        $evaluaciones = DB::table('EVALUACION_DESEMPENO')
            ->join('EMPLEADO as EVALUADO', 'EVALUACION_DESEMPENO.ID_EMPLEADO', '=', 'EVALUADO.ID_EMPLEADO')
            ->join('EMPLEADO as EVALUADOR', 'EVALUACION_DESEMPENO.ID_EVALUADOR', '=', 'EVALUADOR.ID_EMPLEADO')
            ->where('EVALUACION_DESEMPENO.ID_PERIODO', $idPeriodo)
            ->where('EVALUACION_DESEMPENO.ESACTIVO', true)
            ->select(
                'EVALUACION_DESEMPENO.*',
                DB::raw("EVALUADO.NOMBRES || ' ' || EVALUADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                DB::raw("EVALUADOR.NOMBRES || ' ' || EVALUADOR.APELLIDO_1 as EVALUADOR_NOMBRE")
            )
            ->get();

        $completadas = $evaluaciones->where('ESTADO', 'completada');
        $promedio = $completadas->avg('PUNTUACION_GLOBAL');

        return [
            'total' => $evaluaciones->count(),
            'completadas' => $completadas->count(),
            'pendientes' => $evaluaciones->where('ESTADO', '!=', 'completada')->count(),
            'promedio_puntuacion' => $promedio ? round($promedio, 2) : null,
            'evaluaciones' => $evaluaciones->all(),
        ];
    }
}
