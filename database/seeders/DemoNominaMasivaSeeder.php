<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CalculatesDemoPayroll;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Genera empresa grande con ~200 empleados, salarios $385–$12,000 y planillas por tipo de contrato.
 *
 * Ajuste CANTIDAD_PERMANENTES a 600 si desea probar planillas muy grandes.
 */
class DemoNominaMasivaSeeder extends Seeder
{
    use CalculatesDemoPayroll;

    /** Cantidad de empleados permanentes (planilla ordinaria grande). */
    protected int $cantidadPermanentes = 150;

    protected int $cantidadHonorarios = 25;

    protected int $cantidadComercial = 25;

    protected float $salarioMinimo = 385.00;

    protected float $salarioMaximo = 12000.00;

    protected int $empresaId = 3;

    protected int $idEmpleadoInicio = 100;

    public function run(): void
    {
        $this->seedEmpresaEstructura();
        $this->seedEmpleadosMasivos();
        $this->seedDescuentosMuestra();
        $this->seedPlanillas();
    }

    protected function seedEmpresaEstructura(): void
    {
        DB::table('EMPRESA')->updateOrInsert(
            ['ID_EMPRESA' => $this->empresaId],
            [
                'NOMBREEMPRESA' => 'Grupo Industrial Centroamericano S.A. de C.V.',
                'ABREVIATURA' => 'GICA',
                'URL_LOGO' => '/images/logos/empresa-2.svg',
                'NUMERONIT' => '0614-150126-103-7',
                'DIRECCION' => 'Km 12.5 Carretera a Quezaltepeque, La Libertad',
                'TELEFONO' => '2500-8800',
                'EMPRESAACTIVA' => true,
                'ID_DISTRITO' => 110,
            ]
        );

        DB::table('CENTRO_COSTO')->updateOrInsert(
            ['ID_CENTROCOSTO' => 3],
            [
                'ID_EMPRESA' => $this->empresaId,
                'CODIGO_CENTROCOSTO' => 'CC-PROD',
                'NOMBRE_CENTROCOSTO' => 'Planta de Producción',
                'DESCRIPCION' => 'Operaciones industriales',
                'ESACTIVO' => true,
            ]
        );

        DB::table('AREA')->updateOrInsert(
            ['ID_AREA' => 3],
            ['ID_EMPRESA' => $this->empresaId, 'NOMBREAREA' => 'Manufactura', 'ACTIVA' => true, 'PRORRATEADA' => true]
        );
        DB::table('AREA')->updateOrInsert(
            ['ID_AREA' => 4],
            ['ID_EMPRESA' => $this->empresaId, 'NOMBREAREA' => 'Administración Corporativa', 'ACTIVA' => true, 'PRORRATEADA' => false]
        );

        $departamentos = [
            ['ID_DEPARTAMENTO' => 10, 'ID_AREA' => 3, 'NOMBREDEPARTAMENTO' => 'Línea de Ensamble', 'MANO_OBRA_DIRECTA' => true],
            ['ID_DEPARTAMENTO' => 11, 'ID_AREA' => 3, 'NOMBREDEPARTAMENTO' => 'Control de Calidad', 'MANO_OBRA_DIRECTA' => true],
            ['ID_DEPARTAMENTO' => 12, 'ID_AREA' => 3, 'NOMBREDEPARTAMENTO' => 'Mantenimiento Industrial', 'MANO_OBRA_DIRECTA' => true],
            ['ID_DEPARTAMENTO' => 13, 'ID_AREA' => 4, 'NOMBREDEPARTAMENTO' => 'Finanzas y Nómina', 'MANO_OBRA_DIRECTA' => false],
            ['ID_DEPARTAMENTO' => 14, 'ID_AREA' => 4, 'NOMBREDEPARTAMENTO' => 'Recursos Humanos', 'MANO_OBRA_DIRECTA' => false],
            ['ID_DEPARTAMENTO' => 15, 'ID_AREA' => 4, 'NOMBREDEPARTAMENTO' => 'Tecnología', 'MANO_OBRA_DIRECTA' => false],
        ];

        foreach ($departamentos as $dep) {
            DB::table('DEPARTAMENTO')->updateOrInsert(
                ['ID_DEPARTAMENTO' => $dep['ID_DEPARTAMENTO']],
                array_merge($dep, [
                    'ID_EMPRESA' => $this->empresaId,
                    'DESCRIPCION' => $dep['NOMBREDEPARTAMENTO'],
                ])
            );
        }

        $cargos = [
            ['ID_CARGO' => 10, 'ID_DEPARTAMENTO' => 10, 'NOMBRECARGO' => 'Operario de Producción', 'NIVEL_JERARQUICO' => 5],
            ['ID_CARGO' => 11, 'ID_DEPARTAMENTO' => 10, 'NOMBRECARGO' => 'Supervisor de Línea', 'NIVEL_JERARQUICO' => 3],
            ['ID_CARGO' => 12, 'ID_DEPARTAMENTO' => 11, 'NOMBRECARGO' => 'Inspector de Calidad', 'NIVEL_JERARQUICO' => 4],
            ['ID_CARGO' => 13, 'ID_DEPARTAMENTO' => 12, 'NOMBRECARGO' => 'Técnico de Mantenimiento', 'NIVEL_JERARQUICO' => 4],
            ['ID_CARGO' => 14, 'ID_DEPARTAMENTO' => 13, 'NOMBRECARGO' => 'Contador General', 'NIVEL_JERARQUICO' => 2],
            ['ID_CARGO' => 15, 'ID_DEPARTAMENTO' => 13, 'NOMBRECARGO' => 'Analista Financiero', 'NIVEL_JERARQUICO' => 3],
            ['ID_CARGO' => 16, 'ID_DEPARTAMENTO' => 14, 'NOMBRECARGO' => 'Especialista RRHH', 'NIVEL_JERARQUICO' => 3],
            ['ID_CARGO' => 17, 'ID_DEPARTAMENTO' => 15, 'NOMBRECARGO' => 'Desarrollador de Software', 'NIVEL_JERARQUICO' => 3],
            ['ID_CARGO' => 18, 'ID_DEPARTAMENTO' => 14, 'NOMBRECARGO' => 'Consultor Externo', 'NIVEL_JERARQUICO' => 4],
            ['ID_CARGO' => 19, 'ID_DEPARTAMENTO' => 13, 'NOMBRECARGO' => 'Agente Comercial', 'NIVEL_JERARQUICO' => 4],
        ];

        foreach ($cargos as $cargo) {
            DB::table('CARGO')->updateOrInsert(
                ['ID_CARGO' => $cargo['ID_CARGO']],
                array_merge($cargo, ['CARGOESTADO' => true])
            );
        }
    }

