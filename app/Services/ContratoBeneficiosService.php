<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\TipoContratacion;
use Carbon\Carbon;

class ContratoBeneficiosService
{
    public function __construct(
        protected AguinaldoCalculatorService $aguinaldo,
        protected NumeroALetrasService $numeroALetras,
    ) {
    }

    public function calcularParaEmpleado(Empleado $empleado, ?Carbon $fechaReferencia = null): array
    {
        $fechaReferencia = $fechaReferencia ?? Carbon::now();
        $tipo = $empleado->tipoContratacion;
        $aniosCompletos = $this->aniosCompletos($empleado->FECHAINGRESO, $fechaReferencia);
        $antiguedadTexto = $this->textoAntiguedad($empleado->FECHAINGRESO, $fechaReferencia);

        $aguinaldo = $this->calcularAguinaldo($empleado, $tipo);
        $quincena25 = $this->calcularQuincena25($empleado, $tipo, $aniosCompletos);

        return [
            'ANTIGUEDAD_ANIOS' => $aniosCompletos,
            'ANTIGUEDAD_TEXTO' => $antiguedadTexto,
            'AGUINALDO_APLICA' => $aguinaldo['aplica'],
            'DIAS_AGUINALDO' => $aguinaldo['dias'],
            'MONTO_AGUINALDO' => $aguinaldo['monto'],
            'AGUINALDO_LETRAS' => $aguinaldo['letras'],
            'QUINCENA25_APLICA' => $quincena25['aplica'],
            'QUINCENA25_MONTO' => $quincena25['monto'],
            'QUINCENA25_LETRAS' => $quincena25['letras'],
            'QUINCENA25_DETALLE' => $quincena25['detalle'],
        ];
    }

    public function placeholdersBeneficios(array $beneficios): array
    {
        return [
            '{{empleado.antiguedad_anios}}' => (string) $beneficios['ANTIGUEDAD_ANIOS'],
            '{{empleado.antiguedad_texto}}' => $beneficios['ANTIGUEDAD_TEXTO'],
            '{{beneficios.aguinaldo_aplica}}' => $beneficios['AGUINALDO_APLICA'] ? 'Sí' : 'No',
            '{{beneficios.dias_aguinaldo}}' => (string) $beneficios['DIAS_AGUINALDO'],
            '{{beneficios.monto_aguinaldo}}' => number_format((float) $beneficios['MONTO_AGUINALDO'], 2),
            '{{beneficios.aguinaldo_letras}}' => $beneficios['AGUINALDO_LETRAS'],
            '{{beneficios.quincena25_aplica}}' => $beneficios['QUINCENA25_APLICA'] ? 'Sí' : 'No',
            '{{beneficios.quincena25_monto}}' => number_format((float) $beneficios['QUINCENA25_MONTO'], 2),
            '{{beneficios.quincena25_letras}}' => $beneficios['QUINCENA25_LETRAS'],
            '{{beneficios.quincena25_detalle}}' => $beneficios['QUINCENA25_DETALLE'],
        ];
    }

    public function aniosCompletos(?Carbon $fechaIngreso, Carbon $fechaReferencia): int
    {
        if (!$fechaIngreso) {
            return 0;
        }

        $ingreso = Carbon::parse($fechaIngreso)->startOfDay();
        if ($ingreso->gt($fechaReferencia)) {
            return 0;
        }

        return (int) $ingreso->diffInYears($fechaReferencia->copy()->startOfDay());
    }

    private function calcularAguinaldo(Empleado $empleado, ?TipoContratacion $tipo): array
    {
        if (!$tipo || !$tipo->APLICA_AGUINALDO) {
            return ['aplica' => false, 'dias' => 0, 'monto' => 0.0, 'letras' => ''];
        }

        $calc = $this->aguinaldo->calcularMontoAguinaldo($empleado);
        $monto = (float) $calc['MONTO_AGUINALDO'];

        return [
            'aplica' => true,
            'dias' => (int) $calc['DIAS_AGUINALDO'],
            'monto' => $monto,
            'letras' => $this->numeroALetras->convertir($monto),
        ];
    }

    private function calcularQuincena25(Empleado $empleado, ?TipoContratacion $tipo, int $aniosCompletos): array
    {
        if (!$tipo || !$tipo->APLICA_QUINCENA_25) {
            return [
                'aplica' => false,
                'monto' => 0.0,
                'letras' => '',
                'detalle' => 'No aplica para este tipo de contratación.',
            ];
        }

        $minAnios = max(0, (int) ($tipo->ANIOS_MINIMOS_QUINCENA_25 ?? 1));
        if ($aniosCompletos < $minAnios) {
            $detalle = $minAnios === 1
                ? 'Requiere al menos 1 año de antigüedad.'
                : "Requiere al menos {$minAnios} años de antigüedad (tiene {$aniosCompletos}).";

            return [
                'aplica' => false,
                'monto' => 0.0,
                'letras' => '',
                'detalle' => $detalle,
            ];
        }

        $porcentaje = (float) ($tipo->PORCENTAJE_QUINCENA_25 ?? 50);
        $salario = (float) ($empleado->SALARIOMENSUAL ?? 0);
        $monto = round($salario * ($porcentaje / 100), 2);

        return [
            'aplica' => true,
            'monto' => $monto,
            'letras' => $this->numeroALetras->convertir($monto),
            'detalle' => "Aplica quincena 25 ({$porcentaje}% del salario mensual).",
        ];
    }

    private function textoAntiguedad(?Carbon $fechaIngreso, Carbon $fechaReferencia): string
    {
        if (!$fechaIngreso) {
            return 'Sin fecha de ingreso registrada';
        }

        $ingreso = Carbon::parse($fechaIngreso)->startOfDay();
        if ($ingreso->gt($fechaReferencia)) {
            return '0 años';
        }

        $diff = $ingreso->diff($fechaReferencia->copy()->startOfDay());
        $partes = [];

        if ($diff->y > 0) {
            $partes[] = $diff->y . ' ' . ($diff->y === 1 ? 'año' : 'años');
        }
        if ($diff->m > 0) {
            $partes[] = $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
        }
        if ($diff->d > 0 && $diff->y === 0) {
            $partes[] = $diff->d . ' ' . ($diff->d === 1 ? 'día' : 'días');
        }

        return $partes ? implode(' y ', $partes) : 'Menos de un mes';
    }
}
