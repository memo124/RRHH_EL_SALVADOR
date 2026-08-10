<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Planilla;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VacacionesPlanillaService
{
    public function __construct(
        protected PayrollCalculatorService $calculator,
        protected PayrollPostingService $posting,
    ) {}

    /**
     * Integra una solicitud de vacaciones aprobada en planilla tipo Vacaciones.
     *
     * @return array{ID_PLANILLA: int, TITULO: string, calculada: bool}
     */
    public function integrarSolicitud(int $idSolicitud): array
    {
        $sol = DB::table('SOLICITUD_PERMISO')
            ->join('TIPO_PERMISO_LABORAL', 'SOLICITUD_PERMISO.ID_TIPO_PERMISO', '=', 'TIPO_PERMISO_LABORAL.ID_TIPO_PERMISO')
            ->join('EMPLEADO', 'SOLICITUD_PERMISO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('SOLICITUD_PERMISO.ID_SOLICITUD', $idSolicitud)
            ->where('SOLICITUD_PERMISO.ESTADO', 'aprobada')
            ->select(
                'SOLICITUD_PERMISO.*',
                'TIPO_PERMISO_LABORAL.DESCUENTA_SALDO_VACACIONES',
                'TIPO_PERMISO_LABORAL.NOMBRE as TIPO_NOMBRE',
                'EMPLEADO.ID_EMPRESA',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as NOMBRE_EMPLEADO")
            )
            ->first();

        if (!$sol) {
            throw new \InvalidArgumentException('Solicitud no encontrada o no está aprobada.');
        }

        if (!$sol->DESCUENTA_SALDO_VACACIONES) {
            throw new \InvalidArgumentException('Este tipo de permiso no genera planilla de vacaciones.');
        }

        if ($sol->INTEGRADO_PLANILLA && $sol->ID_PLANILLA) {
            $existente = DB::table('PLANILLA')->where('ID_PLANILLA', $sol->ID_PLANILLA)->first();
            if ($existente) {
                return [
                    'ID_PLANILLA' => (int) $sol->ID_PLANILLA,
                    'TITULO' => $existente->TITULO,
                    'calculada' => (bool) $existente->RECALCULADA,
                    'ya_integrada' => true,
                ];
            }
        }

        $tipoVacaciones = DB::table('TIPO_PLANILLA')
            ->where('TIPOPLANILLA', 'Vacaciones')
            ->where('ESACTIVO', true)
            ->first();

        if (!$tipoVacaciones) {
            throw new \RuntimeException('No existe el tipo de planilla "Vacaciones" en catálogo.');
        }

        $periodoId = $this->findOrCreatePeriodo($sol->FECHA_INICIO, $sol->FECHA_FIN, (float) $sol->DIAS_SOLICITADOS);
        $planillaId = $this->findOrCreatePlanillaVacaciones(
            (int) $sol->ID_EMPRESA,
            $periodoId,
            (int) $tipoVacaciones->ID_TIPOPLANILLA,
            $sol->NOMBRE_EMPLEADO,
            $sol->FECHA_INICIO,
            $sol->FECHA_FIN
        );

        $this->calcularEmpleadoEnPlanilla(
            $planillaId,
            (int) $sol->ID_EMPLEADO,
            (float) $sol->DIAS_SOLICITADOS
        );

        DB::table('SOLICITUD_PERMISO')->where('ID_SOLICITUD', $idSolicitud)->update([
            'INTEGRADO_PLANILLA' => true,
            'ID_PLANILLA' => $planillaId,
        ]);

        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();

        return [
            'ID_PLANILLA' => $planillaId,
            'TITULO' => $planilla->TITULO ?? '',
            'calculada' => (bool) ($planilla->RECALCULADA ?? false),
            'ya_integrada' => false,
        ];
    }

    protected function findOrCreatePeriodo(string $fechaInicio, string $fechaFin, float $dias): int
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        $existente = DB::table('PERIODO_LABORAL')
            ->where('FECHAINICIO', '<=', $inicio)
            ->where('FECHAFIN', '>=', $fin)
            ->where('ESACTIVO', true)
            ->orderBy('ID_PERIODO')
            ->first();

        if ($existente) {
            return (int) $existente->ID_PERIODO;
        }

        $maxId = DB::table('PERIODO_LABORAL')->max('ID_PERIODO') ?? 0;
        $id = $maxId + 1;
        $label = 'Vac ' . $inicio->format('d/m/Y') . '–' . $fin->format('d/m/Y');

        DB::table('PERIODO_LABORAL')->insert([
            'ID_PERIODO' => $id,
            'FECHAINICIO' => $inicio,
            'FECHAFIN' => $fin,
            'DIAS' => (int) max(1, round($dias)),
            'CALPERIODO' => $label,
            'ESACTIVO' => true,
        ]);

        return $id;
    }

    protected function findOrCreatePlanillaVacaciones(
        int $empresaId,
        int $periodoId,
        int $tipoPlanillaId,
        string $nombreEmpleado,
        string $fechaInicio,
        string $fechaFin
    ): int {
        $abierta = DB::table('PLANILLA')
            ->where('ID_EMPRESA', $empresaId)
            ->where('ID_TIPOPLANILLA', $tipoPlanillaId)
            ->where('ID_PERIODO', $periodoId)
            ->where('ANULADA', false)
            ->where('CERRADA', false)
            ->orderByDesc('ID_PLANILLA')
            ->first();

        if ($abierta) {
            return (int) $abierta->ID_PLANILLA;
        }

        $maxId = DB::table('PLANILLA')->max('ID_PLANILLA') ?? 0;
        $id = $maxId + 1;
        $titulo = 'Vacaciones — ' . trim($nombreEmpleado) . ' (' . Carbon::parse($fechaInicio)->format('d/m') . '–' . Carbon::parse($fechaFin)->format('d/m/Y') . ')';

        $cuenta = DB::table('CUENTA')
            ->where('ESACTIVO', true)
            ->orderBy('ID_CUENTA')
            ->first();

        DB::table('PLANILLA')->insert([
            'ID_PLANILLA' => $id,
            'ID_EMPRESA' => $empresaId,
            'ID_TIPOPLANILLA' => $tipoPlanillaId,
            'ID_PERIODO' => $periodoId,
            'ID_FRECUENCIAPAGO' => 1,
            'ID_CUENTA' => $cuenta->ID_CUENTA ?? 1,
            'TITULO' => $titulo,
            'FECHAPAGO' => Carbon::parse($fechaFin)->addDays(5)->toDateString(),
            'FORMAPAGO' => 'Transferencia',
            'OBSERVACION' => 'Generada automáticamente desde solicitud de vacaciones aprobada.',
            'ESACTIVA' => true,
            'CERRADA' => false,
            'ANULADA' => false,
            'CONTABILIZADA' => false,
            'RECALCULADA' => false,
        ]);

        return $id;
    }

    protected function calcularEmpleadoEnPlanilla(int $planillaId, int $empleadoId, float $dias): void
    {
        $planilla = Planilla::with('periodoLaboral')->find($planillaId);
        if (!$planilla) {
            throw new \RuntimeException('Planilla no encontrada.');
        }

        if ($planilla->CERRADA || $planilla->ANULADA) {
            throw new \RuntimeException('No se puede integrar en una planilla cerrada o anulada.');
        }

        $detallePrevio = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $planillaId)
            ->where('ID_EMPLEADO', $empleadoId)
            ->first();

        if ($detallePrevio) {
            $this->posting->reverseLoanPaymentsForDetalle((int) $detallePrevio->ID_DETALLEPLANILLA);
            DB::table('DETALLE_DESCUENTO_PLANILLA')
                ->where('ID_DETALLEPLANILLA', $detallePrevio->ID_DETALLEPLANILLA)
                ->delete();
            DB::table('DETALLE_PLANILLA')
                ->where('ID_DETALLEPLANILLA', $detallePrevio->ID_DETALLEPLANILLA)
                ->delete();
        }

        $empleado = Empleado::with(['tipoContratacion', 'departamento.area', 'cargo', 'centroCosto'])
            ->where('ID_EMPLEADO', $empleadoId)
            ->where('ESACTIVO', true)
            ->first();

        if (!$empleado) {
            throw new \RuntimeException('Empleado no encontrado o inactivo.');
        }

        $line = $this->calculator->calculatePayrollLine($empleado, $planilla, $dias);
        $descuentosDetalle = $line['DESCUENTOS_DETALLE'] ?? [];
        unset($line['DESCUENTOS_DETALLE']);

        $maxDetailId = DB::table('DETALLE_PLANILLA')->max('ID_DETALLEPLANILLA') ?? 0;
        $maxDescId = DB::table('DETALLE_DESCUENTO_PLANILLA')->max('ID_DETALLEDESCPLANILLA') ?? 0;
        $correlativo = (int) DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId)->max('CORRELATIVO') + 1;

        $maxDetailId++;
        DB::table('DETALLE_PLANILLA')->insert(array_merge([
            'ID_DETALLEPLANILLA' => $maxDetailId,
            'ID_PLANILLA' => $planillaId,
            'ID_EMPLEADO' => $empleadoId,
            'CORRELATIVO' => max(1, $correlativo),
        ], $line));

        foreach ($descuentosDetalle as $desc) {
            $maxDescId++;
            DB::table('DETALLE_DESCUENTO_PLANILLA')->insert([
                'ID_DETALLEDESCPLANILLA' => $maxDescId,
                'ID_DETALLEPLANILLA' => $maxDetailId,
                'ID_TIPODESCUENTO' => $desc['ID_TIPODESCUENTO'],
                'CONCEPTO' => $desc['CONCEPTO'],
                'CATEGORIA' => $desc['CATEGORIA'],
                'MONTO' => $desc['MONTO'],
            ]);
        }

        if (($line['PRESTAMOS'] ?? 0) > 0) {
            $this->posting->postLoanPayments($empleadoId, $maxDetailId, $planilla);
        }

        DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update(['RECALCULADA' => true]);
    }
}
