<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\RetencionIsr;
use App\Models\RetencionLey;
use App\Models\Incapacidad;
use Illuminate\Support\Facades\DB;

class PayrollCalculatorService
{
    /** @var PayrollMonthlyCeilingService */
    protected $monthlyCeiling;

    public function __construct(PayrollMonthlyCeilingService $monthlyCeiling)
    {
        $this->monthlyCeiling = $monthlyCeiling;
    }

    /**
     * Calcula la retención del ISR progresivo de El Salvador.
     */
    public function calculateISR(float $baseGravada, int $frecuenciaIsrId): float
    {
        // Obtener tramos progresivos de retención de la base de datos
        $tramo = DB::table('RETENCION_ISR')
            ->where('ID_FRECUENCIAISR', $frecuenciaIsrId)
            ->where('MONTOINICIAL', '<=', $baseGravada)
            ->where('MONTOFINA', '>=', $baseGravada)
            ->first();

        if (!$tramo) {
            // Buscar si es un tramo final sin límite superior (o con exceso)
            $tramo = DB::table('RETENCION_ISR')
                ->where('ID_FRECUENCIAISR', $frecuenciaIsrId)
                ->where('MONTOINICIAL', '<=', $baseGravada)
                ->orderBy('MONTOINICIAL', 'desc')
                ->first();
        }

        if (!$tramo) {
            return 0.00;
        }

        $exceso = $baseGravada - (float)$tramo->SOBREEXCESO;
        $impuestoCalculado = ($exceso * ((float)$tramo->PORCENTAJEAPLICA / 100.00)) + (float)$tramo->CUOTAFIJA;

        return round($impuestoCalculado, 2);
    }

