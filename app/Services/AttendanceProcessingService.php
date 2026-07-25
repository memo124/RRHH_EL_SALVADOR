<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\HorarioDetalle;
use App\Models\MarcacionRaw;
use App\Models\AsistenciaDiaria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceProcessingService
{
    public function __construct(
        protected HorasExtrasCalculatorService $horasExtrasCalculator
    ) {
    }

    /**
     * Procesa marcaciones crudas contra el horario asignado del empleado para un rango de fechas.
     */
    public function processAttendance(Empleado $empleado, Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $horario = $empleado->horario;
            if (!$horario) {
                $currentDate->addDay();
                continue;
            }

            $diaSemana = $currentDate->dayOfWeekIso;
            $detalleHorario = HorarioDetalle::where('ID_HORARIO', $horario->ID_HORARIO)
                ->where('DIA_SEMANA', $diaSemana)
                ->first();

            $esDiaDescanso = !$detalleHorario || $detalleHorario->ES_DIA_DESCANSO;

            $marcaciones = MarcacionRaw::where('ID_EMPLEADO', $empleado->ID_EMPLEADO)
                ->whereDate('FECHA_HORA_MARCACION', $currentDate->toDateString())
                ->orderBy('FECHA_HORA_MARCACION', 'asc')
                ->get();

            $entrada = $marcaciones->where('TIPO_MARCACION', 'ENTRADA')->first();
            $salida = $marcaciones->where('TIPO_MARCACION', 'SALIDA')->first();

            if ($esDiaDescanso) {
                $this->registrarDiaDescanso($empleado, $horario, $currentDate, $entrada, $salida, $detalleHorario);
                $currentDate->addDay();
                continue;
            }

            $minutosTarde = 0;
            $minutosTemprano = 0;
            $horasTrabajadas = 0.00;
            $horasExtrasDiurnas = 0.00;
            $horasExtrasNocturnas = 0.00;

            if ($entrada && $salida) {
                $horaEntradaReal = Carbon::parse($entrada->FECHA_HORA_MARCACION);
                $horaSalidaReal = Carbon::parse($salida->FECHA_HORA_MARCACION);
                $horaEntradaProgramada = Carbon::parse($currentDate->toDateString() . ' ' . $detalleHorario->HORA_ENTRADA);
                $horaSalidaProgramada = Carbon::parse($currentDate->toDateString() . ' ' . $detalleHorario->HORA_SALIDA);

                $diferenciaEntrada = $horaEntradaProgramada->diffInMinutes($horaEntradaReal, false);
                if ($diferenciaEntrada > $horario->TOLERANCIA_ENTRADA_MINUTOS) {
                    $minutosTarde = $diferenciaEntrada;
                }

                $diferenciaSalida = $horaSalidaReal->diffInMinutes($horaSalidaProgramada, false);
                if ($diferenciaSalida > $horario->TOLERANCIA_SALIDA_MINUTOS) {
                    $minutosTemprano = $diferenciaSalida;
                }

                $minutosTrabajados = $horaEntradaReal->diffInMinutes($horaSalidaReal);
                $minutosNetos = max(0, $minutosTrabajados - $detalleHorario->TIEMPO_ALMUERZO_MINUTOS);
                $horasTrabajadas = round($minutosNetos / 60, 2);

                $duracionProgramadaMinutos = Carbon::parse($detalleHorario->HORA_ENTRADA)
                    ->diffInMinutes(Carbon::parse($detalleHorario->HORA_SALIDA)) - $detalleHorario->TIEMPO_ALMUERZO_MINUTOS;

                if ($minutosNetos > $duracionProgramadaMinutos) {
                    $inicioExtra = $horaSalidaProgramada->copy();
                    if ($horaSalidaProgramada->lt($horaEntradaReal)) {
                        $inicioExtra = $horaEntradaReal->copy()->addMinutes($duracionProgramadaMinutos + $detalleHorario->TIEMPO_ALMUERZO_MINUTOS);
                    }

                    $clasificacion = $this->horasExtrasCalculator->clasificarMinutosPorJornada($inicioExtra, $horaSalidaReal);
                    $horasExtrasDiurnas = $clasificacion['diurnas'];
                    $horasExtrasNocturnas = $clasificacion['nocturnas'];
                }
            }

            AsistenciaDiaria::updateOrCreate(
                ['ID_EMPLEADO' => $empleado->ID_EMPLEADO, 'FECHA' => $currentDate->toDateString()],
                [
                    'ID_HORARIO' => $horario->ID_HORARIO,
                    'HORA_ENTRADA_PROGRAMADA' => $detalleHorario->HORA_ENTRADA,
                    'HORA_SALIDA_PROGRAMADA' => $detalleHorario->HORA_SALIDA,
                    'HORA_ENTRADA_REAL' => $entrada ? $entrada->FECHA_HORA_MARCACION : null,
                    'HORA_SALIDA_REAL' => $salida ? $salida->FECHA_HORA_MARCACION : null,
                    'MINUTOS_LLEGADA_TARDE' => $minutosTarde,
                    'MINUTOS_SALIDA_TEMPRANO' => $minutosTemprano,
                    'HORAS_TRABAJADAS' => $horasTrabajadas,
                    'HORAS_EXTRAS_DIURNAS' => $horasExtrasDiurnas,
                    'HORAS_EXTRAS_NOCTURNAS' => $horasExtrasNocturnas,
                    'ES_DIA_DESCANSO' => false,
                    'HORAS_EXTRAS_FIJAS_DIURNAS' => 0,
                    'HORAS_EXTRAS_ADICIONALES_DIURNAS' => 0,
                    'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 0,
                    'HORAS_EXTRAS_ADICIONALES_NOCTURNAS' => 0,
                    'ES_INASISTENCIA' => !$entrada,
                    'OBSERVACIONES' => $entrada ? 'Asistencia Normal' : 'Ausencia sin Justificar',
                ]
            );

            $this->marcarProcesadas($entrada, $salida);
            $currentDate->addDay();
        }
    }

    protected function registrarDiaDescanso(
        Empleado $empleado,
        $horario,
        Carbon $currentDate,
        $entrada,
        $salida,
        $detalleHorario
    ): void {
        $horasTrabajadas = 0.00;
        $horasExtrasDiurnas = 0.00;
        $horasExtrasNocturnas = 0.00;

        if ($entrada && $salida) {
            $horaEntradaReal = Carbon::parse($entrada->FECHA_HORA_MARCACION);
            $horaSalidaReal = Carbon::parse($salida->FECHA_HORA_MARCACION);
            $minutosTrabajados = $horaEntradaReal->diffInMinutes($horaSalidaReal);
            $horasTrabajadas = round($minutosTrabajados / 60, 2);

            $clasificacion = $this->horasExtrasCalculator->clasificarMinutosPorJornada($horaEntradaReal, $horaSalidaReal);
            $horasExtrasDiurnas = $clasificacion['diurnas'];
            $horasExtrasNocturnas = $clasificacion['nocturnas'];
        }

        AsistenciaDiaria::updateOrCreate(
            ['ID_EMPLEADO' => $empleado->ID_EMPLEADO, 'FECHA' => $currentDate->toDateString()],
            [
                'ID_HORARIO' => $horario->ID_HORARIO,
                'HORA_ENTRADA_PROGRAMADA' => $detalleHorario->HORA_ENTRADA ?? null,
                'HORA_SALIDA_PROGRAMADA' => $detalleHorario->HORA_SALIDA ?? null,
                'HORA_ENTRADA_REAL' => $entrada ? $entrada->FECHA_HORA_MARCACION : null,
                'HORA_SALIDA_REAL' => $salida ? $salida->FECHA_HORA_MARCACION : null,
                'MINUTOS_LLEGADA_TARDE' => 0,
                'MINUTOS_SALIDA_TEMPRANO' => 0,
                'HORAS_TRABAJADAS' => $horasTrabajadas,
                'HORAS_EXTRAS_DIURNAS' => $horasExtrasDiurnas,
                'HORAS_EXTRAS_NOCTURNAS' => $horasExtrasNocturnas,
                'ES_DIA_DESCANSO' => true,
                'HORAS_EXTRAS_FIJAS_DIURNAS' => 0,
                'HORAS_EXTRAS_ADICIONALES_DIURNAS' => 0,
                'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 0,
                'HORAS_EXTRAS_ADICIONALES_NOCTURNAS' => 0,
                'ES_INASISTENCIA' => !$entrada,
                'OBSERVACIONES' => $entrada ? 'Laboró en día de descanso' : 'Día de Descanso / Libre',
            ]
        );

        $this->marcarProcesadas($entrada, $salida);
    }

    protected function marcarProcesadas($entrada, $salida): void
    {
        if ($entrada) {
            MarcacionRaw::where('ID_MARCACION', $entrada->ID_MARCACION)->update(['PROCESADO' => true]);
        }
        if ($salida) {
            MarcacionRaw::where('ID_MARCACION', $salida->ID_MARCACION)->update(['PROCESADO' => true]);
        }
    }
}
