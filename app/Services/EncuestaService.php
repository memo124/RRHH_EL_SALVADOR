<?php

namespace App\Services;

use App\Jobs\EnviarRecordatoriosEncuestaJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EncuestaService
{
    public function __construct(
        protected CalendarioEventoService $calendario,
        protected NotificationService $notifications,
    ) {}

    public function find(int $id): ?object
    {
        return DB::table('ENCUESTA')->where('ID_ENCUESTA', $id)->where('ESACTIVO', true)->first();
    }

    public function getPreguntas(int $idEncuesta): array
    {
        return DB::table('ENCUESTA_PREGUNTA')
            ->where('ID_ENCUESTA', $idEncuesta)
            ->where('ESACTIVO', true)
            ->orderBy('ORDEN')
            ->get()
            ->map(function ($p) {
                $p->OPCIONES = $p->OPCIONES ? json_decode($p->OPCIONES, true) : null;
                return $p;
            })
            ->all();
    }

    public function savePreguntas(int $idEncuesta, array $preguntas): void
    {
        DB::table('ENCUESTA_PREGUNTA')->where('ID_ENCUESTA', $idEncuesta)->update(['ESACTIVO' => false]);

        $maxId = DB::table('ENCUESTA_PREGUNTA')->max('ID_PREGUNTA') ?? 0;
        foreach ($preguntas as $i => $p) {
            $maxId++;
            DB::table('ENCUESTA_PREGUNTA')->insert([
                'ID_PREGUNTA' => $maxId,
                'ID_ENCUESTA' => $idEncuesta,
                'ORDEN' => $p['ORDEN'] ?? ($i + 1),
                'TIPO' => $p['TIPO'],
                'ENUNCIADO' => $p['ENUNCIADO'],
                'OPCIONES' => isset($p['OPCIONES']) ? json_encode($p['OPCIONES']) : null,
                'REQUERIDA' => $p['REQUERIDA'] ?? true,
                'ESACTIVO' => true,
            ]);
        }
    }

    public function saveAsignaciones(int $idEncuesta, array $asignaciones): void
    {
        DB::table('ENCUESTA_ASIGNACION')->where('ID_ENCUESTA', $idEncuesta)->update(['ESACTIVO' => false]);

        $maxId = DB::table('ENCUESTA_ASIGNACION')->max('ID_ASIGNACION') ?? 0;
        foreach ($asignaciones as $a) {
            $maxId++;
            DB::table('ENCUESTA_ASIGNACION')->insert([
                'ID_ASIGNACION' => $maxId,
                'ID_ENCUESTA' => $idEncuesta,
                'TIPO_AUDIENCIA' => $a['TIPO_AUDIENCIA'],
                'ID_REFERENCIA' => $a['ID_REFERENCIA'] ?? null,
                'ESACTIVO' => true,
            ]);
        }
    }

    public function publicar(int $idEncuesta): bool
    {
        $enc = $this->find($idEncuesta);
        if (!$enc) {
            return false;
        }

        DB::table('ENCUESTA')->where('ID_ENCUESTA', $idEncuesta)->update(['ESTADO' => 'publicada']);

        $this->calendario->syncEncuestaEvento(
            $idEncuesta,
            $enc->TITULO,
            $enc->FECHA_INICIO,
            $enc->FECHA_FIN
        );

        if ($enc->ENVIAR_RECORDATORIOS) {
            try {
                EnviarRecordatoriosEncuestaJob::dispatch($idEncuesta);
            } catch (Throwable $e) {
                Log::warning('No se pudo encolar recordatorios de encuesta', [
                    'ID_ENCUESTA' => $idEncuesta,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($this->empleadosAsignados($idEncuesta) as $idEmpleado) {
            $this->notifications->notifyByEmpleado(
                (int) $idEmpleado,
                'Nueva encuesta disponible',
                "La encuesta \"{$enc->TITULO}\" está disponible para su participación.",
                'info',
                '/encuestas'
            );
        }

        return true;
    }

    public function cerrar(int $idEncuesta): bool
    {
        return DB::table('ENCUESTA')->where('ID_ENCUESTA', $idEncuesta)->update(['ESTADO' => 'cerrada']) > 0;
    }

    public function empleadosAsignados(int $idEncuesta): array
    {
        $asignaciones = DB::table('ENCUESTA_ASIGNACION')
            ->where('ID_ENCUESTA', $idEncuesta)
            ->where('ESACTIVO', true)
            ->get();

        $empleadoIds = collect();

        foreach ($asignaciones as $a) {
            $query = DB::table('EMPLEADO')->where('ESACTIVO', true);
            match ($a->TIPO_AUDIENCIA) {
                'todos' => null,
                'empresa' => $query->where('ID_EMPRESA', $a->ID_REFERENCIA),
                'departamento' => $query->where('ID_DEPARTAMENTO', $a->ID_REFERENCIA),
                'cargo' => $query->where('ID_CARGO', $a->ID_REFERENCIA),
                'empleado' => $query->where('ID_EMPLEADO', $a->ID_REFERENCIA),
                default => $query->whereRaw('1=0'),
            };
            $empleadoIds = $empleadoIds->merge($query->pluck('ID_EMPLEADO'));
        }

        return $empleadoIds->unique()->values()->all();
    }

    /** Empleados de la audiencia que aún no respondieron (con email). */
    public function empleadosPendientesRespuesta(int $idEncuesta): array
    {
        $ids = $this->empleadosAsignados($idEncuesta);
        if ($ids === []) {
            return [];
        }

        $respondieron = DB::table('ENCUESTA_RESPUESTA')
            ->where('ID_ENCUESTA', $idEncuesta)
            ->where('ESACTIVO', true)
            ->whereNotNull('ID_EMPLEADO')
            ->pluck('ID_EMPLEADO')
            ->all();

        $pendientes = array_values(array_diff($ids, $respondieron));
        if ($pendientes === []) {
            return [];
        }

        return DB::table('EMPLEADO')
            ->whereIn('ID_EMPLEADO', $pendientes)
            ->where('ESACTIVO', true)
            ->select('ID_EMPLEADO', 'NOMBRES', 'APELLIDO_1', 'CORREOELECTRONICO')
            ->get()
            ->all();
    }

    public function encuestasParaEmpleado(?int $idEmpleado): array
    {
        if (!$idEmpleado) {
            return [];
        }

        $emp = DB::table('EMPLEADO')->where('ID_EMPLEADO', $idEmpleado)->first();
        if (!$emp) {
            return [];
        }

        $encuestas = DB::table('ENCUESTA')
            ->where('ESACTIVO', true)
            ->where('ESTADO', 'publicada')
            ->where(function ($q) {
                $q->whereNull('FECHA_FIN')->orWhere('FECHA_FIN', '>=', now());
            })
            ->get();

        return $encuestas->filter(function ($enc) use ($emp, $idEmpleado) {
            $asignaciones = DB::table('ENCUESTA_ASIGNACION')
                ->where('ID_ENCUESTA', $enc->ID_ENCUESTA)
                ->where('ESACTIVO', true)
                ->get();

            foreach ($asignaciones as $a) {
                $match = match ($a->TIPO_AUDIENCIA) {
                    'todos' => true,
                    'empresa' => (int) $a->ID_REFERENCIA === (int) $emp->ID_EMPRESA,
                    'departamento' => (int) $a->ID_REFERENCIA === (int) $emp->ID_DEPARTAMENTO,
                    'cargo' => (int) $a->ID_REFERENCIA === (int) $emp->ID_CARGO,
                    'empleado' => (int) $a->ID_REFERENCIA === $idEmpleado,
                    default => false,
                };
                if ($match) {
                    return true;
                }
            }
            return false;
        })->values()->all();
    }

    public function yaRespondio(int $idEncuesta, int $idEmpleado): bool
    {
        return DB::table('ENCUESTA_RESPUESTA')
            ->where('ID_ENCUESTA', $idEncuesta)
            ->where('ID_EMPLEADO', $idEmpleado)
            ->where('ESACTIVO', true)
            ->exists();
    }

    public function guardarRespuesta(int $idEncuesta, ?int $idEmpleado, array $detalles): int
    {
        $maxId = DB::table('ENCUESTA_RESPUESTA')->max('ID_RESPUESTA') ?? 0;
        $idRespuesta = $maxId + 1;

        DB::table('ENCUESTA_RESPUESTA')->insert([
            'ID_RESPUESTA' => $idRespuesta,
            'ID_ENCUESTA' => $idEncuesta,
            'ID_EMPLEADO' => $idEmpleado,
            'FECHA_RESPUESTA' => now(),
            'ESACTIVO' => true,
        ]);

        $maxDet = DB::table('ENCUESTA_RESPUESTA_DETALLE')->max('ID_DETALLE') ?? 0;
        foreach ($detalles as $d) {
            $maxDet++;
            DB::table('ENCUESTA_RESPUESTA_DETALLE')->insert([
                'ID_DETALLE' => $maxDet,
                'ID_RESPUESTA' => $idRespuesta,
                'ID_PREGUNTA' => $d['ID_PREGUNTA'],
                'VALOR_TEXTO' => $d['VALOR_TEXTO'] ?? null,
                'VALOR_OPCION' => $d['VALOR_OPCION'] ?? null,
                'ID_ADJUNTO' => $d['ID_ADJUNTO'] ?? null,
            ]);
        }

        return $idRespuesta;
    }

    public function resultados(int $idEncuesta): array
    {
        $enc = $this->find($idEncuesta);
        if (!$enc) {
            return ['total_respuestas' => 0, 'preguntas' => []];
        }

        $preguntas = $this->getPreguntas($idEncuesta);
        $totalRespuestas = DB::table('ENCUESTA_RESPUESTA')
            ->where('ID_ENCUESTA', $idEncuesta)
            ->where('ESACTIVO', true)
            ->count();

        $asignados = count($this->empleadosAsignados($idEncuesta));

        $resultados = [];
        foreach ($preguntas as $p) {
            $detalles = DB::table('ENCUESTA_RESPUESTA_DETALLE')
                ->join('ENCUESTA_RESPUESTA', 'ENCUESTA_RESPUESTA_DETALLE.ID_RESPUESTA', '=', 'ENCUESTA_RESPUESTA.ID_RESPUESTA')
                ->where('ENCUESTA_RESPUESTA.ID_ENCUESTA', $idEncuesta)
                ->where('ENCUESTA_RESPUESTA_DETALLE.ID_PREGUNTA', $p->ID_PREGUNTA)
                ->where('ENCUESTA_RESPUESTA.ESACTIVO', true)
                ->select('ENCUESTA_RESPUESTA_DETALLE.*')
                ->get();

            $item = [
                'pregunta' => $p,
                'total' => $detalles->count(),
                'distribucion' => [],
            ];

            if (in_array($p->TIPO, ['opcion_multiple', 'likert', 'si_no'], true)) {
                $counts = [];
                foreach ($detalles as $d) {
                    $val = $d->VALOR_OPCION ?? 'sin_respuesta';
                    $counts[$val] = ($counts[$val] ?? 0) + 1;
                }
                foreach ($counts as $opcion => $count) {
                    $item['distribucion'][] = [
                        'opcion' => $opcion,
                        'count' => $count,
                        'porcentaje' => $detalles->count() > 0 ? round($count / $detalles->count() * 100, 1) : 0,
                    ];
                }
            } else {
                $item['respuestas'] = $detalles->pluck('VALOR_TEXTO')->filter()->values()->all();
            }

            $resultados[] = $item;
        }

        return [
            'anonima' => (bool) $enc->ANONIMA,
            'total_respuestas' => $totalRespuestas,
            'participacion' => [
                'asignados' => $asignados,
                'respondieron' => $totalRespuestas,
                'porcentaje' => $asignados > 0 ? round($totalRespuestas / $asignados * 100, 1) : null,
            ],
            'preguntas' => $resultados,
        ];
    }
}