    /**
     * Calcula la línea de planilla para un empleado específico.
     */
    public function calculatePayrollLine(Empleado $empleado, Planilla $planilla, float $diasTrabajados = 30.00): array
    {
        $tipoContrato = $empleado->tipoContratacion;
        $salarioBase = (float)$empleado->SALARIOMENSUAL;

        // Pro-ratear el salario base por los días trabajados en el periodo
        $salarioDias = ($salarioBase / 30.00) * $diasTrabajados;

        $fechaInicioPeriodo = $planilla->periodoLaboral->FECHAINICIO;
        $fechaFinPeriodo = $planilla->periodoLaboral->FECHAFIN;

        $ingresoEnPeriodo = function ($query) use ($fechaInicioPeriodo, $fechaFinPeriodo) {
            $query->where('OTRO_INGRESO.FECHAINICIO', '<=', $fechaFinPeriodo)
                ->where(function ($q) use ($fechaInicioPeriodo) {
                    $q->whereNull('OTRO_INGRESO.FECHAFIN')
                        ->orWhere('OTRO_INGRESO.FECHAFIN', '>=', $fechaInicioPeriodo);
                });
        };

        // Sumar otros devengados dinámicos del periodo
        $productividad = DB::table('OTRO_INGRESO')
            ->join('TIPO_INGRESO', 'OTRO_INGRESO.ID_TIPOINGRESO', '=', 'TIPO_INGRESO.ID_TIPOINGRESO')
            ->where('OTRO_INGRESO.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('OTRO_INGRESO.ESACTIVO', true)
            ->where('TIPO_INGRESO.TIPOINGRESO', 'Productividad')
            ->where($ingresoEnPeriodo)
            ->sum('OTRO_INGRESO.MONTOINGRESO') ?? 0.00;

        $comision = DB::table('OTRO_INGRESO')
            ->join('TIPO_INGRESO', 'OTRO_INGRESO.ID_TIPOINGRESO', '=', 'TIPO_INGRESO.ID_TIPOINGRESO')
            ->where('OTRO_INGRESO.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('OTRO_INGRESO.ESACTIVO', true)
            ->where('TIPO_INGRESO.TIPOINGRESO', 'Comisión')
            ->where($ingresoEnPeriodo)
            ->sum('OTRO_INGRESO.MONTOINGRESO') ?? 0.00;

        $otrosIngresos = DB::table('OTRO_INGRESO')
            ->join('TIPO_INGRESO', 'OTRO_INGRESO.ID_TIPOINGRESO', '=', 'TIPO_INGRESO.ID_TIPOINGRESO')
            ->where('OTRO_INGRESO.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('OTRO_INGRESO.ESACTIVO', true)
            ->whereNotIn('TIPO_INGRESO.TIPOINGRESO', ['Productividad', 'Comisión'])
            ->where($ingresoEnPeriodo)
            ->sum('OTRO_INGRESO.MONTOINGRESO') ?? 0.00;

        // Horas extras acumuladas en el periodo
        $montoHorasExtras = DB::table('DETALLES_HORASEXTRAS')
            ->where('ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('ID_PLANILLA', $planilla->ID_PLANILLA)
            ->sum('MONTOAPAGAR') ?? 0.00;

        // Evaluar subsidios / deducciones por incapacidad
        $diasIncapacidad = DB::table('INCAPACIDAD')
            ->where('ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('FECHA_INICIO', '<=', $planilla->periodoLaboral->FECHAFIN)
            ->where('FECHA_FIN', '>=', $planilla->periodoLaboral->FECHAINICIO)
            ->sum('DIAS_TOTALES') ?? 0;

        // Si hay incapacidad, los días subsidiados por ISSS (enfermedad > 3 días o maternidad) no se pagan como devengado gravado
        $diasSubsidiados = 0;
        if ($diasIncapacidad > 0) {
            $incapacidades = Incapacidad::where('ID_EMPLEADO', $empleado->ID_EMPLEADO)->get();
            foreach ($incapacidades as $inc) {
                $diasSubsidiados += $inc->DIAS_SUBSIDIADOS_ISSS;
            }
        }

        // Ajustar la base gravable restando los días subsidiados
        $descuentoIncapacidad = ($empleado->SALARIODIARIO * $diasSubsidiados);
        $devengadoGravado = ($salarioDias + $productividad + $comision + $otrosIngresos + $montoHorasExtras) - $descuentoIncapacidad;
        if ($devengadoGravado < 0) {
            $devengadoGravado = 0.00;
        }

        // Banderas de cálculo (Hereda del contrato con Overrides)
        $aplicaISSS = $tipoContrato->APLICA_ISSS;
        if ($empleado->APLICA_ISSS_OVERRIDE !== null) {
            $aplicaISSS = $empleado->APLICA_ISSS_OVERRIDE;
        }

        $aplicaAFP = $tipoContrato->APLICA_AFP;
        if ($empleado->APLICA_AFP_OVERRIDE !== null) {
            $aplicaAFP = $empleado->APLICA_AFP_OVERRIDE;
        }

        if ($empleado->JUBILADO) {
            $aplicaAFP = false; // Jubilado no cotiza AFP
        }

        // 1. Cálculos de AFP
        $afpEmpleado = 0.00;
        $afpPatronal = 0.00;
        if ($aplicaAFP) {
            // Parámetros de ley (AFP de El Salvador)
            $afpInfo = DB::table('AFP')->where('ESACTIVO', true)->first();
            $tasaEmpleado = $afpInfo ? (float)$afpInfo->PORCENTAJEEMPLEADOR / 100.00 : 0.0725; // 7.25%
            $tasaPatronal = $afpInfo ? (float)$afpInfo->PORCENTAJEPATRONAL / 100.00 : 0.0875; // 8.75%
            $techoAfp = $afpInfo ? (float)$afpInfo->DEVENGADOMAXIMO : 7028.29; // Techo actual aproximado

            $baseAfp = min($devengadoGravado, $techoAfp);
            $afpEmpleado = $baseAfp * $tasaEmpleado;
            $afpPatronal = $baseAfp * $tasaPatronal;
        }

        // 2. Cálculos de ISSS (techo mensual $1,000 por empleado)
        $devengadoAcumuladoMes = $this->monthlyCeiling->getDevengadoAcumuladoMes($empleado->ID_EMPLEADO, $planilla);
        $isss = $this->monthlyCeiling->calcularIsss($devengadoGravado, $devengadoAcumuladoMes, $aplicaISSS);
        $isssEmpleado = $isss['empleado'];
        $isssPatronal = $isss['patronal'];

        // 3. Renta (ISR) progresiva o fija, con recálculo en junio/diciembre
        $rentaEmpleado = 0.00;
        $rentaAjusteRecalculo = 0.00;
        if ($tipoContrato->APLICA_RENTA_FIJA) {
            $tasaRentaFija = (float)$tipoContrato->PORCENTAJE_RENTA_FIJA / 100.00;
            $rentaEmpleado = $devengadoGravado * $tasaRentaFija;
        } elseif ($tipoContrato->APLICA_RENTA_TABLA) {
            $baseIsr = $devengadoGravado - $afpEmpleado - $isssEmpleado;
            if ($baseIsr < 0) {
                $baseIsr = 0.00;
            }
            $frecuenciaIsrId = $planilla->ID_FRECUENCIAPAGO;
            $rentaPeriodo = $this->calculateISR($baseIsr, $frecuenciaIsrId);
            $recalculo = app(PayrollRentRecalculationService::class)->calcularRentaConRecalculo(
                $baseIsr,
                $rentaPeriodo,
                $empleado->ID_EMPLEADO,
                $planilla,
                $frecuenciaIsrId,
                true
            );
            $rentaEmpleado = $recalculo['renta'];
            $rentaAjusteRecalculo = $recalculo['ajuste'];
        }

        // 4. INSAFORP (1% patronal, techo mensual $1,000 por empleado)
        $insaforpPatronal = $this->monthlyCeiling->calcularInsaforp(
            $devengadoGravado,
            $devengadoAcumuladoMes,
            (bool) $tipoContrato->APLICA_INSAFORP,
            $this->monthlyCeiling->empresaAplicaInsaforp($planilla->ID_EMPRESA)
        );

        // 5. Descuentos y Préstamos (desglose dinámico para boletas e impresión)
        $descuentosDetalle = [];

        if ($afpEmpleado > 0) {
            $descuentosDetalle[] = $this->lineaDescuento(2, 'AFP', 'LEY', $afpEmpleado);
        }
        if ($isssEmpleado > 0) {
            $descuentosDetalle[] = $this->lineaDescuento(1, 'ISSS', 'LEY', $isssEmpleado);
        }
        if ($rentaEmpleado > 0) {
            $descuentosDetalle[] = $this->lineaDescuento(3, 'Renta (ISR)', 'LEY', $rentaEmpleado);
        }

        $prestamosActivos = DB::table('PRESTAMOS')
            ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
            ->where('PRESTAMOS.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('PRESTAMOS.PRESTAMOESTADO', true)
            ->where('PRESTAMOS.SALDO_ACTUAL', '>', 0)
            ->select('PRESTAMOS.*', 'TIPO_PRESTAMO.NOMBREPRESTAMO')
            ->get();

        $prestamosCuota = 0.00;
        foreach ($prestamosActivos as $prestamo) {
            $cuota = (float) $prestamo->CUOTA;
            if ($cuota <= 0) {
                continue;
            }
            $prestamosCuota += $cuota;
            $descuentosDetalle[] = $this->lineaDescuento(
                (int) $prestamo->ID_TIPODESCUENTO,
                $prestamo->NOMBREPRESTAMO,
                'PRESTAMO',
                $cuota
            );
        }

        $anticipos = DB::table('PRESTAMO_ABONO')
            ->join('PRESTAMOS', 'PRESTAMO_ABONO.ID_PRESTAMO', '=', 'PRESTAMOS.ID_PRESTAMO')
            ->where('PRESTAMOS.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('PRESTAMO_ABONO.FECHAABONO', '>=', $fechaInicioPeriodo)
            ->where('PRESTAMO_ABONO.FECHAABONO', '<=', $fechaFinPeriodo)
            ->where('PRESTAMO_ABONO.FUERA_PLANILLA', false)
            ->whereNull('PRESTAMO_ABONO.ID_DETALLEPLANILLA')
            ->sum('PRESTAMO_ABONO.MONTOABONADO') ?? 0.00;

        if ($anticipos > 0) {
            $descuentosDetalle[] = $this->lineaDescuento(null, 'Anticipo / Abono préstamo', 'PRESTAMO', (float) $anticipos);
        }

        // Descuentos personalizados asignados al empleado (catálogo dinámico)
        $descuentosEmpleado = DB::table('DESCUENTO_EMPLEADO')
            ->join('TIPO_DESCUENTO', 'DESCUENTO_EMPLEADO.ID_TIPODESCUENTO', '=', 'TIPO_DESCUENTO.ID_TIPODESCUENTO')
            ->where('DESCUENTO_EMPLEADO.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('DESCUENTO_EMPLEADO.ESACTIVO', true)
            ->where('TIPO_DESCUENTO.ESACTIVO', true)
            ->where('DESCUENTO_EMPLEADO.FECHAINICIO', '<=', $fechaFinPeriodo)
            ->where(function ($q) use ($fechaInicioPeriodo) {
                $q->whereNull('DESCUENTO_EMPLEADO.FECHAFIN')
                    ->orWhere('DESCUENTO_EMPLEADO.FECHAFIN', '>=', $fechaInicioPeriodo);
            })
            ->select(
                'DESCUENTO_EMPLEADO.*',
                'TIPO_DESCUENTO.NOMBRETIPODESC',
                'TIPO_DESCUENTO.CATEGORIA'
            )
            ->get();

        $otrosDescuentos = 0.00;
        foreach ($descuentosEmpleado as $desc) {
            if ($desc->ES_PORCENTAJE) {
                $montoDesc = $salarioDias * ((float) $desc->PORCENTAJE / 100.00);
            } else {
                $montoDesc = (float) $desc->MONTO;
            }
            $montoDesc = round($montoDesc, 2);
            if ($montoDesc <= 0) {
                continue;
            }
            $otrosDescuentos += $montoDesc;
            $descuentosDetalle[] = $this->lineaDescuento(
                (int) $desc->ID_TIPODESCUENTO,
                $desc->NOMBRETIPODESC,
                $desc->CATEGORIA ?: 'DESCUENTO',
                $montoDesc
            );
        }
        $otrosDescuentos = round($otrosDescuentos, 2);

        $totalDeducciones = $afpEmpleado + $isssEmpleado + $rentaEmpleado + $prestamosCuota + $anticipos + $otrosDescuentos;
        $liquidoARecibir = ($devengadoGravado + $descuentoIncapacidad) - $totalDeducciones; // Neto líquido

        return [
            'NOM_EMPLEADO' => $empleado->NOMBRES . ' ' . $empleado->APELLIDO_1,
            'AREA' => $empleado->departamento->area->NOMBREAREA ?? '',
            'DEPARTAMENTO' => $empleado->departamento->NOMBREDEPARTAMENTO ?? '',
            'CARGO' => $empleado->cargo->NOMBRECARGO ?? '',
            'TIPO_CONTRATACION_NOM' => $tipoContrato->TIPOCONTRATACION,
            'CODIGO_CENTROCOSTO' => $empleado->centroCosto->CODIGO_CENTROCOSTO ?? '',
            'ES_EVENTUAL' => $tipoContrato->ES_EVENTUAL,
            'JUBILADO' => $empleado->JUBILADO,
            'APLICA_ISSS' => $aplicaISSS,
            'APLICA_AFP' => $aplicaAFP,
            'APLICA_RENTA_TABLA' => $tipoContrato->APLICA_RENTA_TABLA,
            'APLICA_RENTA_FIJA' => $tipoContrato->APLICA_RENTA_FIJA,
            'PORCENTAJE_RENTA_FIJA' => $tipoContrato->PORCENTAJE_RENTA_FIJA,
            'APLICA_INSAFORP' => $tipoContrato->APLICA_INSAFORP,
            'SALARIO_BASE' => $salarioBase,
            'DIASLABORADOS' => $diasTrabajados,
            'SALARIO_DIAS' => $salarioDias,
            'HORAEXTRAS' => $montoHorasExtras,
            'PRODUCTIVIDAD' => $productividad,
            'COMISION' => $comision,
            'OTROS_INGRESOS' => $otrosIngresos,
            'DEVENGADO_GRAVADO' => $devengadoGravado,
            'DEVENGADO_EXENTO' => $descuentoIncapacidad, // subsidio ISSS se considera exento temporalmente
            'TOTAL_DEVENGADO' => $devengadoGravado + $descuentoIncapacidad,
            'AFP_EMPLEADO' => $afpEmpleado,
            'ISSS_EMPLEADO' => $isssEmpleado,
            'RENTA_EMPLEADO' => $rentaEmpleado,
            'DESCUENTOS_LEY' => $afpEmpleado + $isssEmpleado + $rentaEmpleado,
            'OTRO_DESCUENTOS' => $otrosDescuentos,
            'PRESTAMOS' => $prestamosCuota,
            'ANTICIPO' => $anticipos,
            'TOTAL_DEDUCCIONES' => $totalDeducciones,
            'LIQUIDO_A_RECIBIR' => $liquidoARecibir,
            'AFP_PATRONAL' => $afpPatronal,
            'ISSS_PATRONAL' => $isssPatronal,
            'INSAFORP_PATRONAL' => $insaforpPatronal,
            'DESCUENTOS_DETALLE' => $descuentosDetalle,
        ];
    }

    /**
     * Construye una línea de descuento para persistencia e impresión.
     */
    protected function lineaDescuento(?int $tipoId, string $concepto, string $categoria, float $monto): array
    {
        return [
            'ID_TIPODESCUENTO' => $tipoId,
            'CONCEPTO' => $concepto,
            'CATEGORIA' => $categoria,
            'MONTO' => round($monto, 2),
        ];
    }

    /**
     * Obtiene días laborados desde asistencia o usa default del periodo.
     */
    public function getDiasTrabajados(Empleado $empleado, Planilla $planilla): float
    {
        $inicio = $planilla->periodoLaboral->FECHAINICIO;
        $fin = $planilla->periodoLaboral->FECHAFIN;
        $diasPeriodo = (float) ($planilla->periodoLaboral->DIAS ?? 30);

        $count = DB::table('ASISTENCIA_DIARIA')
            ->where('ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->whereBetween('FECHA', [$inicio, $fin])
            ->where('ES_INASISTENCIA', false)
            ->where('ES_INCAPACIDAD', false)
            ->where('HORAS_TRABAJADAS', '>', 0)
            ->count();

        if ($count > 0) {
            return min((float) $count, $diasPeriodo);
        }

        return $diasPeriodo;
    }

    /**
     * Calcula línea de planilla tipo Aguinaldo (exento de ley).
     */
    public function calculateAguinaldoLine(Empleado $empleado, Planilla $planilla): array
    {
        $aguinaldoService = app(AguinaldoCalculatorService::class);
        $aguinaldo = $aguinaldoService->calcularMontoAguinaldo($empleado);
        $monto = $aguinaldo['MONTO_AGUINALDO'];
        $tipoContrato = $empleado->tipoContratacion;

        $prestamosActivos = DB::table('PRESTAMOS')
            ->join('TIPO_PRESTAMO', 'PRESTAMOS.ID_TIPOPRESTAMO', '=', 'TIPO_PRESTAMO.ID_TIPOPRESTAMO')
            ->where('PRESTAMOS.ID_EMPLEADO', $empleado->ID_EMPLEADO)
            ->where('PRESTAMOS.PRESTAMOESTADO', true)
            ->where('PRESTAMOS.SALDO_ACTUAL', '>', 0)
            ->select('PRESTAMOS.*', 'TIPO_PRESTAMO.NOMBREPRESTAMO')
            ->get();

        $prestamosCuota = 0.00;
        $descuentosDetalle = [];
        foreach ($prestamosActivos as $prestamo) {
            $cuota = (float) $prestamo->CUOTA;
            if ($cuota <= 0) {
                continue;
            }
            $prestamosCuota += $cuota;
            $descuentosDetalle[] = $this->lineaDescuento(
                (int) $prestamo->ID_TIPODESCUENTO,
                $prestamo->NOMBREPRESTAMO,
                'PRESTAMO',
                $cuota
            );
        }

        $liquido = $monto - $prestamosCuota;

        return [
            'NOM_EMPLEADO' => $empleado->NOMBRES . ' ' . $empleado->APELLIDO_1,
            'AREA' => $empleado->departamento->area->NOMBREAREA ?? '',
            'DEPARTAMENTO' => $empleado->departamento->NOMBREDEPARTAMENTO ?? '',
            'CARGO' => $empleado->cargo->NOMBRECARGO ?? '',
            'TIPO_CONTRATACION_NOM' => $tipoContrato->TIPOCONTRATACION ?? '',
            'CODIGO_CENTROCOSTO' => $empleado->centroCosto->CODIGO_CENTROCOSTO ?? '',
            'ES_EVENTUAL' => $tipoContrato->ES_EVENTUAL ?? false,
            'JUBILADO' => $empleado->JUBILADO,
            'APLICA_ISSS' => false,
            'APLICA_AFP' => false,
            'APLICA_RENTA_TABLA' => false,
            'APLICA_RENTA_FIJA' => false,
            'PORCENTAJE_RENTA_FIJA' => 0,
            'APLICA_INSAFORP' => false,
            'SALARIO_BASE' => 0.00,
            'DIASLABORADOS' => $aguinaldo['DIAS_AGUINALDO'],
            'SALARIO_DIAS' => 0.00,
            'HORAEXTRAS' => 0.00,
            'PRODUCTIVIDAD' => 0.00,
            'COMISION' => 0.00,
            'OTROS_INGRESOS' => $monto,
            'DEVENGADO_GRAVADO' => 0.00,
            'DEVENGADO_EXENTO' => $monto,
            'TOTAL_DEVENGADO' => $monto,
            'AFP_EMPLEADO' => 0.00,
            'ISSS_EMPLEADO' => 0.00,
            'RENTA_EMPLEADO' => 0.00,
            'DESCUENTOS_LEY' => 0.00,
            'OTRO_DESCUENTOS' => 0.00,
            'PRESTAMOS' => $prestamosCuota,
            'ANTICIPO' => 0.00,
            'TOTAL_DEDUCCIONES' => $prestamosCuota,
            'LIQUIDO_A_RECIBIR' => max(0, $liquido),
            'AFP_PATRONAL' => 0.00,
            'ISSS_PATRONAL' => 0.00,
            'INSAFORP_PATRONAL' => 0.00,
            'DESCUENTOS_DETALLE' => $descuentosDetalle,
        ];
    }
}
