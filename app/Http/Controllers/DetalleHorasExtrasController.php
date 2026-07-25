<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Planilla;
use App\Services\HorasExtrasCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetalleHorasExtrasController extends Controller
{
    public function __construct(
        protected HorasExtrasCalculatorService $horasExtrasCalculator
    ) {
    }

    public function index($planillaId)
    {
        return response()->json(
            DB::table('DETALLES_HORASEXTRAS')
                ->join('EMPLEADO', 'DETALLES_HORASEXTRAS.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
                ->join('HORAS_EXTRAS', 'DETALLES_HORASEXTRAS.ID_HORASEXTRAS', '=', 'HORAS_EXTRAS.ID_HORASEXTRAS')
                ->select(
                    'DETALLES_HORASEXTRAS.*',
                    DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                    'HORAS_EXTRAS.TIPOHORAEXTRA',
                    'HORAS_EXTRAS.MODALIDAD',
                    'HORAS_EXTRAS.JORNADA',
                    'HORAS_EXTRAS.ES_DOMINICAL'
                )
                ->where('DETALLES_HORASEXTRAS.ID_PLANILLA', $planillaId)
                ->get()
        );
    }

    public function store(Request $request, $planillaId)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'ID_HORASEXTRAS' => 'required|integer',
            'CANTIDADHORAS' => 'required|numeric|min:0.01',
        ]);

        $empleado = Empleado::findOrFail($request->ID_EMPLEADO);
        $tipoHe = DB::table('HORAS_EXTRAS')->where('ID_HORASEXTRAS', $request->ID_HORASEXTRAS)->first();
        if (!$tipoHe) {
            return response()->json(['error' => 'Tipo de hora extra no encontrado.'], 404);
        }

        $monto = $this->horasExtrasCalculator->calcularMonto($empleado, (float) $request->CANTIDADHORAS, (float) $tipoHe->FACTOR);

        $maxId = DB::table('DETALLES_HORASEXTRAS')->max('ID_DETALLEHORAEXTRA') ?? 0;

        DB::table('DETALLES_HORASEXTRAS')->insert([
            'ID_DETALLEHORAEXTRA' => $maxId + 1,
            'ID_HORASEXTRAS' => $request->ID_HORASEXTRAS,
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'ID_PLANILLA' => $planillaId,
            'CANTIDADHORAS' => $request->CANTIDADHORAS,
            'MONTOAPAGAR' => $monto,
        ]);

        return response()->json(['ID_DETALLEHORAEXTRA' => $maxId + 1, 'MONTOAPAGAR' => $monto], 201);
    }

    public function destroy($planillaId, $id)
    {
        DB::table('DETALLES_HORASEXTRAS')
            ->where('ID_PLANILLA', $planillaId)
            ->where('ID_DETALLEHORAEXTRA', $id)
            ->delete();

        return response()->json(['message' => 'Registro eliminado.']);
    }

    public function syncFromAttendance($planillaId)
    {
        $planilla = Planilla::with('periodoLaboral')->findOrFail($planillaId);
        $inicio = $planilla->periodoLaboral->FECHAINICIO;
        $fin = $planilla->periodoLaboral->FECHAFIN;

        $tiposPorCodigo = DB::table('HORAS_EXTRAS')->whereNotNull('CODIGO')->get()->keyBy('CODIGO');
        if ($tiposPorCodigo->isEmpty()) {
            return response()->json(['error' => 'Configure tipos de horas extras en Catálogos RRHH.'], 422);
        }

        DB::table('DETALLES_HORASEXTRAS')->where('ID_PLANILLA', $planillaId)->delete();

        $asistencias = DB::table('ASISTENCIA_DIARIA')
            ->whereBetween('FECHA', [$inicio, $fin])
            ->where(function ($q) {
                $q->where('HORAS_EXTRAS_DIURNAS', '>', 0)
                    ->orWhere('HORAS_EXTRAS_NOCTURNAS', '>', 0);
            })
            ->get();

        $maxId = DB::table('DETALLES_HORASEXTRAS')->max('ID_DETALLEHORAEXTRA') ?? 0;
        $insertados = 0;

        $agrupado = [];
        foreach ($asistencias as $a) {
            if (!isset($agrupado[$a->ID_EMPLEADO])) {
                $agrupado[$a->ID_EMPLEADO] = [
                    'diurnas' => 0,
                    'nocturnas' => 0,
                    'diurnas_dom' => 0,
                    'nocturnas_dom' => 0,
                ];
            }

            if ($a->ES_DIA_DESCANSO ?? false) {
                $agrupado[$a->ID_EMPLEADO]['diurnas_dom'] += (float) $a->HORAS_EXTRAS_DIURNAS;
                $agrupado[$a->ID_EMPLEADO]['nocturnas_dom'] += (float) $a->HORAS_EXTRAS_NOCTURNAS;
            } else {
                $agrupado[$a->ID_EMPLEADO]['diurnas'] += (float) $a->HORAS_EXTRAS_DIURNAS;
                $agrupado[$a->ID_EMPLEADO]['nocturnas'] += (float) $a->HORAS_EXTRAS_NOCTURNAS;
            }
        }

        foreach ($agrupado as $empId => $horas) {
            $empleado = Empleado::find($empId);
            if (!$empleado) {
                continue;
            }

            $cupoDiurnas = (float) ($empleado->HORAS_EXTRAS_FIJAS_DIURAS ?? 0);
            $cupoNocturnas = (float) ($empleado->HORAS_EXTRAS_FIJAS_NOCTURNAS ?? 0);

            [$cupoDiurnas, $insertados, $maxId] = $this->insertarLineasHoras(
                $empleado, $empId, $planillaId, $horas['diurnas'], false, 'DIURNA', $cupoDiurnas, $tiposPorCodigo, $insertados, $maxId
            );
            [$cupoDiurnas, $insertados, $maxId] = $this->insertarLineasHoras(
                $empleado, $empId, $planillaId, $horas['diurnas_dom'], true, 'DIURNA', $cupoDiurnas, $tiposPorCodigo, $insertados, $maxId
            );
            [$cupoNocturnas, $insertados, $maxId] = $this->insertarLineasHoras(
                $empleado, $empId, $planillaId, $horas['nocturnas'], false, 'NOCTURNA', $cupoNocturnas, $tiposPorCodigo, $insertados, $maxId
            );
            [$cupoNocturnas, $insertados, $maxId] = $this->insertarLineasHoras(
                $empleado, $empId, $planillaId, $horas['nocturnas_dom'], true, 'NOCTURNA', $cupoNocturnas, $tiposPorCodigo, $insertados, $maxId
            );
        }

        return response()->json(['message' => "Se sincronizaron {$insertados} registros de horas extras (fijas/adicionales, diurnas/nocturnas)."]);
    }

    protected function insertarLineasHoras(
        Empleado $empleado,
        int $empId,
        int $planillaId,
        float $horas,
        bool $dominical,
        string $jornada,
        float $cupoFijas,
        $tiposPorCodigo,
        int $insertados,
        int $maxId
    ): array {
        if ($horas <= 0) {
            return [$cupoFijas, $insertados, $maxId];
        }

        $division = $this->horasExtrasCalculator->dividirFijaAdicional($horas, $cupoFijas);
        $cupoFijas = $division['restantes'];

        $partes = [
            ['modalidad' => 'FIJA', 'horas' => $division['fijas']],
            ['modalidad' => 'ADICIONAL', 'horas' => $division['adicionales']],
        ];

        foreach ($partes as $parte) {
            if ($parte['horas'] <= 0) {
                continue;
            }

            $codigo = sprintf(
                'HE_%s_%s%s',
                $parte['modalidad'],
                $jornada,
                $dominical ? '_DOM' : ''
            );

            $tipo = $tiposPorCodigo->get($codigo);
            if (!$tipo) {
                continue;
            }

            $maxId++;
            DB::table('DETALLES_HORASEXTRAS')->insert([
                'ID_DETALLEHORAEXTRA' => $maxId,
                'ID_HORASEXTRAS' => $tipo->ID_HORASEXTRAS,
                'ID_EMPLEADO' => $empId,
                'ID_PLANILLA' => $planillaId,
                'CANTIDADHORAS' => $parte['horas'],
                'MONTOAPAGAR' => $this->horasExtrasCalculator->calcularMonto($empleado, $parte['horas'], (float) $tipo->FACTOR),
            ]);
            $insertados++;
        }

        return [$cupoFijas, $insertados, $maxId];
    }
}