    protected function seedEmpleadosMasivos(): void
    {
        $nombres = ['Juan', 'María', 'Pedro', 'Ana', 'Luis', 'Carmen', 'Roberto', 'Sofía', 'Miguel', 'Laura', 'Diego', 'Patricia', 'Francisco', 'Gabriela', 'Ricardo', 'Verónica', 'Eduardo', 'Claudia', 'Jorge', 'Mónica'];
        $apellidos = ['García', 'Rodríguez', 'Martínez', 'Hernández', 'López', 'Pérez', 'González', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Morales', 'Cruz', 'Reyes', 'Mendoza', 'Vásquez', 'Castillo', 'Jiménez', 'Ortiz', 'Silva'];

        $id = $this->idEmpleadoInicio;
        $total = $this->cantidadPermanentes + $this->cantidadHonorarios + $this->cantidadComercial;

        for ($i = 0; $i < $total; $i++) {
            $tipoContrato = 1;
            $cargoPool = [10, 11, 12, 13, 14, 15, 16, 17];
            $deptoPool = [10, 11, 12, 13, 14, 15];

            if ($i >= $this->cantidadPermanentes && $i < $this->cantidadPermanentes + $this->cantidadHonorarios) {
                $tipoContrato = 2;
                $cargoPool = [18, 15, 17];
                $deptoPool = [14, 15, 13];
            } elseif ($i >= $this->cantidadPermanentes + $this->cantidadHonorarios) {
                $tipoContrato = 3;
                $cargoPool = [19, 18];
                $deptoPool = [13, 14];
            }

            $salario = $this->salarioParaIndice($i, $tipoContrato);
            $nombre = $nombres[$i % count($nombres)];
            $apellido = $apellidos[intdiv($i, count($nombres)) % count($apellidos)];
            $apellido2 = $apellidos[($i + 7) % count($apellidos)];

            $prefijo = match ($tipoContrato) {
                2 => 'HON',
                3 => 'COM',
                default => 'GICA',
            };

            DB::table('EMPLEADO')->updateOrInsert(
                ['ID_EMPLEADO' => $id],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_DEPARTAMENTO' => $deptoPool[$i % count($deptoPool)],
                    'ID_CARGO' => $cargoPool[$i % count($cargoPool)],
                    'ID_CENTROCOSTO' => 3,
                    'ID_TIPOCONTRATACION' => $tipoContrato,
                    'ID_AFP' => ($tipoContrato === 1) ? (($i % 2) + 1) : null,
                    'ID_BANCO' => ($i % 3) + 1,
                    'ID_DISTRITO' => 110,
                    'CODIGOEMPLEADO' => sprintf('%s-%04d', $prefijo, $id),
                    'NOMBRES' => $nombre,
                    'APELLIDO_1' => $apellido,
                    'APELLIDO_2' => $apellido2,
                    'DUI' => sprintf('%08d-%d', 10000000 + $id, $id % 10),
                    'NIT' => sprintf('0614-%06d-%03d-%d', 100000 + $id, $id % 1000, $id % 10),
                    'ISSS' => sprintf('%09d', 200000000 + $id),
                    'GENERO' => ($i % 2 === 0) ? 'M' : 'F',
                    'FECHANACIMIENTO' => sprintf('198%d-%02d-%02d', ($i % 9), ($i % 12) + 1, ($i % 28) + 1),
                    'FECHAINGRESO' => sprintf('202%d-%02d-01', ($i % 4), ($i % 12) + 1),
                    'SALARIOMENSUAL' => $salario,
                    'SALARIODIARIO' => round($salario / 30, 4),
                    'NUMEROCUENTA' => sprintf('5020000000%05d', $id),
                    'ESACTIVO' => true,
                ]
            );

            $id++;
        }
    }

