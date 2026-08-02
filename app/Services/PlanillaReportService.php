<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanillaReportService
{
    public function __construct(protected EmpresaLogoService $logos)
    {
    }

    public function getPlanillaData(int $planillaId): array
    {
        $planilla = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->join('EMPRESA', 'PLANILLA.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('FRECUENCIA_PAGO', 'PLANILLA.ID_FRECUENCIAPAGO', '=', 'FRECUENCIA_PAGO.ID_FRECUENCIAPAGO')
            ->leftJoin('CUENTA', 'PLANILLA.ID_CUENTA', '=', 'CUENTA.ID_CUENTA')
            ->select(
                'PLANILLA.*',
                'TIPO_PLANILLA.TIPOPLANILLA',
                'PERIODO_LABORAL.CALPERIODO',
                'PERIODO_LABORAL.FECHAINICIO',
                'PERIODO_LABORAL.FECHAFIN',
                'EMPRESA.NOMBREEMPRESA',
                'EMPRESA.ABREVIATURA',
                'EMPRESA.NUMERONIT as EMPRESA_NIT',
                'EMPRESA.DIRECCION as EMPRESA_DIRECCION',
                'EMPRESA.TELEFONO as EMPRESA_TELEFONO',
                'EMPRESA.URL_LOGO',
                'FRECUENCIA_PAGO.NOMBREFRECUENCIA',
                'CUENTA.CONCEPTOCUENTA',
                'CUENTA.NUMEROCUENTA'
            )
            ->where('PLANILLA.ID_PLANILLA', $planillaId)
            ->first();

        if (!$planilla) {
            throw new RuntimeException('Planilla no encontrada.');
        }

        $detalles = DB::table('DETALLE_PLANILLA')
            ->where('ID_PLANILLA', $planillaId)
            ->orderBy('CORRELATIVO')
            ->orderBy('ID_DETALLEPLANILLA')
            ->get();

        if ($detalles->isEmpty()) {
            throw new RuntimeException('La planilla no tiene detalles calculados. Calcule la planilla primero.');
        }

        $descuentos = DB::table('DETALLE_DESCUENTO_PLANILLA')
            ->whereIn('ID_DETALLEPLANILLA', $detalles->pluck('ID_DETALLEPLANILLA'))
            ->orderBy('CATEGORIA')
            ->orderBy('CONCEPTO')
            ->get()
            ->groupBy('ID_DETALLEPLANILLA');

        $empleadosInfo = DB::table('EMPLEADO')
            ->whereIn('ID_EMPLEADO', $detalles->pluck('ID_EMPLEADO'))
            ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'DUI', 'NIT', 'ISSS', 'NUP', 'NUMEROCUENTA')
            ->get()
            ->keyBy('ID_EMPLEADO');

        $detalles = $detalles->map(function ($det) use ($descuentos, $empleadosInfo) {
            $det->descuentos_detalle = ($descuentos[$det->ID_DETALLEPLANILLA] ?? collect())->values();
            if ($det->descuentos_detalle->isEmpty()) {
                $det->descuentos_detalle = collect($this->buildDescuentosFromColumns($det))->map(fn ($d) => (object) $d);
            }
            $det->empleado_info = $empleadosInfo[$det->ID_EMPLEADO] ?? null;
            return $det;
        });

        $conceptosDescuento = $this->collectConceptosDescuento($detalles);
        $conceptosIngreso = $this->collectConceptosIngreso($detalles);
        $conceptosPatronal = $this->collectConceptosPatronal($detalles);

        $totales = [
            'TOTAL_DEVENGADO' => $detalles->sum('TOTAL_DEVENGADO'),
            'AFP_EMPLEADO' => $detalles->sum('AFP_EMPLEADO'),
            'ISSS_EMPLEADO' => $detalles->sum('ISSS_EMPLEADO'),
            'RENTA_EMPLEADO' => $detalles->sum('RENTA_EMPLEADO'),
            'PRESTAMOS' => $detalles->sum('PRESTAMOS'),
            'OTRO_DESCUENTOS' => $detalles->sum('OTRO_DESCUENTOS'),
            'ANTICIPO' => $detalles->sum('ANTICIPO'),
            'TOTAL_DEDUCCIONES' => $detalles->sum('TOTAL_DEDUCCIONES'),
            'LIQUIDO_A_RECIBIR' => $detalles->sum('LIQUIDO_A_RECIBIR'),
            'AFP_PATRONAL' => $detalles->sum('AFP_PATRONAL'),
            'ISSS_PATRONAL' => $detalles->sum('ISSS_PATRONAL'),
            'INSAFORP_PATRONAL' => $detalles->sum('INSAFORP_PATRONAL'),
            'COUNT' => $detalles->count(),
        ];

        $empresaLogo = $this->logos->resolveDataUri((int) $planilla->ID_EMPRESA);

        $firmantes = DB::table('EMPRESA_FIRMANTE')
            ->where('ID_EMPRESA', $planilla->ID_EMPRESA)
            ->where('ESACTIVO', true)
            ->orderBy('ORDEN')
            ->get();

        $grupos = $this->groupDetallesByAreaDepartamento($detalles);

        return compact('planilla', 'detalles', 'totales', 'conceptosDescuento', 'conceptosIngreso', 'conceptosPatronal', 'empresaLogo', 'firmantes', 'grupos');
    }

    /**
     * Agrupa detalles por área y departamento (orden alfabético).
     *
     * @return array<int, array{area: string, departamentos: array<int, array{departamento: string, detalles: \Illuminate\Support\Collection}>, detalles: \Illuminate\Support\Collection}>
     */
    public function groupDetallesByAreaDepartamento($detalles): array
    {
        $sinArea = 'Sin área';
        $sinDepto = 'Sin departamento';
        $grouped = [];

        foreach ($detalles as $det) {
            $area = trim((string) ($det->AREA ?? '')) ?: $sinArea;
            $depto = trim((string) ($det->DEPARTAMENTO ?? '')) ?: $sinDepto;
            $grouped[$area][$depto][] = $det;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $result = [];
        foreach ($grouped as $area => $departamentos) {
            ksort($departamentos, SORT_NATURAL | SORT_FLAG_CASE);
            $deptoList = [];
            $areaDetalles = collect();

            foreach ($departamentos as $depto => $items) {
                $coll = collect($items);
                $deptoList[] = [
                    'departamento' => $depto,
                    'detalles' => $coll,
                ];
                $areaDetalles = $areaDetalles->concat($coll);
            }

            $result[] = [
                'area' => $area,
                'departamentos' => $deptoList,
                'detalles' => $areaDetalles,
            ];
        }

        return $result;
    }

    /**
     * Totales numéricos para un subconjunto de detalles (subtotales por grupo).
     */
    public function computeGroupTotals($detalles): array
    {
        $detalles = collect($detalles);

        return [
            'COUNT' => $detalles->count(),
            'TOTAL_DEVENGADO' => (float) $detalles->sum('TOTAL_DEVENGADO'),
            'TOTAL_DEDUCCIONES' => (float) $detalles->sum('TOTAL_DEDUCCIONES'),
            'LIQUIDO_A_RECIBIR' => (float) $detalles->sum('LIQUIDO_A_RECIBIR'),
            'AFP_PATRONAL' => (float) $detalles->sum('AFP_PATRONAL'),
            'ISSS_PATRONAL' => (float) $detalles->sum('ISSS_PATRONAL'),
            'INSAFORP_PATRONAL' => (float) $detalles->sum('INSAFORP_PATRONAL'),
        ];
    }

    /**
     * Adjunta descuentos detallados y reconstruye desde columnas agregadas si hace falta.
     */
    public function attachDescuentosDetalle($detalles, $descuentosPorDetalle)
    {
        return $detalles->map(function ($det) use ($descuentosPorDetalle) {
            $det->descuentos_detalle = ($descuentosPorDetalle[$det->ID_DETALLEPLANILLA] ?? collect())
                ->map(function ($desc) {
                    $desc->MONTO = (float) $desc->MONTO;
                    return $desc;
                })
                ->values();
            if ($det->descuentos_detalle->isEmpty()) {
                $det->descuentos_detalle = collect($this->buildDescuentosFromColumns($det))
                    ->map(fn ($d) => (object) $d);
            }
            return $det;
        });
    }

    /**
     * Conceptos de ingreso presentes en la planilla (columnas dinámicas).
     */
    public function collectConceptosIngreso($detalles): array
    {
        $catalog = [
            ['key' => 'SALARIO_DIAS', 'label' => 'Salario'],
            ['key' => 'HORAEXTRAS', 'label' => 'Horas extras'],
            ['key' => 'PRODUCTIVIDAD', 'label' => 'Productividad'],
            ['key' => 'COMISION', 'label' => 'Comisión'],
            ['key' => 'OTROS_INGRESOS', 'label' => 'Otros ingresos'],
            ['key' => 'DEVENGADO_EXENTO', 'label' => 'Devengado exento'],
        ];

        return collect($catalog)
            ->filter(function ($item) use ($detalles) {
                if (in_array($item['key'], ['SALARIO_DIAS', 'HORAEXTRAS'], true)) {
                    return true;
                }

                return $detalles->sum(fn ($d) => (float) ($d->{$item['key']} ?? 0)) > 0;
            })
            ->values()
            ->all();
    }

    /**
     * Conceptos de costo patronal por empleado (columnas dinámicas).
     */
    public function collectConceptosPatronal($detalles): array
    {
        $catalog = [
            ['key' => 'AFP_PATRONAL', 'label' => 'AFP Patronal'],
            ['key' => 'ISSS_PATRONAL', 'label' => 'ISSS Patronal'],
            ['key' => 'INSAFORP_PATRONAL', 'label' => 'INSAFORP Patronal'],
        ];

        $visible = collect($catalog)
            ->filter(fn ($item) => $detalles->sum(fn ($d) => (float) ($d->{$item['key']} ?? 0)) > 0)
            ->values()
            ->all();

        if (!empty($visible)) {
            $visible[] = ['key' => 'TOTAL_PATRONAL', 'label' => 'Total Patronal', 'computed' => true];
        }

        return $visible;
    }

    public function getBoletaData(int $planillaId, int $detalleId): array
    {
        $data = $this->getPlanillaData($planillaId);
        $detalle = $data['detalles']->firstWhere('ID_DETALLEPLANILLA', $detalleId);

        if (!$detalle) {
            throw new RuntimeException('Boleta no encontrada en esta planilla.');
        }

        $data['detalle'] = $detalle;

        return $data;
    }

    /**
     * Recolecta todos los conceptos de descuento presentes (dinámico según catálogo).
     */
    public function collectConceptosDescuento($detalles): array
    {
        $conceptos = [];

        foreach ($detalles as $det) {
            foreach ($det->descuentos_detalle as $desc) {
                $key = $desc->CONCEPTO . '|' . $desc->CATEGORIA;
                if (!isset($conceptos[$key])) {
                    $conceptos[$key] = [
                        'CONCEPTO' => $desc->CONCEPTO,
                        'CATEGORIA' => $desc->CATEGORIA,
                    ];
                }
            }
        }

        $orden = ['LEY' => 1, 'PRESTAMO' => 2, 'DESCUENTO' => 3];

        return collect($conceptos)
            ->sortBy(fn ($c) => ($orden[$c['CATEGORIA']] ?? 9) . $c['CONCEPTO'])
            ->values()
            ->all();
    }

    /**
     * Reconstruye descuentos desde columnas agregadas (planillas calculadas antes del desglose).
     */
    /**
     * Totales por concepto para pie de grilla (sin cargar todos los detalles en cliente).
     */
    public function computeConceptTotals($detalles, array $conceptosDescuento, array $conceptosPatronal): array
    {
        $ingresoKeys = ['SALARIO_DIAS', 'HORAEXTRAS', 'PRODUCTIVIDAD', 'COMISION', 'OTROS_INGRESOS', 'DEVENGADO_EXENTO'];
        $ingreso = [];
        foreach ($ingresoKeys as $key) {
            $ingreso[$key] = round($detalles->sum(function ($d) use ($key) {
                return (float) ($d->{$key} ?? 0);
            }), 2);
        }

        $descuento = [];
        foreach ($conceptosDescuento as $concepto) {
            $monto = 0;
            foreach ($detalles as $det) {
                foreach ($det->descuentos_detalle as $desc) {
                    if ($desc->CONCEPTO === $concepto['CONCEPTO'] && $desc->CATEGORIA === $concepto['CATEGORIA']) {
                        $monto += (float) $desc->MONTO;
                    }
                }
            }
            $descuento[] = [
                'CONCEPTO' => $concepto['CONCEPTO'],
                'CATEGORIA' => $concepto['CATEGORIA'],
                'MONTO' => round($monto, 2),
            ];
        }

        $patronal = [
            'AFP_PATRONAL' => round($detalles->sum('AFP_PATRONAL'), 2),
            'ISSS_PATRONAL' => round($detalles->sum('ISSS_PATRONAL'), 2),
            'INSAFORP_PATRONAL' => round($detalles->sum('INSAFORP_PATRONAL'), 2),
        ];
        $patronal['TOTAL_PATRONAL'] = round(
            $patronal['AFP_PATRONAL'] + $patronal['ISSS_PATRONAL'] + $patronal['INSAFORP_PATRONAL'],
            2
        );

        return [
            'ingreso' => $ingreso,
            'descuento' => $descuento,
            'patronal' => $patronal,
        ];
    }

    public function buildDescuentosFromColumns(object $det): array
    {
        $lineas = [];
        $map = [
            ['field' => 'AFP_EMPLEADO', 'concepto' => 'AFP', 'categoria' => 'LEY', 'tipo' => 2],
            ['field' => 'ISSS_EMPLEADO', 'concepto' => 'ISSS', 'categoria' => 'LEY', 'tipo' => 1],
            ['field' => 'RENTA_EMPLEADO', 'concepto' => 'Renta (ISR)', 'categoria' => 'LEY', 'tipo' => 3],
            ['field' => 'PRESTAMOS', 'concepto' => 'Préstamos', 'categoria' => 'PRESTAMO', 'tipo' => 10],
            ['field' => 'ANTICIPO', 'concepto' => 'Anticipo', 'categoria' => 'PRESTAMO', 'tipo' => null],
            ['field' => 'OTRO_DESCUENTOS', 'concepto' => 'Otros descuentos', 'categoria' => 'DESCUENTO', 'tipo' => null],
        ];

        foreach ($map as $item) {
            $monto = (float) ($det->{$item['field']} ?? 0);
            if ($monto > 0) {
                $lineas[] = [
                    'ID_TIPODESCUENTO' => $item['tipo'],
                    'CONCEPTO' => $item['concepto'],
                    'CATEGORIA' => $item['categoria'],
                    'MONTO' => $monto,
                ];
            }
        }

        return $lineas;
    }
}
