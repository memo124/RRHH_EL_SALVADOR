<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\Empleado;
use App\Services\IsssMovimientoService;
use App\Services\LiquidacionCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiquidacionController extends Controller
{
    use PaginatesQueries;

    protected $calculator;
    protected $isssMovimiento;

    public function __construct(LiquidacionCalculatorService $calculator, IsssMovimientoService $isssMovimiento)
    {
        $this->calculator = $calculator;
        $this->isssMovimiento = $isssMovimiento;
    }

    public function index(Request $request)
    {
        $query = DB::table('LIQUIDACIONES')
            ->join('EMPLEADO', 'LIQUIDACIONES.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->select(
                'LIQUIDACIONES.*',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'EMPLEADO.CODIGOEMPLEADO'
            )
            ->orderBy('LIQUIDACIONES.ID_LIQUIDACION', 'desc');

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGOEMPLEADO']);
    }

    public function calcularPreview(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'FECHA_LIQUIDACION' => 'required|date',
            'INCLUIR_INDEMNIZACION' => 'boolean',
        ]);

        $empleado = Empleado::findOrFail($request->ID_EMPLEADO);
        $result = $this->calculator->calcular(
            $empleado,
            Carbon::parse($request->FECHA_LIQUIDACION),
            $request->boolean('INCLUIR_INDEMNIZACION')
        );

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'FECHA_LIQUIDACION' => 'required|date',
            'INCLUIR_INDEMNIZACION' => 'boolean',
        ]);

        $empleadoId = (int) $request->ID_EMPLEADO;

        $existente = DB::table('LIQUIDACIONES')
            ->where('ID_EMPLEADO', $empleadoId)
            ->first();

        if ($existente) {
            return response()->json([
                'message' => 'Este empleado ya tiene una liquidación registrada. No se permiten duplicados.',
            ], 422);
        }

        $empleado = Empleado::findOrFail($empleadoId);

        if (!$empleado->ESACTIVO) {
            return response()->json([
                'message' => 'El empleado ya está inactivo. No se puede liquidar nuevamente.',
            ], 422);
        }

        $data = $this->calculator->calcular(
            $empleado,
            Carbon::parse($request->FECHA_LIQUIDACION),
            $request->boolean('INCLUIR_INDEMNIZACION')
        );

        $maxId = DB::table('LIQUIDACIONES')->max('ID_LIQUIDACION') ?? 0;

        try {
            DB::transaction(function () use ($request, $empleadoId, $data, $maxId) {
                $duplicado = DB::table('LIQUIDACIONES')
                    ->where('ID_EMPLEADO', $empleadoId)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicado) {
                    throw new \RuntimeException('Liquidación duplicada detectada.');
                }

                DB::table('LIQUIDACIONES')->insert(array_merge([
                    'ID_LIQUIDACION' => $maxId + 1,
                    'ID_EMPLEADO' => $empleadoId,
                    'USUARIO_CREACION' => $request->user()?->USUARIO ?? 'SYSTEM',
                ], $data));

                DB::table('EMPLEADO')->where('ID_EMPLEADO', $empleadoId)->update(['ESACTIVO' => false]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->isssMovimiento->registrarBaja($empleadoId, $request->FECHA_LIQUIDACION);

        return response()->json(['ID_LIQUIDACION' => $maxId + 1, 'message' => 'Liquidación registrada.'], 201);
    }
}
