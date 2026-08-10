<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Genera asientos contables (partida doble simplificada) a partir de los
 * totales de una planilla ya cerrada, siguiendo el esquema típico de
 * nómina en El Salvador: gasto de sueldos y cuota patronal como débito,
 * y los pasivos por retenciones/pagos (ISSS, AFP, renta, banco) como crédito.
 */
class AccountingPostingService
{
    /** Cuentas contables por defecto, sembradas la primera vez que se usa cada empresa. */
    private const CUENTAS_DEFAULT = [
        'gasto' => ['CODIGO' => '5100', 'NOMBRE' => 'Gastos de sueldos y salarios'],
        'isss'  => ['CODIGO' => '2105', 'NOMBRE' => 'ISSS por pagar'],
        'afp'   => ['CODIGO' => '2106', 'NOMBRE' => 'AFP por pagar'],
        'renta' => ['CODIGO' => '2107', 'NOMBRE' => 'Impuesto sobre la renta retenido'],
        'otros' => ['CODIGO' => '2199', 'NOMBRE' => 'Otras cuentas por pagar de nómina'],
        'banco' => ['CODIGO' => '1102', 'NOMBRE' => 'Banco - Cuenta de planillas'],
    ];

    /**
     * Genera (o retorna, si ya existe) el asiento contable de una planilla.
     */
    public function contabilizar(int $planillaId, ?int $idUsuario = null): int
    {
        $existente = DB::table('ASIENTO_CONTABLE')
            ->where('ID_PLANILLA', $planillaId)
            ->where('ESACTIVO', true)
            ->first();

        if ($existente) {
            return $existente->ID_ASIENTO;
        }

        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new \RuntimeException('Planilla no encontrada.');
        }

