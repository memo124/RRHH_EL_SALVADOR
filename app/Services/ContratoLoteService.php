<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\PlantillaContrato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContratoLoteService
{
    public function __construct(
        protected ContratoTemplateService $templates,
        protected ContratoBeneficiosService $beneficios,
    ) {
    }

    /**
     * @return array{total_elegibles: int, total_excluidos: int, empleados: array<int, array>, resumen: array}
     */
    public function preview(array $params): array
    {
        $elegibles = $this->buscarEmpleadosElegibles($params);
        $fechaReferencia = $this->fechaReferencia($params);
        $detalle = [];

        foreach ($elegibles as $empleado) {
            $detalle[] = $this->filaEmpleado($empleado, $fechaReferencia);
        }

        return $this->armarRespuesta($detalle, $params);
    }

    /**
     * @return array{generados: int, omitidos: int, contratos: array<int, array>, errores: array<int, string>}
     */
    public function generar(array $params): array
    {
        $plantilla = PlantillaContrato::findOrFail($params['ID_PLANTILLA']);
        $elegibles = $this->buscarEmpleadosElegibles($params);
        $fechaReferencia = $this->fechaReferencia($params);
        $fechaInicio = !empty($params['FECHA_INICIO']) ? Carbon::parse($params['FECHA_INICIO']) : null;

        $generados = [];
        $errores = [];
        $omitidos = 0;

        DB::transaction(function () use (
            $params,
            $plantilla,
            $elegibles,
            $fechaReferencia,
            $fechaInicio,
            &$generados,
            &$errores,
            &$omitidos
        ) {
            if (!empty($params['MARCAR_ANTERIORES_VENCIDOS']) && $fechaInicio) {
                $this->marcarContratosAnterioresVencidos(
                    (int) $params['ID_EMPRESA'],
                    (int) $params['ID_TIPOCONTRATACION'],
                    $fechaInicio
                );
            }

            $nextId = (Contrato::max('ID_CONTRATO') ?? 0) + 1;
            $secuencia = Contrato::where('ID_EMPRESA', $params['ID_EMPRESA'])->count() + 1;

            foreach ($elegibles as $empleado) {
                try {
                    if ($this->tieneContratoSolapante($empleado->ID_EMPLEADO, $params)) {
                        $omitidos++;
                        continue;
                    }

                    $contrato = new Contrato();
                    $contrato->ID_CONTRATO = $nextId++;
                    $contrato->ID_EMPLEADO = $empleado->ID_EMPLEADO;
                    $contrato->ID_EMPRESA = (int) $params['ID_EMPRESA'];
                    $contrato->ID_PLANTILLA = $plantilla->ID_PLANTILLA;
                    $contrato->NUMERO_CONTRATO = !empty($params['PREFIJO_NUMERO'])
                        ? sprintf('%s-%04d', rtrim($params['PREFIJO_NUMERO'], '-'), $secuencia++)
                        : sprintf('CONT-%d-%04d', $params['ID_EMPRESA'], $secuencia++);
                    $contrato->FECHA_INICIO = $params['FECHA_INICIO'] ?? null;
                    $contrato->FECHA_FIN = !empty($params['SIN_FECHA_DEFINIDA']) ? null : ($params['FECHA_FIN'] ?? null);
                    $contrato->SIN_FECHA_DEFINIDA = !empty($params['SIN_FECHA_DEFINIDA']);
                    $contrato->SALARIO = $params['SALARIO'] ?? $empleado->SALARIOMENSUAL;
                    $contrato->OBSERVACIONES = $params['OBSERVACIONES'] ?? 'Generado por lote';
                    $contrato->ESTADO = 'VIGENTE';
                    $contrato->ESACTIVO = true;
                    $contrato->FECHA_CREACION = now();
                    $contrato->CONTENIDO_GENERADO = $this->templates->render($plantilla, $contrato, [], $fechaReferencia);
                    $contrato->save();

                    $generados[] = [
                        'ID_CONTRATO' => $contrato->ID_CONTRATO,
                        'ID_EMPLEADO' => $empleado->ID_EMPLEADO,
                        'NOM_EMPLEADO' => $this->nombreEmpleado($empleado),
                        'NUMERO_CONTRATO' => $contrato->NUMERO_CONTRATO,
                    ];
                } catch (\Throwable $e) {
                    $errores[$empleado->ID_EMPLEADO] = $e->getMessage();
                }
            }
        });

        return [
            'generados' => count($generados),
            'omitidos' => $omitidos,
            'contratos' => $generados,
            'errores' => $errores,
        ];
    }

    /**
     * @return Collection<int, Empleado>
     */
    private function buscarEmpleadosElegibles(array $params): Collection
    {
        $query = Empleado::query()
            ->with('tipoContratacion')
            ->where('ID_EMPRESA', $params['ID_EMPRESA'])
            ->where('ID_TIPOCONTRATACION', $params['ID_TIPOCONTRATACION'])
            ->where('ESACTIVO', true)
            ->orderBy('NOMBRES')
            ->orderBy('APELLIDO_1');

        if (!empty($params['SOLO_SIN_VIGENTE'])) {
            $query->whereNotExists(function ($sub) use ($params) {
                $sub->select(DB::raw(1))
                    ->from('CONTRATO')
                    ->whereColumn('CONTRATO.ID_EMPLEADO', 'EMPLEADO.ID_EMPLEADO')
                    ->where('CONTRATO.ID_EMPRESA', $params['ID_EMPRESA'])
                    ->where('CONTRATO.ESACTIVO', true)
                    ->where('CONTRATO.ESTADO', 'VIGENTE');
            });
        } elseif (!empty($params['RENOVAR_VENCIDOS']) && !empty($params['FECHA_INICIO'])) {
            $fechaInicio = Carbon::parse($params['FECHA_INICIO'])->toDateString();
            $query->whereNotExists(function ($sub) use ($fechaInicio) {
                $sub->select(DB::raw(1))
                    ->from('CONTRATO')
                    ->whereColumn('CONTRATO.ID_EMPLEADO', 'EMPLEADO.ID_EMPLEADO')
                    ->where('CONTRATO.ESACTIVO', true)
                    ->where('CONTRATO.ESTADO', 'VIGENTE')
                    ->where(function ($q) use ($fechaInicio) {
                        $q->where('CONTRATO.SIN_FECHA_DEFINIDA', true)
                            ->orWhereNull('CONTRATO.FECHA_FIN')
                            ->orWhere('CONTRATO.FECHA_FIN', '>=', $fechaInicio);
                    });
            });
        }

        return $query->get();
    }

    private function tieneContratoSolapante(int $empleadoId, array $params): bool
    {
        if (empty($params['FECHA_INICIO']) || !empty($params['SIN_FECHA_DEFINIDA'])) {
            return Contrato::where('ID_EMPLEADO', $empleadoId)
                ->where('ID_EMPRESA', $params['ID_EMPRESA'])
                ->where('ESACTIVO', true)
                ->where('ESTADO', 'VIGENTE')
                ->where('SIN_FECHA_DEFINIDA', true)
                ->exists();
        }

        $inicio = Carbon::parse($params['FECHA_INICIO']);
        $fin = !empty($params['FECHA_FIN']) ? Carbon::parse($params['FECHA_FIN']) : null;

        return Contrato::where('ID_EMPLEADO', $empleadoId)
            ->where('ID_EMPRESA', $params['ID_EMPRESA'])
            ->where('ESACTIVO', true)
            ->where('ESTADO', 'VIGENTE')
            ->where(function ($q) use ($inicio, $fin) {
                $q->where('SIN_FECHA_DEFINIDA', true)
                    ->orWhere(function ($q2) use ($inicio, $fin) {
                        $q2->where(function ($q3) use ($fin) {
                            if ($fin) {
                                $q3->whereNull('FECHA_INICIO')->orWhere('FECHA_INICIO', '<=', $fin);
                            }
                        })->where(function ($q3) use ($inicio) {
                            $q3->whereNull('FECHA_FIN')->orWhere('FECHA_FIN', '>=', $inicio);
                        });
                    });
            })
            ->exists();
    }

    private function marcarContratosAnterioresVencidos(int $empresaId, int $tipoContratacionId, Carbon $fechaInicio): void
    {
        $empleadoIds = Empleado::where('ID_EMPRESA', $empresaId)
            ->where('ID_TIPOCONTRATACION', $tipoContratacionId)
            ->where('ESACTIVO', true)
            ->pluck('ID_EMPLEADO');

        Contrato::whereIn('ID_EMPLEADO', $empleadoIds)
            ->where('ID_EMPRESA', $empresaId)
            ->where('ESACTIVO', true)
            ->where('ESTADO', 'VIGENTE')
            ->where('SIN_FECHA_DEFINIDA', false)
            ->whereNotNull('FECHA_FIN')
            ->where('FECHA_FIN', '<', $fechaInicio->toDateString())
            ->update(['ESTADO' => 'VENCIDO']);
    }

    private function filaEmpleado(Empleado $empleado, Carbon $fechaReferencia): array
    {
        $benef = $this->beneficios->calcularParaEmpleado($empleado, $fechaReferencia);

        return [
            'ID_EMPLEADO' => $empleado->ID_EMPLEADO,
            'NOM_EMPLEADO' => $this->nombreEmpleado($empleado),
            'CODIGOEMPLEADO' => $empleado->CODIGOEMPLEADO,
            'SALARIOMENSUAL' => (float) $empleado->SALARIOMENSUAL,
            'ANTIGUEDAD_ANIOS' => $benef['ANTIGUEDAD_ANIOS'],
            'ANTIGUEDAD_TEXTO' => $benef['ANTIGUEDAD_TEXTO'],
            'AGUINALDO_APLICA' => $benef['AGUINALDO_APLICA'],
            'DIAS_AGUINALDO' => $benef['DIAS_AGUINALDO'],
            'MONTO_AGUINALDO' => $benef['MONTO_AGUINALDO'],
            'QUINCENA25_APLICA' => $benef['QUINCENA25_APLICA'],
            'QUINCENA25_MONTO' => $benef['QUINCENA25_MONTO'],
            'QUINCENA25_DETALLE' => $benef['QUINCENA25_DETALLE'],
        ];
    }

    private function armarRespuesta(array $detalle, array $params): array
    {
        $limite = (int) ($params['LIMITE_DETALLE'] ?? 100);
        $muestra = array_slice($detalle, 0, $limite);

        $conAguinaldo = count(array_filter($detalle, fn ($r) => $r['AGUINALDO_APLICA']));
        $conQuincena25 = count(array_filter($detalle, fn ($r) => $r['QUINCENA25_APLICA']));

        return [
            'total_elegibles' => count($detalle),
            'total_excluidos' => 0,
            'empleados' => $muestra,
            'truncado' => count($detalle) > $limite,
            'resumen' => [
                'con_aguinaldo' => $conAguinaldo,
                'con_quincena25' => $conQuincena25,
                'sin_quincena25' => count($detalle) - $conQuincena25,
            ],
        ];
    }

    private function fechaReferencia(array $params): Carbon
    {
        if (!empty($params['FECHA_INICIO'])) {
            return Carbon::parse($params['FECHA_INICIO']);
        }

        return Carbon::now();
    }

    private function nombreEmpleado(Empleado $empleado): string
    {
        return trim(($empleado->NOMBRES ?? '') . ' ' . ($empleado->APELLIDO_1 ?? '') . ' ' . ($empleado->APELLIDO_2 ?? ''));
    }
}
