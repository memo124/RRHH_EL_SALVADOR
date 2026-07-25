<?php

namespace App\Services;

use App\Models\Incapacidad;
use App\Models\TipoIncapacidad;
use App\Models\SubsidioIsss;
use Carbon\Carbon;

class IncapacityManagementService
{
    /**
     * Calcula la distribución de días de incapacidad y el subsidio de ISSS.
     */
    public function registerIncapacidad(int $empleadoId, int $tipoIncapacidadId, string $certificado, Carbon $startDate, Carbon $endDate, float $salarioDiario): Incapacidad
    {
        $tipo = TipoIncapacidad::findOrFail($tipoIncapacidadId);
        $diasTotales = $startDate->diffInDays($endDate) + 1;

        $diasPatrono = 0;
        $diasSubsidio = 0;
        $diasNoPagados = 0;

        if ($tipo->ES_MATERNIDAD) {
            // Maternidad: 112 días al 100% por el ISSS
            $diasSubsidio = $diasTotales;
        } else {
            // Enfermedad común u otros
            if ($diasTotales <= $tipo->DIAS_MAXIMOS_COBERTURA_PATRONO) {
                // Dias 1 a 3 pagados 100% por el patrono
                $diasPatrono = $diasTotales;
            } else {
                $diasPatrono = $tipo->DIAS_MAXIMOS_COBERTURA_PATRONO;
                // Día 4 en adelante es subsidiado por el ISSS
                $diasSubsidio = $diasTotales - $diasPatrono;
            }
        }

        // Crear la incapacidad
        $incapacidad = new Incapacidad();
        $incapacidad->ID_EMPLEADO = $empleadoId;
        $incapacidad->ID_TIPOINCAPACIDAD = $tipoIncapacidadId;
        $incapacidad->NUMERO_CERTIFICADO_ISSS = $certificado;
        $incapacidad->FECHA_EMISION = Carbon::now()->toDateString();
        $incapacidad->FECHA_INICIO = $startDate->toDateString();
        $incapacidad->FECHA_FIN = $endDate->toDateString();
        $incapacidad->DIAS_TOTALES = $diasTotales;
        $incapacidad->DIAS_PAGADOS_PATRONO = $diasPatrono;
        $incapacidad->DIAS_SUBSIDIADOS_ISSS = $diasSubsidio;
        $incapacidad->DIAS_NO_PAGADOS = $diasNoPagados;
        $incapacidad->ESTADO_INCAPACIDAD = 'REGISTRADA';
        $incapacidad->save();

        // Si hay días de subsidio, registrar el cobro al ISSS
        if ($diasSubsidio > 0) {
            $subsidio = new SubsidioIsss();
            $subsidio->ID_INCAPACIDAD = $incapacidad->ID_INCAPACIDAD;
            $subsidio->SALARIO_DIARIO_PROMEDIO = $salarioDiario;
            // ISSS cubre el 75% del salario diario promedio
            $subsidio->MONTO_SUBSIDIO_CALCULADO_ISSS = round(($salarioDiario * $diasSubsidio) * ($tipo->PORCENTAJE_SUBSIDIO_ISSS / 100.00), 2);
            $subsidio->MONTO_PAGADO_POR_PATRONO = round(($salarioDiario * $diasPatrono) * ($tipo->PORCENTAJE_PAGO_PATRONO / 100.00), 2);
            $subsidio->ESTADO_SUBSIDIO = 'PENDIENTE';
            $subsidio->save();
        }

        return $incapacidad;
    }
}
