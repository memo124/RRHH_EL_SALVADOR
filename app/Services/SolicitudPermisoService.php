<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SolicitudPermisoService
{
    public function __construct(
        protected CalendarioEventoService $calendario,
        protected VacacionesPlanillaService $vacacionesPlanilla,
        protected NotificationService $notifications,
    ) {}

    public function calcularDias(string $fechaInicio, string $fechaFin): float
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();
        if ($fin->lt($inicio)) {
            return 0;
        }
        return (float) ($inicio->diffInDays($fin) + 1);
    }

    public function getOrCreateSaldo(int $idEmpleado, ?int $anio = null): object
    {
        $anio = $anio ?? (int) date('Y');
        $saldo = DB::table('EMPLEADO_SALDO_VACACIONES')
            ->where('ID_EMPLEADO', $idEmpleado)
            ->where('ANIO', $anio)
            ->where('ESACTIVO', true)
            ->first();

        if ($saldo) {
            return $saldo;
        }

        $maxId = DB::table('EMPLEADO_SALDO_VACACIONES')->max('ID_SALDO') ?? 0;
        $diasAsignados = $this->calcularDiasAsignados($idEmpleado, $anio);

        DB::table('EMPLEADO_SALDO_VACACIONES')->insert([
            'ID_SALDO' => $maxId + 1,
            'ID_EMPLEADO' => $idEmpleado,
            'ANIO' => $anio,
            'DIAS_ASIGNADOS' => $diasAsignados,
            'DIAS_USADOS' => 0,
            'ESACTIVO' => true,
        ]);

        return DB::table('EMPLEADO_SALDO_VACACIONES')
            ->where('ID_EMPLEADO', $idEmpleado)
            ->where('ANIO', $anio)
            ->first();
    }

    public function saldoDisponible(int $idEmpleado, ?int $anio = null): array
    {
        $saldo = $this->getOrCreateSaldo($idEmpleado, $anio);
        $pendientes = DB::table('SOLICITUD_PERMISO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->where('SOLICITUD_PERMISO.ID_EMPLEADO', $idEmpleado)
            ->where('SOLICITUD_PERMISO.ESTADO', 'pendiente')
            ->where('TIPO_PERMISO_LABORAL.DESCUENTA_SALDO_VACACIONES', true)
            ->where('SOLICITUD_PERMISO.ESACTIVO', true)
            ->sum('SOLICITUD_PERMISO.DIAS_SOLICITADOS');

        $asignados = (float) $saldo->DIAS_ASIGNADOS;
        $usados = (float) $saldo->DIAS_USADOS;
        $disponible = $asignados - $usados - (float) $pendientes;

        return [
            'ANIO' => $saldo->ANIO,
            'DIAS_ASIGNADOS' => $asignados,
            'DIAS_USADOS' => $usados,
            'DIAS_PENDIENTES_APROBACION' => (float) $pendientes,
            'DIAS_DISPONIBLES' => max(0, $disponible),
        ];
    }

    private function calcularDiasAsignados(int $idEmpleado, int $anio): float
    {
        $emp = DB::table('EMPLEADO')->where('ID_EMPLEADO', $idEmpleado)->first();
        if (!$emp) {
            return 15;
        }

        $ingreso = Carbon::parse($emp->FECHAINGRESO);
        if ($ingreso->year > $anio) {
            return 0;
        }

        // 15 días anuales estándar El Salvador (simplificado)
        if ($ingreso->year === $anio) {
            $mesesTrabajados = max(0, 12 - $ingreso->month + 1);
            return round(15 * ($mesesTrabajados / 12), 1);
        }

        return 15;
    }

    public function crearSolicitud(array $data, ?int $idUsuario): int
    {
        $dias = $this->calcularDias($data['FECHA_INICIO'], $data['FECHA_FIN']);
        $tipo = DB::table('TIPO_PERMISO_LABORAL')->where('ID_TIPO_PERMISO', $data['ID_TIPO_PERMISO'])->first();

        if ($tipo && $tipo->DESCUENTA_SALDO_VACACIONES) {
            $anio = (int) Carbon::parse($data['FECHA_INICIO'])->year;
            $saldo = $this->saldoDisponible($data['ID_EMPLEADO'], $anio);
            if ($dias > $saldo['DIAS_DISPONIBLES']) {
                throw new \InvalidArgumentException(
                    "Saldo insuficiente. Disponible: {$saldo['DIAS_DISPONIBLES']} días."
                );
            }
        }

        $maxId = DB::table('SOLICITUD_PERMISO')->max('ID_SOLICITUD') ?? 0;
        $id = $maxId + 1;

        DB::table('SOLICITUD_PERMISO')->insert([
            'ID_SOLICITUD' => $id,
            'ID_EMPLEADO' => $data['ID_EMPLEADO'],
            'ID_TIPO_PERMISO' => $data['ID_TIPO_PERMISO'],
            'FECHA_INICIO' => $data['FECHA_INICIO'],
            'FECHA_FIN' => $data['FECHA_FIN'],
            'DIAS_SOLICITADOS' => $dias,
            'MOTIVO' => $data['MOTIVO'] ?? null,
            'ESTADO' => 'pendiente',
            'ID_USUARIO_SOLICITA' => $idUsuario,
            'FECHA_SOLICITUD' => now(),
            'ESACTIVO' => true,
        ]);

        return $id;
    }

    public function aprobar(int $idSolicitud, int $idUsuario): array
    {
        $sol = DB::table('SOLICITUD_PERMISO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->where('SOLICITUD_PERMISO.ID_SOLICITUD', $idSolicitud)
            ->where('SOLICITUD_PERMISO.ESTADO', 'pendiente')
            ->select('SOLICITUD_PERMISO.*', 'TIPO_PERMISO_LABORAL.NOMBRE as TIPO_NOMBRE', 'TIPO_PERMISO_LABORAL.DESCUENTA_SALDO_VACACIONES')
            ->first();

        if (!$sol) {
            return ['ok' => false];
        }

        DB::transaction(function () use ($sol, $idSolicitud, $idUsuario) {
            DB::table('SOLICITUD_PERMISO')->where('ID_SOLICITUD', $idSolicitud)->update([
                'ESTADO' => 'aprobada',
                'ID_USUARIO_APRUEBA' => $idUsuario,
                'FECHA_REVISION' => now(),
            ]);

            if ($sol->DESCUENTA_SALDO_VACACIONES) {
                $anio = (int) Carbon::parse($sol->FECHA_INICIO)->year;
                $saldo = $this->getOrCreateSaldo($sol->ID_EMPLEADO, $anio);
                DB::table('EMPLEADO_SALDO_VACACIONES')
                    ->where('ID_SALDO', $saldo->ID_SALDO)
                    ->update(['DIAS_USADOS' => (float) $saldo->DIAS_USADOS + (float) $sol->DIAS_SOLICITADOS]);
            }

            $emp = DB::table('EMPLEADO')->where('ID_EMPLEADO', $sol->ID_EMPLEADO)->first();
            $nombre = trim(($emp->NOMBRES ?? '') . ' ' . ($emp->APELLIDO_1 ?? ''));

            $this->calendario->syncPermisoEvento(
                $idSolicitud,
                "{$sol->TIPO_NOMBRE}: {$nombre}",
                $sol->FECHA_INICIO,
                $sol->FECHA_FIN,
                $sol->ID_EMPLEADO,
                $emp->ID_EMPRESA ?? null,
                $emp->ID_DEPARTAMENTO ?? null
            );
        });

        $planilla = null;
        $planillaError = null;

        if ($sol->DESCUENTA_SALDO_VACACIONES) {
            try {
                $planilla = $this->vacacionesPlanilla->integrarSolicitud($idSolicitud);
            } catch (\Throwable $e) {
                $planillaError = $e->getMessage();
            }
        }

        $this->notifications->notifyByEmpleado(
            $sol->ID_EMPLEADO,
            'Permiso aprobado',
            "Su solicitud de {$sol->TIPO_NOMBRE} del " . Carbon::parse($sol->FECHA_INICIO)->format('d/m/Y') . ' fue aprobada.',
            'success',
            '/vacaciones-permisos'
        );

        return [
            'ok' => true,
            'planilla' => $planilla,
            'planilla_error' => $planillaError,
        ];
    }

    public function rechazar(int $idSolicitud, int $idUsuario, string $motivo): bool
    {
        $sol = DB::table('SOLICITUD_PERMISO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->where('SOLICITUD_PERMISO.ID_SOLICITUD', $idSolicitud)
            ->where('SOLICITUD_PERMISO.ESTADO', 'pendiente')
            ->select('SOLICITUD_PERMISO.ID_EMPLEADO', 'TIPO_PERMISO_LABORAL.NOMBRE as TIPO_NOMBRE')
            ->first();

        $actualizado = DB::table('SOLICITUD_PERMISO')
            ->where('ID_SOLICITUD', $idSolicitud)
            ->where('ESTADO', 'pendiente')
            ->update([
                'ESTADO' => 'rechazada',
                'ID_USUARIO_APRUEBA' => $idUsuario,
                'FECHA_REVISION' => now(),
                'MOTIVO_RECHAZO' => $motivo,
            ]) > 0;

        if ($actualizado && $sol) {
            $this->notifications->notifyByEmpleado(
                $sol->ID_EMPLEADO,
                'Permiso rechazado',
                "Su solicitud de {$sol->TIPO_NOMBRE} fue rechazada. Motivo: {$motivo}",
                'warning',
                '/vacaciones-permisos'
            );
        }

        return $actualizado;
    }

    public function cancelar(int $idSolicitud): bool
    {
        return DB::table('SOLICITUD_PERMISO')
            ->where('ID_SOLICITUD', $idSolicitud)
            ->where('ESTADO', 'pendiente')
            ->update(['ESTADO' => 'cancelada']) > 0;
    }

    public function tiposPermiso(): array
    {
        return DB::table('TIPO_PERMISO_LABORAL')
            ->where('ESACTIVO', true)
            ->orderBy('ID_TIPO_PERMISO')
            ->get()
            ->all();
    }
}
