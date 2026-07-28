<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\IncapacityManagementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncapacidadController extends Controller
{
    protected $incapacityService;

    public function __construct(IncapacityManagementService $incapacityService)
    {
        $this->incapacityService = $incapacityService;
    }

    public function catalogs()
    {
        return response()->json([
            'empleados' => DB::table('EMPLEADO')->where('ESACTIVO', true)
                ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'SALARIODIARIO')
                ->orderBy('NOMBRES')->get(),
            'tipos' => DB::table('TIPO_INCAPACIDAD')->where('ESACTIVO', true)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $query = DB::table('INCAPACIDAD')
            ->join('EMPLEADO', 'INCAPACIDAD.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_INCAPACIDAD', 'INCAPACIDAD.ID_TIPOINCAPACIDAD', '=', 'TIPO_INCAPACIDAD.ID_TIPOINCAPACIDAD')
            ->leftJoin('SUBSIDIO_ISSS', 'INCAPACIDAD.ID_INCAPACIDAD', '=', 'SUBSIDIO_ISSS.ID_INCAPACIDAD')
            ->select(
                'INCAPACIDAD.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'TIPO_INCAPACIDAD.NOMBRE_TIPO',
                'SUBSIDIO_ISSS.ID_SUBSIDIO',
                'SUBSIDIO_ISSS.MONTO_SUBSIDIO_CALCULADO_ISSS',
                'SUBSIDIO_ISSS.ESTADO_SUBSIDIO'
            )
            ->orderBy('INCAPACIDAD.FECHA_INICIO', 'desc');

        if ($request->boolean('activas')) {
            $query->where('INCAPACIDAD.ESTADO_INCAPACIDAD', '!=', 'CANCELADA')
                ->where('INCAPACIDAD.FECHA_FIN', '>=', now()->toDateString());
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'ID_TIPOINCAPACIDAD' => 'required|integer',
            'NUMERO_CERTIFICADO_ISSS' => 'required|string|max:50',
            'FECHA_INICIO' => 'required|date',
            'FECHA_FIN' => 'required|date|after_or_equal:FECHA_INICIO',
        ]);

        $empleado = Empleado::findOrFail($request->ID_EMPLEADO);
        $salarioDiario = (float) $empleado->SALARIODIARIO;
        if ($salarioDiario <= 0) {
            $salarioDiario = (float) $empleado->SALARIOMENSUAL / 30;
        }

        $incapacidad = $this->incapacityService->registerIncapacidad(
            $request->ID_EMPLEADO,
            $request->ID_TIPOINCAPACIDAD,
            $request->NUMERO_CERTIFICADO_ISSS,
            Carbon::parse($request->FECHA_INICIO),
            Carbon::parse($request->FECHA_FIN),
            $salarioDiario
        );

        // Marcar días en asistencia
        $fecha = Carbon::parse($request->FECHA_INICIO);
        $fechaFin = Carbon::parse($request->FECHA_FIN);
        while ($fecha->lte($fechaFin)) {
            DB::table('ASISTENCIA_DIARIA')->updateOrInsert(
                ['ID_EMPLEADO' => $request->ID_EMPLEADO, 'FECHA' => $fecha->toDateString()],
                ['ES_INCAPACIDAD' => true, 'ES_INASISTENCIA' => false, 'OBSERVACIONES' => 'Incapacidad ISSS']
            );
            $fecha->addDay();
        }

        return response()->json([
            'ID_INCAPACIDAD' => $incapacidad->ID_INCAPACIDAD,
            'message' => 'Incapacidad registrada. Si aplica subsidio ISSS, aparecerá en la pestaña Cobros al ISSS.',
        ], 201);
    }

    public function cancelar(Request $request, $id)
    {
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $updates = ['ESTADO_INCAPACIDAD' => 'CANCELADA'];
        if ($request->filled('motivo')) {
            $updates['OBSERVACIONES'] = $request->motivo;
        }

        DB::table('INCAPACIDAD')->where('ID_INCAPACIDAD', $id)->update($updates);

        return response()->json(['message' => 'Incapacidad cancelada.']);
    }

    public function subsidios(Request $request)
    {
        $query = DB::table('SUBSIDIO_ISSS')
            ->join('INCAPACIDAD', 'SUBSIDIO_ISSS.ID_INCAPACIDAD', '=', 'INCAPACIDAD.ID_INCAPACIDAD')
            ->join('EMPLEADO', 'INCAPACIDAD.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('TIPO_INCAPACIDAD', 'INCAPACIDAD.ID_TIPOINCAPACIDAD', '=', 'TIPO_INCAPACIDAD.ID_TIPOINCAPACIDAD')
            ->select(
                'SUBSIDIO_ISSS.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'),
                'INCAPACIDAD.NUMERO_CERTIFICADO_ISSS',
                'INCAPACIDAD.FECHA_INICIO',
                'INCAPACIDAD.FECHA_FIN',
                'INCAPACIDAD.DIAS_TOTALES',
                'INCAPACIDAD.DIAS_PAGADOS_PATRONO',
                'INCAPACIDAD.DIAS_SUBSIDIADOS_ISSS',
                'TIPO_INCAPACIDAD.NOMBRE_TIPO'
            )
            ->orderBy('SUBSIDIO_ISSS.ID_SUBSIDIO', 'desc');

        if ($request->filled('estado')) {
            $query->where('SUBSIDIO_ISSS.ESTADO_SUBSIDIO', $request->estado);
        }

        $items = $query->get();

        return response()->json([
            'items' => $items,
            'totales' => [
                'pendiente' => $items->where('ESTADO_SUBSIDIO', 'PENDIENTE')->sum('MONTO_SUBSIDIO_CALCULADO_ISSS'),
                'cobrado' => $items->where('ESTADO_SUBSIDIO', 'COBRADO')->sum('MONTO_SUBSIDIO_CALCULADO_ISSS'),
                'count_pendiente' => $items->where('ESTADO_SUBSIDIO', 'PENDIENTE')->count(),
            ],
        ]);
    }

    public function actualizarSubsidio(Request $request, $id)
    {
        $request->validate([
            'ESTADO_SUBSIDIO' => 'required|string|max:20',
            'FECHA_COBRO_ISSS' => 'nullable|date',
            'COMPROBANTE_PAGO_ISSS' => 'nullable|string|max:50',
        ]);

        DB::table('SUBSIDIO_ISSS')->where('ID_SUBSIDIO', $id)->update([
            'ESTADO_SUBSIDIO' => $request->ESTADO_SUBSIDIO,
            'FECHA_COBRO_ISSS' => $request->FECHA_COBRO_ISSS,
            'COMPROBANTE_PAGO_ISSS' => $request->COMPROBANTE_PAGO_ISSS,
        ]);

        return response()->json(['message' => 'Subsidio actualizado.']);
    }
}
