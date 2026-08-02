<?php

namespace Database\Seeders;

use App\Services\PayrollCalculatorService;
use App\Services\PayrollRentRecalculationService;
use Database\Seeders\Concerns\CalculatesDemoPayroll;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ejercicio quincenal Ene–Jun 2026 (1ra quincena) + vacaciones + recálculo ISR de junio.
 *
 * Empleados demo (GICA, tipo contratación 1 — aplica tabla ISR):
 * - 401: $880/mes
 * - 402: $1,200/mes → recibe planilla de vacaciones en marzo (planilla 26)
 * - 403: $2,000/mes
 * - 404: $3,000/mes
 *
 * Planillas ordinarias 20–25 | Vacaciones 26 (mar) | Periodos 10–16.
 */
class DemoQuincenalJunioRecalculoSeeder extends Seeder
{
    use CalculatesDemoPayroll;

    protected int $empresaId = 3;

    protected int $planillaVacacionesId = 26;

    protected int $periodoVacacionesId = 16;

    /** @var array<int, array{salario: float, nombre: string}>> */
    protected array $empleadosDemo = [
        401 => ['salario' => 880.00, 'nombre' => 'Carlos Vega ($880)'],
        402 => ['salario' => 1200.00, 'nombre' => 'María López ($1,200)'],
        403 => ['salario' => 2000.00, 'nombre' => 'José Herrera ($2,000)'],
        404 => ['salario' => 3000.00, 'nombre' => 'Ana Rivas ($3,000)'],
    ];

    /** @var list<array{id: int, mes: int, inicio: string, fin: string, label: string, pago: string}>> */
    protected array $quincenas = [
        ['id' => 10, 'mes' => 1, 'inicio' => '2026-01-01', 'fin' => '2026-01-15', 'label' => 'Ene 2026 - 1ra quincena', 'pago' => '2026-01-15'],
        ['id' => 11, 'mes' => 2, 'inicio' => '2026-02-01', 'fin' => '2026-02-15', 'label' => 'Feb 2026 - 1ra quincena', 'pago' => '2026-02-15'],
        ['id' => 12, 'mes' => 3, 'inicio' => '2026-03-01', 'fin' => '2026-03-15', 'label' => 'Mar 2026 - 1ra quincena', 'pago' => '2026-03-15'],
        ['id' => 13, 'mes' => 4, 'inicio' => '2026-04-01', 'fin' => '2026-04-15', 'label' => 'Abr 2026 - 1ra quincena', 'pago' => '2026-04-15'],
        ['id' => 14, 'mes' => 5, 'inicio' => '2026-05-01', 'fin' => '2026-05-15', 'label' => 'May 2026 - 1ra quincena', 'pago' => '2026-05-15'],
        ['id' => 15, 'mes' => 6, 'inicio' => '2026-06-01', 'fin' => '2026-06-15', 'label' => 'Jun 2026 - 1ra quincena (recálculo ISR)', 'pago' => '2026-06-15'],
    ];

    public function run(): void
    {
        $this->seedPeriodos();
        $this->seedEmpleados();
        $this->limpiarPlanillasEjercicio();
        $this->seedYCalcularPlanillas();
        $this->imprimirReporteVerificacion();
    }

    protected function seedPeriodos(): void
    {
        foreach ($this->quincenas as $q) {
            DB::table('PERIODO_LABORAL')->updateOrInsert(
                ['ID_PERIODO' => $q['id']],
                [
                    'FECHAINICIO' => $q['inicio'] . ' 00:00:00',
                    'FECHAFIN' => $q['fin'] . ' 23:59:59',
                    'DIAS' => 15,
                    'CALPERIODO' => $q['label'],
                    'ESACTIVO' => true,
                ]
            );
        }

        DB::table('PERIODO_LABORAL')->updateOrInsert(
            ['ID_PERIODO' => $this->periodoVacacionesId],
            [
                'FECHAINICIO' => '2026-03-16 00:00:00',
                'FECHAFIN' => '2026-03-30 23:59:59',
                'DIAS' => 15,
                'CALPERIODO' => 'Mar 2026 - Vacaciones (15 días)',
                'ESACTIVO' => true,
            ]
        );
    }

