<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CalculatesDemoPayroll;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoNominaSeeder extends Seeder
{
    use CalculatesDemoPayroll;
    public function run(): void
    {
        DB::table('EMPRESA')->updateOrInsert(
            ['ID_EMPRESA' => 1],
            [
                'NOMBREEMPRESA' => 'Empresa Principal RRHH',
                'ABREVIATURA' => 'EPR',
                'URL_LOGO' => '/images/logos/empresa-1.svg',
                'EMPRESAACTIVA' => true,
            ]
        );

        DB::table('PERIODO_LABORAL')->updateOrInsert(
            ['ID_PERIODO' => 1],
            [
                'FECHAINICIO' => '2026-07-01 00:00:00',
                'FECHAFIN' => '2026-07-31 23:59:59',
                'DIAS' => 30,
                'CALPERIODO' => 'Julio 2026',
                'ESACTIVO' => true,
            ]
        );

        DB::table('CUENTA')->updateOrInsert(
            ['ID_CUENTA' => 1],
            [
                'ID_BANCO' => 1,
                'CONCEPTOCUENTA' => 'Cuenta Principal Planilla',
                'NUMEROCUENTA' => '1234567890',
                'CENTROCOSTO_CODIGO' => 'CC01',
                'ESACTIVO' => true,
            ]
        );

        // Segunda empresa de demostración
        DB::table('EMPRESA')->updateOrInsert(
            ['ID_EMPRESA' => 2],
            [
                'NOMBREEMPRESA' => 'Distribuidora El Salvador S.A. de C.V.',
                'ABREVIATURA' => 'DESA',
                'URL_LOGO' => '/images/logos/empresa-2.svg',
                'NUMERONIT' => '0614-250726-001-5',
                'DIRECCION' => 'Blvd. Constitución #125, San Salvador',
                'TELEFONO' => '2222-5500',
                'EMPRESAACTIVA' => true,
                'ID_DISTRITO' => 110,
            ]
        );

        DB::table('CENTRO_COSTO')->updateOrInsert(
            ['ID_CENTROCOSTO' => 2],
            [
                'ID_EMPRESA' => 2,
                'CODIGO_CENTROCOSTO' => 'CC-VENTAS',
                'NOMBRE_CENTROCOSTO' => 'Ventas y Distribución',
                'DESCRIPCION' => 'Centro de costo comercial',
                'ESACTIVO' => true,
            ]
        );

        DB::table('AREA')->updateOrInsert(
            ['ID_AREA' => 2],
            [
                'ID_EMPRESA' => 2,
                'NOMBREAREA' => 'Operaciones',
                'ACTIVA' => true,
                'PRORRATEADA' => false,
            ]
        );

        DB::table('DEPARTAMENTO')->updateOrInsert(
            ['ID_DEPARTAMENTO' => 2],
            [
                'ID_EMPRESA' => 2,
                'ID_AREA' => 2,
                'NOMBREDEPARTAMENTO' => 'Recursos Humanos y Nómina',
                'DESCRIPCION' => 'Gestión de personal',
                'MANO_OBRA_DIRECTA' => false,
            ]
        );

        DB::table('CARGO')->updateOrInsert(
            ['ID_CARGO' => 2],
            ['ID_DEPARTAMENTO' => 2, 'NOMBRECARGO' => 'Analista de Nómina', 'CARGOESTADO' => true, 'NIVEL_JERARQUICO' => 2]
        );
        DB::table('CARGO')->updateOrInsert(
            ['ID_CARGO' => 3],
            ['ID_DEPARTAMENTO' => 2, 'NOMBRECARGO' => 'Auxiliar Contable', 'CARGOESTADO' => true, 'NIVEL_JERARQUICO' => 3]
        );
        DB::table('CARGO')->updateOrInsert(
            ['ID_CARGO' => 4],
            ['ID_DEPARTAMENTO' => 2, 'NOMBRECARGO' => 'Vendedor', 'CARGOESTADO' => true, 'NIVEL_JERARQUICO' => 4]
        );

        // Estructura empresa principal (ID 1)
        DB::table('AREA')->updateOrInsert(
            ['ID_AREA' => 1],
            ['ID_EMPRESA' => 1, 'NOMBREAREA' => 'Administración', 'ACTIVA' => true, 'PRORRATEADA' => false]
        );
        DB::table('DEPARTAMENTO')->updateOrInsert(
            ['ID_DEPARTAMENTO' => 1],
            ['ID_EMPRESA' => 1, 'ID_AREA' => 1, 'NOMBREDEPARTAMENTO' => 'Finanzas', 'DESCRIPCION' => 'Finanzas', 'MANO_OBRA_DIRECTA' => false]
        );
        DB::table('CARGO')->updateOrInsert(
            ['ID_CARGO' => 1],
            ['ID_DEPARTAMENTO' => 1, 'NOMBRECARGO' => 'Gerente General', 'CARGOESTADO' => true, 'NIVEL_JERARQUICO' => 1]
        );
        DB::table('CENTRO_COSTO')->updateOrInsert(
            ['ID_CENTROCOSTO' => 1],
            ['ID_EMPRESA' => 1, 'CODIGO_CENTROCOSTO' => 'CC-ADM', 'NOMBRE_CENTROCOSTO' => 'Administración', 'ESACTIVO' => true]
        );

        DB::table('EMPRESA')->where('ID_EMPRESA', 1)->update([
            'NUMERONIT' => '0614-010126-102-3',
            'URL_LOGO' => '/images/logos/empresa-1.svg',
            'TELEFONO' => '2234-5600',
            'DIRECCION' => 'Calle Principal #100, San Salvador',
            'NOMBRE_DUENO' => 'Roberto García Pineda',
            'DUI_DUENO' => '01234567-8',
        ]);

        $firmantesDemo = [
            ['ID_FIRMANTE' => 1, 'ID_EMPRESA' => 1, 'NOMBRE' => 'Roberto García Pineda', 'CARGO' => 'Representante Legal', 'DUI' => '01234567-8', 'ORDEN' => 1, 'ESACTIVO' => true],
            ['ID_FIRMANTE' => 2, 'ID_EMPRESA' => 1, 'NOMBRE' => 'Laura Méndez de García', 'CARGO' => 'Gerente de Recursos Humanos', 'DUI' => '02345678-9', 'ORDEN' => 2, 'ESACTIVO' => true],
            ['ID_FIRMANTE' => 3, 'ID_EMPRESA' => 2, 'NOMBRE' => 'Carlos Eduardo Rivas', 'CARGO' => 'Representante Legal', 'DUI' => '03456789-0', 'ORDEN' => 1, 'ESACTIVO' => true],
            ['ID_FIRMANTE' => 4, 'ID_EMPRESA' => 2, 'NOMBRE' => 'María Elena Rodríguez', 'CARGO' => 'Jefe de Nómina', 'DUI' => '04567890-1', 'ORDEN' => 2, 'ESACTIVO' => true],
        ];
        foreach ($firmantesDemo as $firmante) {
            DB::table('EMPRESA_FIRMANTE')->updateOrInsert(['ID_FIRMANTE' => $firmante['ID_FIRMANTE']], $firmante);
        }

        $empleados = [
            [
                'ID_EMPLEADO' => 1,
                'ID_EMPRESA' => 1,
                'ID_DEPARTAMENTO' => 1,
                'ID_CARGO' => 1,
                'ID_CENTROCOSTO' => 1,
                'ID_TIPOCONTRATACION' => 1,
                'ID_AFP' => 1,
                'ID_BANCO' => 1,
                'ID_DISTRITO' => 110,
                'CODIGOEMPLEADO' => 'EMP-001',
                'NOMBRES' => 'Carlos Alberto',
                'APELLIDO_1' => 'Martínez',
                'APELLIDO_2' => 'López',
                'DUI' => '01234567-8',
                'NIT' => '0614-010180-001-2',
                'ISSS' => '123456789',
                'GENERO' => 'M',
                'FECHANACIMIENTO' => '1985-03-15',
                'FECHAINGRESO' => '2020-01-15',
                'SALARIOMENSUAL' => 1200.00,
                'SALARIODIARIO' => 40.00,
                'NUMEROCUENTA' => '001234567890',
                'ESACTIVO' => true,
            ],
            [
                'ID_EMPLEADO' => 2,
                'ID_EMPRESA' => 2,
                'ID_DEPARTAMENTO' => 2,
                'ID_CARGO' => 2,
                'ID_CENTROCOSTO' => 2,
                'ID_TIPOCONTRATACION' => 1,
                'ID_AFP' => 1,
                'ID_BANCO' => 2,
                'ID_DISTRITO' => 110,
                'CODIGOEMPLEADO' => 'DESA-001',
                'NOMBRES' => 'María Elena',
                'APELLIDO_1' => 'Rodríguez',
                'APELLIDO_2' => 'Hernández',
                'DUI' => '02345678-9',
                'NIT' => '0614-050590-002-3',
                'ISSS' => '987654321',
                'GENERO' => 'F',
                'FECHANACIMIENTO' => '1990-05-05',
                'FECHAINGRESO' => '2022-06-01',
                'SALARIOMENSUAL' => 850.00,
                'SALARIODIARIO' => 28.3333,
                'NUMEROCUENTA' => '009876543210',
                'ESACTIVO' => true,
            ],
            [
                'ID_EMPLEADO' => 3,
                'ID_EMPRESA' => 2,
                'ID_DEPARTAMENTO' => 2,
                'ID_CARGO' => 3,
                'ID_CENTROCOSTO' => 2,
                'ID_TIPOCONTRATACION' => 1,
                'ID_AFP' => 2,
                'ID_BANCO' => 1,
                'ID_DISTRITO' => 110,
                'CODIGOEMPLEADO' => 'DESA-002',
                'NOMBRES' => 'José Antonio',
                'APELLIDO_1' => 'García',
                'APELLIDO_2' => 'Pérez',
                'DUI' => '03456789-0',
                'NIT' => '0614-120885-003-4',
                'ISSS' => '456789123',
                'GENERO' => 'M',
                'FECHANACIMIENTO' => '1988-08-12',
                'FECHAINGRESO' => '2021-03-10',
                'SALARIOMENSUAL' => 650.00,
                'SALARIODIARIO' => 21.6667,
                'NUMEROCUENTA' => '005566778899',
                'ESACTIVO' => true,
            ],
            [
                'ID_EMPLEADO' => 4,
                'ID_EMPRESA' => 2,
                'ID_DEPARTAMENTO' => 2,
                'ID_CARGO' => 4,
                'ID_CENTROCOSTO' => 2,
                'ID_TIPOCONTRATACION' => 1,
                'ID_AFP' => 1,
                'ID_BANCO' => 3,
                'ID_DISTRITO' => 110,
                'CODIGOEMPLEADO' => 'DESA-003',
                'NOMBRES' => 'Ana Lucía',
                'APELLIDO_1' => 'Vásquez',
                'APELLIDO_2' => 'Morales',
                'DUI' => '04567890-1',
                'NIT' => '0614-220795-004-5',
                'ISSS' => '789123456',
                'GENERO' => 'F',
                'FECHANACIMIENTO' => '1995-07-22',
                'FECHAINGRESO' => '2023-01-20',
                'SALARIOMENSUAL' => 500.00,
                'SALARIODIARIO' => 16.6667,
                'NUMEROCUENTA' => '001122334455',
                'ESACTIVO' => true,
            ],
        ];

        foreach ($empleados as $emp) {
            DB::table('EMPLEADO')->updateOrInsert(['ID_EMPLEADO' => $emp['ID_EMPLEADO']], $emp);
        }

        // Descuentos por empleado (catálogo dinámico)
        $descuentos = [
            ['ID_DESCUENTOEMPLEADO' => 1, 'ID_EMPLEADO' => 1, 'ID_TIPODESCUENTO' => 21, 'MONTO' => 25.00, 'ES_PORCENTAJE' => false, 'FECHAINICIO' => '2026-01-01', 'FECHAFIN' => null, 'ES_RECURRENTE' => true, 'ESACTIVO' => true],
            ['ID_DESCUENTOEMPLEADO' => 2, 'ID_EMPLEADO' => 2, 'ID_TIPODESCUENTO' => 4, 'MONTO' => 75.00, 'ES_PORCENTAJE' => false, 'FECHAINICIO' => '2026-01-01', 'FECHAFIN' => null, 'ES_RECURRENTE' => true, 'ESACTIVO' => true],
            ['ID_DESCUENTOEMPLEADO' => 3, 'ID_EMPLEADO' => 2, 'ID_TIPODESCUENTO' => 21, 'MONTO' => 15.00, 'ES_PORCENTAJE' => false, 'FECHAINICIO' => '2026-01-01', 'FECHAFIN' => null, 'ES_RECURRENTE' => true, 'ESACTIVO' => true],
            ['ID_DESCUENTOEMPLEADO' => 4, 'ID_EMPLEADO' => 3, 'ID_TIPODESCUENTO' => 23, 'MONTO' => 0, 'PORCENTAJE' => 2.5, 'ES_PORCENTAJE' => true, 'FECHAINICIO' => '2026-01-01', 'FECHAFIN' => null, 'ES_RECURRENTE' => true, 'ESACTIVO' => true],
            ['ID_DESCUENTOEMPLEADO' => 5, 'ID_EMPLEADO' => 4, 'ID_TIPODESCUENTO' => 22, 'MONTO' => 20.00, 'ES_PORCENTAJE' => false, 'FECHAINICIO' => '2026-01-01', 'FECHAFIN' => null, 'ES_RECURRENTE' => true, 'ESACTIVO' => true],
        ];

        foreach ($descuentos as $desc) {
            DB::table('DESCUENTO_EMPLEADO')->updateOrInsert(['ID_DESCUENTOEMPLEADO' => $desc['ID_DESCUENTOEMPLEADO']], $desc);
        }

        // Préstamo de demostración
        DB::table('PRESTAMOS')->updateOrInsert(
            ['ID_PRESTAMO' => 1],
            [
                'ID_EMPLEADO' => 3,
                'ID_TIPODESCUENTO' => 10,
                'ID_TIPOPRESTAMO' => 2,
                'MONTOPRESTAMO' => 1200.00,
                'CUOTA' => 100.00,
                'NUMCUOTAS' => 12,
                'SALDO_ACTUAL' => 800.00,
                'FECHAINICIO' => '2026-01-01 00:00:00',
                'FECHAFINALIZACION' => '2026-12-31 23:59:59',
                'PRESTAMOESTADO' => true,
            ]
        );

        // Planilla de demostración para empresa 2 (Distribuidora)
        DB::table('PLANILLA')->updateOrInsert(
            ['ID_PLANILLA' => 1],
            [
                'ID_EMPRESA' => 2,
                'ID_TIPOPLANILLA' => 1,
                'ID_PERIODO' => 1,
                'ID_FRECUENCIAPAGO' => 1,
                'ID_CUENTA' => 1,
                'TITULO' => 'Planilla Ordinaria Julio 2026 - DESA',
                'FECHAPAGO' => '2026-07-30',
                'FORMAPAGO' => 'Transferencia',
                'OBSERVACION' => 'Planilla de demostración generada por seeder',
                'ESACTIVA' => true,
                'CERRADA' => false,
                'ANULADA' => false,
                'CONTABILIZADA' => false,
                'RECALCULADA' => false,
            ]
        );

        $this->calcularPlanillaDemo(1);
    }
}
