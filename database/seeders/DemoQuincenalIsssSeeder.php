<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CalculatesDemoPayroll;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ejercicio quincenal ISSS / INSAFORP (techo mensual $1,000).
 *
 * Empleados en GICA (empresa con +10 activos → aplica INSAFORP):
 * - 301: $2,000/mes → $1,000 por quincena (justo en el techo en 1ra quincena)
 * - 302: $4,800/mes → $2,400 por quincena (devengado alto, ISSS/INSAFORP solo sobre $1,000 acumulables)
 *
 * Planilla 6: 1ra quincena Jul 2026 (calcular primero)
 * Planilla 7: 2da quincena Jul 2026 (calcular después → ISSS e INSAFORP en $0 por techo consumido)
 */
class DemoQuincenalIsssSeeder extends Seeder
{
    use CalculatesDemoPayroll;

    protected int $empresaId = 3;

    public function run(): void
    {
        $this->seedPeriodosQuincenales();
        $this->seedEmpleadosQuincenales();
        $this->seedPlanillasQuincenales();
    }

    protected function seedPeriodosQuincenales(): void
    {
        DB::table('PERIODO_LABORAL')->updateOrInsert(
            ['ID_PERIODO' => 3],
            [
                'FECHAINICIO' => '2026-07-01 00:00:00',
                'FECHAFIN' => '2026-07-15 23:59:59',
                'DIAS' => 15,
                'CALPERIODO' => 'Jul 2026 - 1ra quincena',
                'ESACTIVO' => true,
            ]
        );

        DB::table('PERIODO_LABORAL')->updateOrInsert(
            ['ID_PERIODO' => 4],
            [
                'FECHAINICIO' => '2026-07-16 00:00:00',
                'FECHAFIN' => '2026-07-31 23:59:59',
                'DIAS' => 15,
                'CALPERIODO' => 'Jul 2026 - 2da quincena',
                'ESACTIVO' => true,
            ]
        );
    }

    protected function seedEmpleadosQuincenales(): void
    {
        $empleados = [
            [
                'ID_EMPLEADO' => 301,
                'CODIGOEMPLEADO' => 'GICA-Q-301',
                'NOMBRES' => 'Roberto',
                'APELLIDO_1' => 'Salazar',
                'APELLIDO_2' => 'Mejía',
                'SALARIOMENSUAL' => 2000.00,
                'CARGO' => 14,
                'DEPARTAMENTO' => 13,
            ],
            [
                'ID_EMPLEADO' => 302,
                'CODIGOEMPLEADO' => 'GICA-Q-302',
                'NOMBRES' => 'Patricia',
                'APELLIDO_1' => 'Mendoza',
                'APELLIDO_2' => 'Castro',
                'SALARIOMENSUAL' => 4800.00,
                'CARGO' => 15,
                'DEPARTAMENTO' => 13,
            ],
        ];

        foreach ($empleados as $emp) {
            $salario = (float) $emp['SALARIOMENSUAL'];
            DB::table('EMPLEADO')->updateOrInsert(
                ['ID_EMPLEADO' => $emp['ID_EMPLEADO']],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_DEPARTAMENTO' => $emp['DEPARTAMENTO'],
                    'ID_CARGO' => $emp['CARGO'],
                    'ID_CENTROCOSTO' => 3,
                    'ID_TIPOCONTRATACION' => 1,
                    'ID_AFP' => 1,
                    'ID_BANCO' => 1,
                    'ID_DISTRITO' => 110,
                    'CODIGOEMPLEADO' => $emp['CODIGOEMPLEADO'],
                    'NOMBRES' => $emp['NOMBRES'],
                    'APELLIDO_1' => $emp['APELLIDO_1'],
                    'APELLIDO_2' => $emp['APELLIDO_2'],
                    'DUI' => sprintf('0300000%d-%d', $emp['ID_EMPLEADO'], $emp['ID_EMPLEADO'] % 10),
                    'NIT' => sprintf('0614-030301-%03d-%d', $emp['ID_EMPLEADO'], $emp['ID_EMPLEADO'] % 10),
                    'ISSS' => sprintf('30100000%d', $emp['ID_EMPLEADO']),
                    'GENERO' => ($emp['ID_EMPLEADO'] % 2 === 1) ? 'M' : 'F',
                    'FECHANACIMIENTO' => '1988-06-15',
                    'FECHAINGRESO' => '2024-03-01',
                    'SALARIOMENSUAL' => $salario,
                    'SALARIODIARIO' => round($salario / 30, 4),
                    'NUMEROCUENTA' => sprintf('5023010000%03d', $emp['ID_EMPLEADO']),
                    'ESACTIVO' => true,
                ]
            );
        }
    }

    protected function seedPlanillasQuincenales(): void
    {
        $empleados = [301, 302];

        // Limpiar ambas quincenas antes de recalcular (evita acumulados incorrectos)
        foreach ([6, 7] as $planillaId) {
            DB::table('DETALLE_DESCUENTO_PLANILLA')
                ->whereIn('ID_DETALLEPLANILLA', function ($q) use ($planillaId) {
                    $q->select('ID_DETALLEPLANILLA')->from('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId);
                })
                ->delete();
            DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId)->delete();
            DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update(['RECALCULADA' => false]);
        }

        $planillas = [
            [
                'ID_PLANILLA' => 6,
                'TITULO' => 'Quincenal 1ra - Jul 2026 GICA (ISSS/INSAFORP demo)',
                'ID_PERIODO' => 3,
                'FECHAPAGO' => '2026-07-15',
                'OBSERVACION' => 'Validar: ISSS emp $30, ISSS pat $75, INSAFORP $10 c/u (base $1,000). Roberto devenga $1,000; Patricia $2,400 pero ISSS igual sobre $1,000.',
            ],
            [
                'ID_PLANILLA' => 7,
                'TITULO' => 'Quincenal 2da - Jul 2026 GICA (ISSS/INSAFORP demo)',
                'ID_PERIODO' => 4,
                'FECHAPAGO' => '2026-07-31',
                'OBSERVACION' => 'Validar: ISSS e INSAFORP en $0 — techo mensual $1,000 ya consumido en planilla 6.',
            ],
        ];

        foreach ($planillas as $plan) {
            DB::table('PLANILLA')->updateOrInsert(
                ['ID_PLANILLA' => $plan['ID_PLANILLA']],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_TIPOPLANILLA' => 1,
                    'ID_PERIODO' => $plan['ID_PERIODO'],
                    'ID_FRECUENCIAPAGO' => 2,
                    'ID_CUENTA' => 1,
                    'TITULO' => $plan['TITULO'],
                    'FECHAPAGO' => $plan['FECHAPAGO'],
                    'FORMAPAGO' => 'Transferencia',
                    'OBSERVACION' => $plan['OBSERVACION'],
                    'ESACTIVA' => true,
                    'CERRADA' => false,
                    'ANULADA' => false,
                    'CONTABILIZADA' => false,
                    'RECALCULADA' => false,
                ]
            );

            $this->calcularPlanillaDemo($plan['ID_PLANILLA'], $empleados);
        }
    }
}