    protected function salarioParaIndice(int $indice, int $tipoContrato): float
    {
        if ($tipoContrato === 2) {
            return round(800 + ($indice % 25) * 180 + ($indice % 7) * 50, 2);
        }
        if ($tipoContrato === 3) {
            return round(600 + ($indice % 20) * 220 + ($indice % 5) * 80, 2);
        }

        $rango = $this->salarioMaximo - $this->salarioMinimo;
        $factor = $this->cantidadPermanentes > 1 ? ($indice % $this->cantidadPermanentes) / ($this->cantidadPermanentes - 1) : 0;
        $base = $this->salarioMinimo + ($factor * $rango);
        $variacion = (($indice * 17) % 100) - 50;

        return round(max($this->salarioMinimo, min($this->salarioMaximo, $base + $variacion)), 2);
    }

    protected function seedDescuentosMuestra(): void
    {
        $tiposDescuento = [4, 21, 22, 23, 24];
        $descId = 1000;
        $finPermanente = $this->idEmpleadoInicio + $this->cantidadPermanentes - 1;

        for ($empId = $this->idEmpleadoInicio; $empId <= $finPermanente; $empId++) {
            if ($empId % 4 !== 0) {
                continue;
            }

            $tipo = $tiposDescuento[$empId % count($tiposDescuento)];
            $monto = round(10 + ($empId % 9) * 5, 2);

            DB::table('DESCUENTO_EMPLEADO')->updateOrInsert(
                ['ID_DESCUENTOEMPLEADO' => $descId++],
                [
                    'ID_EMPLEADO' => $empId,
                    'ID_TIPODESCUENTO' => $tipo,
                    'MONTO' => $monto,
                    'ES_PORCENTAJE' => false,
                    'FECHAINICIO' => '2026-01-01',
                    'FECHAFIN' => null,
                    'ES_RECURRENTE' => true,
                    'ESACTIVO' => true,
                ]
            );

            if ($empId % 12 === 0) {
                DB::table('DESCUENTO_EMPLEADO')->updateOrInsert(
                    ['ID_DESCUENTOEMPLEADO' => $descId++],
                    [
                        'ID_EMPLEADO' => $empId,
                        'ID_TIPODESCUENTO' => 23,
                        'MONTO' => 0,
                        'PORCENTAJE' => 2.0,
                        'ES_PORCENTAJE' => true,
                        'FECHAINICIO' => '2026-01-01',
                        'FECHAFIN' => null,
                        'ES_RECURRENTE' => true,
                        'ESACTIVO' => true,
                    ]
                );
            }
        }
    }

