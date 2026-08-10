<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\Empleado;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsistenciaController extends Controller
{
    use PaginatesQueries;

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

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGOEMPLEADO']);
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

    /**
     * Importa marcaciones desde un archivo CSV exportado del reloj biométrico.
     * Columnas admitidas: empleado (código o DUI), fecha, hora, tipo (E/S o entrada/salida)
     * — o bien empleado, fecha_hora, tipo en 3 columnas.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:5120',
        ]);

        $handle = fopen($request->file('archivo')->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['error' => 'No se pudo leer el archivo.'], 422);
        }

        $empleadosPorCodigo = DB::table('EMPLEADO')->whereNotNull('CODIGOEMPLEADO')->pluck('ID_EMPLEADO', 'CODIGOEMPLEADO');
        $empleadosPorDui = DB::table('EMPLEADO')->whereNotNull('DUI')->pluck('ID_EMPLEADO', 'DUI');

        $filasValidas = [];
        $errores = [];
        $numeroFila = 0;

        while (($fila = fgetcsv($handle)) !== false) {
            $numeroFila++;
            $fila = array_values(array_filter(array_map(
                fn ($v) => is_string($v) ? trim($v) : $v,
                $fila
            ), fn ($v) => $v !== null && $v !== ''));

            if ($fila === []) {
                continue;
            }
            if ($numeroFila === 1 && $this->pareceEncabezadoMarcacion($fila)) {
                continue;
            }

            $parsed = $this->parsearFilaMarcacion($fila, $empleadosPorCodigo, $empleadosPorDui);
            if ($parsed['error']) {
                $errores[] = "Fila {$numeroFila}: {$parsed['error']}";
                continue;
            }

            $filasValidas[] = $parsed;
        }
        fclose($handle);

        if ($filasValidas === []) {
            return response()->json([
                'message' => 'No se importó ninguna marcación válida.',
                'insertadas' => 0,
                'duplicadas' => 0,
                'errores' => $errores,
            ], 422);
        }

        $fechas = collect($filasValidas)->pluck('FECHA_HORA_MARCACION');
        $clavesExistentes = DB::table('MARCACION_RAW')
            ->whereBetween('FECHA_HORA_MARCACION', [$fechas->min(), $fechas->max()])
            ->get(['ID_EMPLEADO', 'FECHA_HORA_MARCACION', 'TIPO_MARCACION'])
            ->map(fn ($m) => $this->claveMarcacion($m->ID_EMPLEADO, $m->FECHA_HORA_MARCACION, $m->TIPO_MARCACION))
            ->flip();

        $vistas = [];
        $registrosNuevos = [];
        $duplicadas = 0;

        foreach ($filasValidas as $fila) {
            $clave = $this->claveMarcacion($fila['ID_EMPLEADO'], $fila['FECHA_HORA_MARCACION'], $fila['TIPO_MARCACION']);
            if (isset($clavesExistentes[$clave]) || isset($vistas[$clave])) {
                $duplicadas++;
                continue;
            }
            $vistas[$clave] = true;
            $registrosNuevos[] = [
                'ID_EMPLEADO' => $fila['ID_EMPLEADO'],
                'CODIGO_RELOJ' => null,
                'FECHA_HORA_MARCACION' => $fila['FECHA_HORA_MARCACION'],
                'TIPO_MARCACION' => $fila['TIPO_MARCACION'],
                'ORIGEN' => 'BIOMETRICO',
                'PROCESADO' => false,
            ];
        }

        foreach (array_chunk($registrosNuevos, 500) as $lote) {
            DB::table('MARCACION_RAW')->insert($lote);
        }

        return response()->json([
            'message' => count($registrosNuevos) . ' marcación(es) importada(s), ' . $duplicadas . ' duplicada(s) omitida(s).',
            'insertadas' => count($registrosNuevos),
            'duplicadas' => $duplicadas,
            'errores' => $errores,
        ]);
    }

    private function pareceEncabezadoMarcacion(array $fila): bool
    {
        $palabrasClave = ['empleado', 'codigo', 'código', 'dui', 'fecha', 'hora', 'tipo', 'fecha_hora'];
        foreach ($fila as $valor) {
            if (in_array(Str::lower((string) $valor), $palabrasClave, true)) {
                return true;
            }
        }
        return false;
    }

    /** @return array{ID_EMPLEADO?:int, FECHA_HORA_MARCACION?:string, TIPO_MARCACION?:string, error:?string} */
    private function parsearFilaMarcacion(array $fila, $empleadosPorCodigo, $empleadosPorDui): array
    {
        if (count($fila) < 3) {
            return ['error' => 'formato inválido, se esperan al menos 3 columnas.'];
        }

        if (count($fila) >= 4) {
            [$identificador, $fecha, $hora, $tipoRaw] = $fila;
            $fechaHoraTexto = "{$fecha} {$hora}";
        } else {
            [$identificador, $fechaHoraTexto, $tipoRaw] = $fila;
        }

        $idEmpleado = $empleadosPorCodigo[$identificador] ?? $empleadosPorDui[$identificador] ?? null;
        if (!$idEmpleado) {
            return ['error' => "empleado no encontrado ({$identificador})."];
        }

        try {
            $fechaHora = Carbon::parse($fechaHoraTexto);
        } catch (\Throwable $e) {
            return ['error' => "fecha/hora inválida ({$fechaHoraTexto})."];
        }

        $tipo = $this->normalizarTipoMarcacion((string) $tipoRaw);
        if (!$tipo) {
            return ['error' => "tipo de marcación inválido ({$tipoRaw})."];
        }

        return [
            'ID_EMPLEADO' => (int) $idEmpleado,
            'FECHA_HORA_MARCACION' => $fechaHora->format('Y-m-d H:i:s'),
            'TIPO_MARCACION' => $tipo,
            'error' => null,
        ];
    }

    private function normalizarTipoMarcacion(string $tipo): ?string
    {
        $t = Str::upper(trim($tipo));
        if (in_array($t, ['E', 'ENTRADA', 'IN', 'CHECKIN', 'CHECK-IN'], true)) {
            return 'ENTRADA';
        }
        if (in_array($t, ['S', 'SALIDA', 'OUT', 'CHECKOUT', 'CHECK-OUT'], true)) {
            return 'SALIDA';
        }
        return null;
    }

    private function claveMarcacion(int|string $idEmpleado, string $fechaHora, string $tipo): string
    {
        return $idEmpleado . '|' . Carbon::parse($fechaHora)->format('Y-m-d H:i:s') . '|' . $tipo;
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
            ->orderBy('FECHA_HORA_MARCACION', 'desc');

        if ($request->filled('ID_EMPLEADO')) {
            $query->where('MARCACION_RAW.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        return $this->paginateQuery($query, $request, ['NOMBRE_EMPLEADO', 'CODIGO_RELOJ']);
    }
}
