<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CalendarioEventoService
{
    private const COLORES = [
        'capacitacion' => '#3b82f6',
        'reunion_rrhh' => '#8b5cf6',
        'vencimiento_contrato' => '#ef4444',
        'encuesta' => '#f59e0b',
        'formulario' => '#10b981',
        'feriado' => '#6366f1',
        'permiso' => '#14b8a6',
        'manual' => '#64748b',
    ];

    public function listForRange(?string $start, ?string $end, array $filters = []): array
    {
        $query = DB::table('CALENDARIO_EVENTO')
            ->leftJoin('EMPLEADO', 'CALENDARIO_EVENTO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->leftJoin('EMPRESA', 'CALENDARIO_EVENTO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->where('CALENDARIO_EVENTO.ESACTIVO', true)
            ->select(
                'CALENDARIO_EVENTO.*',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE"),
                'EMPRESA.NOMBREEMPRESA'
            );

        if ($start) {
            $query->where('CALENDARIO_EVENTO.FECHA_INICIO', '>=', $start);
        }
        if ($end) {
            $query->where(function ($q) use ($end) {
                $q->where('CALENDARIO_EVENTO.FECHA_FIN', '<=', $end)
                    ->orWhereNull('CALENDARIO_EVENTO.FECHA_FIN')
                    ->where('CALENDARIO_EVENTO.FECHA_INICIO', '<=', $end);
            });
        }
        if (!empty($filters['tipo'])) {
            $query->where('CALENDARIO_EVENTO.TIPO', $filters['tipo']);
        }
        if (!empty($filters['ID_EMPRESA'])) {
            $query->where('CALENDARIO_EVENTO.ID_EMPRESA', (int) $filters['ID_EMPRESA']);
        }
        if (!empty($filters['ID_DEPARTAMENTO'])) {
            $query->where('CALENDARIO_EVENTO.ID_DEPARTAMENTO', (int) $filters['ID_DEPARTAMENTO']);
        }

        return $query->orderBy('CALENDARIO_EVENTO.FECHA_INICIO')->get()->map(function ($e) {
            return $this->toFullCalendarEvent($e);
        })->all();
    }

    public function create(array $data, ?int $idUsuario): array
    {
        $maxId = DB::table('CALENDARIO_EVENTO')->max('ID_EVENTO') ?? 0;
        $id = $maxId + 1;
        $tipo = $data['TIPO'] ?? 'manual';
        $row = [
            'ID_EVENTO' => $id,
            'TIPO' => $tipo,
            'TITULO' => $data['TITULO'],
            'DESCRIPCION' => $data['DESCRIPCION'] ?? null,
            'FECHA_INICIO' => $data['FECHA_INICIO'],
            'FECHA_FIN' => $data['FECHA_FIN'] ?? null,
            'TODO_DIA' => $data['TODO_DIA'] ?? false,
            'COLOR' => $data['COLOR'] ?? (self::COLORES[$tipo] ?? '#64748b'),
            'ID_EMPLEADO' => $data['ID_EMPLEADO'] ?? null,
            'ID_EMPRESA' => $data['ID_EMPRESA'] ?? null,
            'ID_DEPARTAMENTO' => $data['ID_DEPARTAMENTO'] ?? null,
            'ORIGEN_TIPO' => $data['ORIGEN_TIPO'] ?? null,
            'ORIGEN_ID' => $data['ORIGEN_ID'] ?? null,
            'ID_USUARIO_CREACION' => $idUsuario,
            'ESACTIVO' => true,
        ];
        DB::table('CALENDARIO_EVENTO')->insert($row);

        return $row;
    }

    public function update(int $id, array $data): ?array
    {
        $exists = DB::table('CALENDARIO_EVENTO')->where('ID_EVENTO', $id)->where('ESACTIVO', true)->exists();
        if (!$exists) {
            return null;
        }

        $allowed = ['TIPO', 'TITULO', 'DESCRIPCION', 'FECHA_INICIO', 'FECHA_FIN', 'TODO_DIA', 'COLOR',
            'ID_EMPLEADO', 'ID_EMPRESA', 'ID_DEPARTAMENTO'];
        $update = array_intersect_key($data, array_flip($allowed));
        if ($update !== []) {
            DB::table('CALENDARIO_EVENTO')->where('ID_EVENTO', $id)->update($update);
        }

        return (array) DB::table('CALENDARIO_EVENTO')->where('ID_EVENTO', $id)->first();
    }

    public function softDelete(int $id): bool
    {
        return DB::table('CALENDARIO_EVENTO')->where('ID_EVENTO', $id)->update(['ESACTIVO' => false]) > 0;
    }

    public function syncFromOrigen(string $origenTipo, int $origenId, array $data): void
    {
        $existing = DB::table('CALENDARIO_EVENTO')
            ->where('ORIGEN_TIPO', $origenTipo)
            ->where('ORIGEN_ID', $origenId)
            ->where('ESACTIVO', true)
            ->first();

        if ($existing) {
            $this->update($existing->ID_EVENTO, $data);
            return;
        }

        $data['ORIGEN_TIPO'] = $origenTipo;
        $data['ORIGEN_ID'] = $origenId;
        $this->create($data, null);
    }

    public function syncCampanaEvento(int $idCampana, string $nombre, ?string $fechaFin): void
    {
        if (!$fechaFin) {
            return;
        }
        $this->syncFromOrigen('formulario_campana', $idCampana, [
            'TIPO' => 'formulario',
            'TITULO' => "Vence: {$nombre}",
            'DESCRIPCION' => 'Fecha límite de campaña de actualización de datos',
            'FECHA_INICIO' => $fechaFin,
            'FECHA_FIN' => $fechaFin,
            'TODO_DIA' => true,
        ]);
    }

    public function syncEncuestaEvento(int $idEncuesta, string $titulo, ?string $fechaInicio, ?string $fechaFin): void
    {
        if (!$fechaInicio) {
            return;
        }
        $this->syncFromOrigen('encuesta', $idEncuesta, [
            'TIPO' => 'encuesta',
            'TITULO' => $titulo,
            'DESCRIPCION' => 'Encuesta activa',
            'FECHA_INICIO' => $fechaInicio,
            'FECHA_FIN' => $fechaFin,
            'TODO_DIA' => false,
        ]);
    }

    public function syncPermisoEvento(
        int $idSolicitud,
        string $titulo,
        string $fechaInicio,
        string $fechaFin,
        int $idEmpleado,
        ?int $idEmpresa,
        ?int $idDepartamento
    ): void {
        $this->syncFromOrigen('solicitud_permiso', $idSolicitud, [
            'TIPO' => 'permiso',
            'TITULO' => $titulo,
            'DESCRIPCION' => 'Permiso / vacación aprobada',
            'FECHA_INICIO' => $fechaInicio,
            'FECHA_FIN' => $fechaFin,
            'TODO_DIA' => true,
            'ID_EMPLEADO' => $idEmpleado,
            'ID_EMPRESA' => $idEmpresa,
            'ID_DEPARTAMENTO' => $idDepartamento,
        ]);
    }

    public function syncCapacitacionEvento(
        int $idCapacitacion,
        string $titulo,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?int $idEmpresa,
        ?string $lugar
    ): void {
        if (!$fechaInicio) {
            return;
        }
        $this->syncFromOrigen('capacitacion', $idCapacitacion, [
            'TIPO' => 'capacitacion',
            'TITULO' => $titulo,
            'DESCRIPCION' => $lugar ? "Lugar: {$lugar}" : 'Capacitación programada',
            'FECHA_INICIO' => $fechaInicio,
            'FECHA_FIN' => $fechaFin,
            'TODO_DIA' => false,
            'ID_EMPRESA' => $idEmpresa,
        ]);
    }

    private function toFullCalendarEvent(object $e): array
    {
        $route = null;
        if ($e->ORIGEN_TIPO === 'encuesta' && $e->ORIGEN_ID) {
            $route = '/encuestas';
        } elseif ($e->ORIGEN_TIPO === 'formulario_campana' && $e->ORIGEN_ID) {
            $route = '/formularios-empleado';
        } elseif ($e->ORIGEN_TIPO === 'solicitud_permiso' && $e->ORIGEN_ID) {
            $route = '/vacaciones-permisos';
        } elseif ($e->ORIGEN_TIPO === 'capacitacion' && $e->ORIGEN_ID) {
            $route = '/capacitaciones';
        } elseif ($e->ORIGEN_TIPO === 'entrevista' && $e->ORIGEN_ID) {
            $route = '/reclutamiento';
        }

        return [
            'id' => (string) $e->ID_EVENTO,
            'title' => $e->TITULO,
            'start' => $e->FECHA_INICIO,
            'end' => $e->FECHA_FIN,
            'allDay' => (bool) $e->TODO_DIA,
            'backgroundColor' => $e->COLOR,
            'borderColor' => $e->COLOR,
            'extendedProps' => [
                'tipo' => $e->TIPO,
                'descripcion' => $e->DESCRIPCION,
                'empleadoNombre' => $e->EMPLEADO_NOMBRE ?? null,
                'empresa' => $e->NOMBREEMPRESA ?? null,
                'origenTipo' => $e->ORIGEN_TIPO,
                'origenId' => $e->ORIGEN_ID,
                'route' => $route,
            ],
        ];
    }

    public static function nextId(): int
    {
        return (DB::table('CALENDARIO_EVENTO')->max('ID_EVENTO') ?? 0) + 1;
    }
}