    protected function seedPlanillas(): void
    {
        $permanentes = range($this->idEmpleadoInicio, $this->idEmpleadoInicio + $this->cantidadPermanentes - 1);
        $honorarios = range(
            $this->idEmpleadoInicio + $this->cantidadPermanentes,
            $this->idEmpleadoInicio + $this->cantidadPermanentes + $this->cantidadHonorarios - 1
        );
        $comercial = range(
            $this->idEmpleadoInicio + $this->cantidadPermanentes + $this->cantidadHonorarios,
            $this->idEmpleadoInicio + $this->cantidadPermanentes + $this->cantidadHonorarios + $this->cantidadComercial - 1
        );

        DB::table('PERIODO_LABORAL')->updateOrInsert(
            ['ID_PERIODO' => 2],
            [
                'FECHAINICIO' => '2026-08-01',
                'FECHAFIN' => '2026-08-31',
                'DIAS' => 30,
                'CALPERIODO' => 'Agosto 2026',
                'ESACTIVO' => true,
            ]
        );

        $planillas = [
            [
                'ID_PLANILLA' => 2,
                'TITULO' => sprintf('Planilla Ordinaria Jul 2026 - GICA (%d emp.)', count($permanentes)),
                'ID_TIPOPLANILLA' => 1,
                'ID_PERIODO' => 1,
                'empleados' => $permanentes,
            ],
            [
                'ID_PLANILLA' => 3,
                'TITULO' => sprintf('Planilla Honorarios Jul 2026 - GICA (%d emp.)', count($honorarios)),
                'ID_TIPOPLANILLA' => 5,
                'ID_PERIODO' => 1,
                'empleados' => $honorarios,
            ],
            [
                'ID_PLANILLA' => 4,
                'TITULO' => sprintf('Planilla Comercial Jul 2026 - GICA (%d emp.)', count($comercial)),
                'ID_TIPOPLANILLA' => 6,
                'ID_PERIODO' => 1,
                'empleados' => $comercial,
            ],
        ];

        // Planilla adicional (mismo personal permanente, periodo agosto) — se crea sin calcular para demo rápida
        DB::table('PLANILLA')->updateOrInsert(
            ['ID_PLANILLA' => 5],
            [
                'ID_EMPRESA' => $this->empresaId,
                'ID_TIPOPLANILLA' => 1,
                'ID_PERIODO' => 2,
                'ID_FRECUENCIAPAGO' => 1,
                'ID_CUENTA' => 1,
                'TITULO' => sprintf('Planilla Ordinaria Ago 2026 - GICA (%d emp.) — pendiente calcular', count($permanentes)),
                'FECHAPAGO' => '2026-08-30',
                'FORMAPAGO' => 'Transferencia',
                'OBSERVACION' => 'Calcule desde la pantalla para generar los 150+ registros de agosto.',
                'ESACTIVA' => true,
                'CERRADA' => false,
                'ANULADA' => false,
                'CONTABILIZADA' => false,
                'RECALCULADA' => false,
            ]
        );

        foreach ($planillas as $plan) {
            DB::table('PLANILLA')->updateOrInsert(
                ['ID_PLANILLA' => $plan['ID_PLANILLA']],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_TIPOPLANILLA' => $plan['ID_TIPOPLANILLA'] ?? 1,
                    'ID_PERIODO' => $plan['ID_PERIODO'],
                    'ID_FRECUENCIAPAGO' => 1,
                    'ID_CUENTA' => 1,
                    'TITULO' => $plan['TITULO'],
                    'FECHAPAGO' => $plan['ID_PERIODO'] === 2 ? '2026-08-30' : '2026-07-30',
                    'FORMAPAGO' => 'Transferencia',
                    'OBSERVACION' => 'Planilla masiva de demostración — salarios $385 a $12,000',
                    'ESACTIVA' => true,
                    'CERRADA' => false,
                    'ANULADA' => false,
                    'CONTABILIZADA' => false,
                    'RECALCULADA' => false,
                ]
            );

            $this->calcularPlanillaDemo($plan['ID_PLANILLA'], $plan['empleados']);
        }
    }
}