        $totales = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $planillaId)
            ->selectRaw('
                COALESCE(SUM(TOTAL_DEVENGADO), 0) AS TOTAL_DEVENGADO,
                COALESCE(SUM(ISSS_EMPLEADO), 0) AS ISSS_EMPLEADO,
                COALESCE(SUM(ISSS_PATRONAL), 0) AS ISSS_PATRONAL,
                COALESCE(SUM(AFP_EMPLEADO), 0) AS AFP_EMPLEADO,
                COALESCE(SUM(AFP_PATRONAL), 0) AS AFP_PATRONAL,
                COALESCE(SUM(INSAFORP_PATRONAL), 0) AS INSAFORP_PATRONAL,
                COALESCE(SUM(RENTA_EMPLEADO), 0) AS RENTA_EMPLEADO,
                COALESCE(SUM(PRESTAMOS), 0) AS PRESTAMOS,
                COALESCE(SUM(OTRO_DESCUENTOS), 0) AS OTRO_DESCUENTOS,
                COALESCE(SUM(ANTICIPO), 0) AS ANTICIPO,
                COALESCE(SUM(LIQUIDO_A_RECIBIR), 0) AS LIQUIDO_A_RECIBIR
            ')
            ->first();

        if (!$totales || (float) $totales->TOTAL_DEVENGADO <= 0) {
            throw new \RuntimeException('La planilla no tiene detalles calculados; no es posible generar el asiento contable.');
        }

        $lineas = $this->construirLineas($totales);
        $cuentas = $this->cuentasPorTipo((int) $planilla->ID_EMPRESA);

        $totalDebe = round((float) array_sum(array_column($lineas, 'DEBE')), 2);
        $totalHaber = round((float) array_sum(array_column($lineas, 'HABER')), 2);

        return DB::transaction(function () use ($planilla, $lineas, $cuentas, $totalDebe, $totalHaber, $idUsuario) {
            $idAsiento = (DB::table('ASIENTO_CONTABLE')->max('ID_ASIENTO') ?? 0) + 1;

            DB::table('ASIENTO_CONTABLE')->insert([
                'ID_ASIENTO' => $idAsiento,
                'ID_PLANILLA' => $planilla->ID_PLANILLA,
                'ID_EMPRESA' => $planilla->ID_EMPRESA,
                'FECHA' => $planilla->FECHAPAGO,
                'CONCEPTO' => "Contabilización planilla #{$planilla->ID_PLANILLA} - {$planilla->TITULO}",
                'TOTAL_DEBE' => $totalDebe,
                'TOTAL_HABER' => $totalHaber,
                'ID_USUARIO' => $idUsuario,
                'FECHA_CREACION' => now(),
                'ESACTIVO' => true,
            ]);

            $maxDetalle = DB::table('DETALLE_ASIENTO')->max('ID_DETALLE') ?? 0;
            $orden = 0;
            foreach ($lineas as $linea) {
                $orden++;
                $maxDetalle++;
                $cuenta = $cuentas[$linea['TIPO']];

                DB::table('DETALLE_ASIENTO')->insert([
                    'ID_DETALLE' => $maxDetalle,
                    'ID_ASIENTO' => $idAsiento,
                    'CUENTA' => $cuenta->CODIGO,
                    'DESCRIPCION' => "{$cuenta->NOMBRE} — {$linea['DESCRIPCION']}",
                    'DEBE' => $linea['DEBE'],
                    'HABER' => $linea['HABER'],
                    'ORDEN' => $orden,
                ]);
            }

            return $idAsiento;
        });
    }

    /** Retorna el asiento y sus líneas para una planilla, o null si no existe. */
    public function getAsiento(int $planillaId): ?array
    {
        $asiento = DB::table('ASIENTO_CONTABLE')
            ->where('ID_PLANILLA', $planillaId)
            ->where('ESACTIVO', true)
            ->first();

        if (!$asiento) {
            return null;
        }

        $detalles = DB::table('DETALLE_ASIENTO')
            ->where('ID_ASIENTO', $asiento->ID_ASIENTO)
            ->orderBy('ORDEN')
            ->get();

        return ['asiento' => $asiento, 'detalles' => $detalles];
    }

    /**
     * Construye las líneas débito/crédito del asiento a partir de los totales
     * de la planilla (montos ya redondeados a 2 decimales).
     *
     * @return list<array{TIPO:string,DEBE:float,HABER:float,DESCRIPCION:string}>
     */
    private function construirLineas(object $totales): array
    {
        $lineas = [];

        $lineas[] = [
            'TIPO' => 'gasto',
            'DEBE' => round((float) $totales->TOTAL_DEVENGADO, 2),
            'HABER' => 0.0,
            'DESCRIPCION' => 'Total devengado (salarios brutos)',
        ];

        $cuotaPatronal = round(
            (float) $totales->ISSS_PATRONAL + (float) $totales->AFP_PATRONAL + (float) $totales->INSAFORP_PATRONAL,
            2
        );
        if ($cuotaPatronal > 0) {
            $lineas[] = [
                'TIPO' => 'gasto',
                'DEBE' => $cuotaPatronal,
                'HABER' => 0.0,
                'DESCRIPCION' => 'Cuota patronal ISSS / AFP / INSAFORP',
            ];
        }

        $isss = round((float) $totales->ISSS_EMPLEADO + (float) $totales->ISSS_PATRONAL, 2);
        if ($isss > 0) {
            $lineas[] = [
                'TIPO' => 'isss',
                'DEBE' => 0.0,
                'HABER' => $isss,
                'DESCRIPCION' => 'ISSS laboral y patronal por pagar',
            ];
        }

        $afp = round((float) $totales->AFP_EMPLEADO + (float) $totales->AFP_PATRONAL, 2);
        if ($afp > 0) {
            $lineas[] = [
                'TIPO' => 'afp',
                'DEBE' => 0.0,
                'HABER' => $afp,
                'DESCRIPCION' => 'AFP laboral y patronal por pagar',
            ];
        }

        if ((float) $totales->INSAFORP_PATRONAL > 0) {
            $lineas[] = [
                'TIPO' => 'otros',
                'DEBE' => 0.0,
                'HABER' => round((float) $totales->INSAFORP_PATRONAL, 2),
                'DESCRIPCION' => 'INSAFORP patronal por pagar',
            ];
        }

        if ((float) $totales->RENTA_EMPLEADO > 0) {
            $lineas[] = [
                'TIPO' => 'renta',
                'DEBE' => 0.0,
                'HABER' => round((float) $totales->RENTA_EMPLEADO, 2),
                'DESCRIPCION' => 'Impuesto sobre la renta retenido',
            ];
        }

        $otros = round((float) $totales->OTRO_DESCUENTOS + (float) $totales->PRESTAMOS + (float) $totales->ANTICIPO, 2);
        if ($otros > 0) {
            $lineas[] = [
                'TIPO' => 'otros',
                'DEBE' => 0.0,
                'HABER' => $otros,
                'DESCRIPCION' => 'Otros descuentos, préstamos y anticipos',
            ];
        }

        $lineas[] = [
            'TIPO' => 'banco',
            'DEBE' => 0.0,
            'HABER' => round((float) $totales->LIQUIDO_A_RECIBIR, 2),
            'DESCRIPCION' => 'Neto a pagar a empleados',
        ];

        return $lineas;
    }

    /** @return array<string, object> */
    private function cuentasPorTipo(int $idEmpresa): array
    {
        $this->sembrarCuentasDefault($idEmpresa);

        return DB::table('CUENTA_CONTABLE_EMPRESA')
            ->where('ID_EMPRESA', $idEmpresa)
            ->where('ESACTIVO', true)
            ->get()
            ->keyBy('TIPO')
            ->all();
    }

    private function sembrarCuentasDefault(int $idEmpresa): void
    {
        $existentes = DB::table('CUENTA_CONTABLE_EMPRESA')
            ->where('ID_EMPRESA', $idEmpresa)
            ->pluck('TIPO')
            ->all();

        $faltantes = array_diff(array_keys(self::CUENTAS_DEFAULT), $existentes);
        if ($faltantes === []) {
            return;
        }

        $maxId = DB::table('CUENTA_CONTABLE_EMPRESA')->max('ID') ?? 0;
        foreach ($faltantes as $tipo) {
            $maxId++;
            $def = self::CUENTAS_DEFAULT[$tipo];
            DB::table('CUENTA_CONTABLE_EMPRESA')->insert([
                'ID' => $maxId,
                'ID_EMPRESA' => $idEmpresa,
                'CODIGO' => $def['CODIGO'],
                'NOMBRE' => $def['NOMBRE'],
                'TIPO' => $tipo,
                'ESACTIVO' => true,
            ]);
        }
    }
}