    protected function seedEmpleados(): void
    {
        foreach ($this->empleadosDemo as $id => $info) {
            $salario = (float) $info['salario'];
            DB::table('EMPLEADO')->updateOrInsert(
                ['ID_EMPLEADO' => $id],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_DEPARTAMENTO' => 13,
                    'ID_CARGO' => 14,
                    'ID_CENTROCOSTO' => 3,
                    'ID_TIPOCONTRATACION' => 1,
                    'ID_AFP' => 1,
                    'ID_BANCO' => 1,
                    'ID_DISTRITO' => 110,
                    'CODIGOEMPLEADO' => sprintf('GICA-EJ-%d', $id),
                    'NOMBRES' => explode(' ', $info['nombre'])[0],
                    'APELLIDO_1' => 'Ejercicio',
                    'APELLIDO_2' => 'Junio',
                    'DUI' => sprintf('0400000%d-%d', $id, $id % 10),
                    'NIT' => sprintf('0614-040401-%03d-%d', $id, $id % 10),
                    'ISSS' => sprintf('40100000%d', $id),
                    'GENERO' => ($id % 2 === 1) ? 'M' : 'F',
                    'FECHANACIMIENTO' => '1990-01-15',
                    'FECHAINGRESO' => '2025-01-02',
                    'SALARIOMENSUAL' => $salario,
                    'SALARIODIARIO' => round($salario / 30, 4),
                    'NUMEROCUENTA' => sprintf('5024010000%03d', $id),
                    'ESACTIVO' => true,
                ]
            );
        }
    }

    protected function limpiarPlanillasEjercicio(): void
    {
        $planillaIds = range(20, 25);
        $planillaIds[] = $this->planillaVacacionesId;

        DB::table('ACUMULADO_RECALCULO')->whereIn('ID_PLANILLA', $planillaIds)->delete();

        foreach ($planillaIds as $planillaId) {
            DB::table('DETALLE_DESCUENTO_PLANILLA')
                ->whereIn('ID_DETALLEPLANILLA', function ($q) use ($planillaId) {
                    $q->select('ID_DETALLEPLANILLA')->from('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId);
                })
                ->delete();
            DB::table('DETALLE_PLANILLA')->where('ID_PLANILLA', $planillaId)->delete();
            DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update(['RECALCULADA' => false]);
        }
    }

    protected function seedYCalcularPlanillas(): void
    {
        $empleadoIds = array_keys($this->empleadosDemo);
        $planillaId = 20;

        foreach ($this->quincenas as $q) {
            DB::table('PLANILLA')->updateOrInsert(
                ['ID_PLANILLA' => $planillaId],
                [
                    'ID_EMPRESA' => $this->empresaId,
                    'ID_TIPOPLANILLA' => 1,
                    'ID_PERIODO' => $q['id'],
                    'ID_FRECUENCIAPAGO' => 2,
                    'ID_CUENTA' => 1,
                    'TITULO' => 'Quincenal 1ra - ' . $q['label'] . ' (ejercicio ISR)',
                    'FECHAPAGO' => $q['pago'],
                    'FORMAPAGO' => 'Transferencia',
                    'OBSERVACION' => $q['mes'] === 6
                        ? 'Planilla ordinaria con recálculo semestral (incluye vacaciones pagadas ene–may).'
                        : 'Planilla ordinaria quincenal — base para recálculo de junio.',
                    'ESACTIVA' => true,
                    'CERRADA' => false,
                    'ANULADA' => false,
                    'CONTABILIZADA' => false,
                    'RECALCULADA' => false,
                ]
            );

            if ($q['mes'] === 6) {
                DB::table('ACUMULADO_RECALCULO')->where('ID_PLANILLA', $planillaId)->delete();
            }

            $this->calcularPlanillaDemo($planillaId, $empleadoIds);

            if ($q['mes'] === 3) {
                $this->seedYCalcularPlanillaVacacionesMarzo();
            }

            $planillaId++;
        }
    }

    protected function seedYCalcularPlanillaVacacionesMarzo(): void
    {
        DB::table('PLANILLA')->updateOrInsert(
            ['ID_PLANILLA' => $this->planillaVacacionesId],
            [
                'ID_EMPRESA' => $this->empresaId,
                'ID_TIPOPLANILLA' => 2,
                'ID_PERIODO' => $this->periodoVacacionesId,
                'ID_FRECUENCIAPAGO' => 2,
                'ID_CUENTA' => 1,
                'TITULO' => 'Vacaciones Mar 2026 - María López (ejercicio ISR)',
                'FECHAPAGO' => '2026-03-30',
                'FORMAPAGO' => 'Transferencia',
                'OBSERVACION' => '15 días de vacación. Gravada, entra al acumulado de junio; retiene ISR normal (sin ajuste semestral).',
                'ESACTIVA' => true,
                'CERRADA' => false,
                'ANULADA' => false,
                'CONTABILIZADA' => false,
                'RECALCULADA' => false,
            ]
        );

        $this->calcularPlanillaDemo($this->planillaVacacionesId, [402]);
    }

    protected function imprimirReporteVerificacion(): void
    {
        $calculator = app(PayrollCalculatorService::class);
        $recalc = app(PayrollRentRecalculationService::class);

        $this->command?->newLine();
        $this->command?->info('═══════════════════════════════════════════════════════════════════');
        $this->command?->info('  EJERCICIO QUINCENAL + VACACIONES — VERIFICACIÓN RECÁLCULO JUNIO');
        $this->command?->info('═══════════════════════════════════════════════════════════════════');
        $this->command?->line('  Regla: vacaciones APLICA_RENTA → suman al acumulado ene–may.');
        $this->command?->line('  El ajuste de junio solo se aplica en planilla ORDINARIA (tipo 1).');

        foreach ($this->empleadosDemo as $empId => $info) {
            $this->command?->newLine();
            $this->command?->info("── Empleado {$empId}: {$info['nombre']} ──");

            $filas = DB::table('DETALLE_PLANILLA')
                ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
                ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
                ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
                ->where('DETALLE_PLANILLA.ID_EMPLEADO', $empId)
                ->where(function ($q) {
                    $q->whereBetween('PLANILLA.ID_PLANILLA', [20, 25])
                        ->orWhere('PLANILLA.ID_PLANILLA', $this->planillaVacacionesId);
                })
                ->orderBy('PERIODO_LABORAL.FECHAFIN')
                ->orderBy('PLANILLA.ID_PLANILLA')
                ->select(
                    'PLANILLA.ID_PLANILLA',
                    'TIPO_PLANILLA.TIPOPLANILLA',
                    'PERIODO_LABORAL.CALPERIODO',
                    'DETALLE_PLANILLA.DEVENGADO_GRAVADO',
                    'DETALLE_PLANILLA.AFP_EMPLEADO',
                    'DETALLE_PLANILLA.ISSS_EMPLEADO',
                    'DETALLE_PLANILLA.RENTA_EMPLEADO',
                    'DETALLE_PLANILLA.LIQUIDO_A_RECIBIR'
                )
                ->get();

            $this->command?->table(
                ['Planilla', 'Tipo', 'Periodo', 'Devengado', 'AFP', 'ISSS', 'Renta', 'Líquido'],
                $filas->map(fn ($r) => [
                    $r->ID_PLANILLA,
                    $r->TIPOPLANILLA,
                    $r->CALPERIODO,
                    number_format((float) $r->DEVENGADO_GRAVADO, 2),
                    number_format((float) $r->AFP_EMPLEADO, 2),
                    number_format((float) $r->ISSS_EMPLEADO, 2),
                    number_format((float) $r->RENTA_EMPLEADO, 2),
                    number_format((float) $r->LIQUIDO_A_RECIBIR, 2),
                ])->all()
            );

            $junio = $filas->first(fn ($r) => (int) $r->ID_PLANILLA === 25);
            if (!$junio) {
                continue;
            }

            $baseIsrJun = (float) $junio->DEVENGADO_GRAVADO - (float) $junio->AFP_EMPLEADO - (float) $junio->ISSS_EMPLEADO;
            $rentaNormalJun = $calculator->calculateISR($baseIsrJun, 2);

            $planillaJun = \App\Models\Planilla::with('periodoLaboral')->find($junio->ID_PLANILLA);
            $baseAcum = $recalc->getBaseIsrAcumuladaPeriodo($empId, $planillaJun, 6, 2026);
            $rentaAcum = $recalc->getRentaAcumuladaPeriodo($empId, $planillaJun, 6, 2026);
            $baseTotal = $baseAcum + $baseIsrJun;
            $rentaDebida = $calculator->calculateISR($baseTotal, 1);
            $ajusteEsperado = round($rentaDebida - $rentaAcum - $rentaNormalJun, 2);
            $rentaFinalEsperada = round(max(0, $rentaNormalJun + $ajusteEsperado), 2);

            $vacacionMar = $filas->first(fn ($r) => (int) $r->ID_PLANILLA === $this->planillaVacacionesId);
            if ($vacacionMar) {
                $this->command?->line('  ↳ Incluye planilla vacaciones mar (26) en acumulado ene–may.');
            }

            $this->command?->line('  Recálculo junio — planilla ordinaria 25:');
            $this->command?->line(sprintf('    Base ISR acum. ene–may (+ vac.): $%s', number_format($baseAcum, 2)));
            $this->command?->line(sprintf('    Renta retenida ene–may (+ vac.): $%s', number_format($rentaAcum, 2)));
            $this->command?->line(sprintf('    Renta final junio ordinaria:     $%s %s',
                number_format((float) $junio->RENTA_EMPLEADO, 2),
                abs((float) $junio->RENTA_EMPLEADO - $rentaFinalEsperada) < 0.02 ? '✓' : '✗ REVISAR'
            ));
        }

        $this->command?->newLine();
        $this->command?->info('Ver: database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md');
    }
}
