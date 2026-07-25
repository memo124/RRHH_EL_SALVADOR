<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceProcessingService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function catalogs()
    {
        return response()->json([
            'empleados' => DB::table('EMPLEADO')
                ->where('ESACTIVO', true)
                ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'ID_HORARIO')
                ->orderBy('NOMBRES')
                ->get(),
            'horarios' => DB::table('HORARIOS')->where('ESACTIVO', true)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $query = DB::table('ASISTENCIA_DIARIA')
            ->join('EMPLEADO', 'ASISTENCIA_DIARIA.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->select(
                'ASISTENCIA_DIARIA.*',
                'EMPLEADO.CODIGOEMPLEADO',
                DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO')
            )
            ->whereBetween('ASISTENCIA_DIARIA.FECHA', [$request->fecha_inicio, $request->fecha_fin])
            ->orderBy('ASISTENCIA_DIARIA.FECHA', 'desc');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('ASISTENCIA_DIARIA.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        return response()->json($query->get());
    }

    public function storeMarcacion(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'FECHA_HORA_MARCACION' => 'required|date',
            'TIPO_MARCACION' => 'required|in:ENTRADA,SALIDA',
            'ORIGEN' => 'nullable|string|max:20',
        ]);

        DB::table('MARCACION_RAW')->insert([
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'CODIGO_RELOJ' => $request->CODIGO_RELOJ,
            'FECHA_HORA_MARCACION' => $request->FECHA_HORA_MARCACION,
            'TIPO_MARCACION' => $request->TIPO_MARCACION,
            'ORIGEN' => $request->ORIGEN ?? 'MANUAL',
            'PROCESADO' => false,
        ]);

        return response()->json(['message' => 'Marcación registrada.'], 201);
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'ID_EMPLEADO' => 'nullable|integer',
            'ID_EMPRESA' => 'nullable|integer',
        ]);

        $start = Carbon::parse($request->fecha_inicio);
        $end = Carbon::parse($request->fecha_fin);

        $query = Empleado::where('ESACTIVO', true);
        if ($request->filled('ID_EMPLEADO')) {
            $query->where('ID_EMPLEADO', $request->ID_EMPLEADO);
        }
        if ($request->filled('ID_EMPRESA')) {
            $query->where('ID_EMPRESA', $request->ID_EMPRESA);
        }

        $procesados = 0;
        foreach ($query->get() as $empleado) {
            $this->attendanceService->processAttendance($empleado, $start, $end);
            $procesados++;
        }

        return response()->json(['message' => "Asistencia procesada para {$procesados} empleado(s)."]);
    }

    public function marcacionesPendientes(Request $request)
    {
        $query = DB::table('MARCACION_RAW')
            ->join('EMPLEADO', 'MARCACION_RAW.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->select('MARCACION_RAW.*', DB::raw('"EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" AS NOMBRE_EMPLEADO'))
            ->where('PROCESADO', false)
            ->orderBy('FECHA_HORA_MARCACION', 'desc')
            ->limit(200);

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('MARCACION_RAW.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        return response()->json($query->get());
    }
}
