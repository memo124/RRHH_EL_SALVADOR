<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\IsssMovimientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpleadoController extends Controller
{
    public function __construct(
        protected IsssMovimientoService $isssMovimiento,
        protected AuditService $audit
    ) {
    }

    public function index(Request $request)
    {
        $query = $this->empleadosQuery();

        if ($search = trim($request->input('search', ''))) {
            $this->applyEmpleadoSearch($query, $search);
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        return response()->json($query->paginate($perPage));
    }

    /**
     * Opciones paginadas para AsyncSelect (búsqueda server-side).
     */
    public function select(Request $request)
    {
        $query = DB::table('EMPLEADO')
            ->where('ESACTIVO', true)
            ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'APELLIDO_2', 'DUI')
            ->orderBy('NOMBRES')
            ->orderBy('APELLIDO_1');

        if ($search = trim($request->input('q', $request->input('search', '')))) {
            $this->applyEmpleadoSearch($query, $search);
        }

        $perPage = min(50, max(10, (int) $request->input('per_page', 30)));
        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(function ($e) {
            return [
                'value' => $e->ID_EMPLEADO,
                'label' => $this->formatEmpleadoLabel($e),
            ];
        })->values();

        if ($request->filled('id')) {
            $selectedId = (int) $request->input('id');
            if (!$data->contains('value', $selectedId)) {
                $selected = DB::table('EMPLEADO')
                    ->where('ID_EMPLEADO', $selectedId)
                    ->select('ID_EMPLEADO', 'CODIGOEMPLEADO', 'NOMBRES', 'APELLIDO_1', 'APELLIDO_2', 'DUI')
                    ->first();
                if ($selected) {
                    $data->prepend([
                        'value' => $selected->ID_EMPLEADO,
                        'label' => $this->formatEmpleadoLabel($selected),
                    ]);
                }
            }
        }

        return response()->json([
            'data' => $data->values(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    private function empleadosQuery()
    {
        return DB::table('EMPLEADO')
            ->leftJoin('EMPRESA', 'EMPLEADO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('DEPARTAMENTO', 'EMPLEADO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->leftJoin('CARGO', 'EMPLEADO.ID_CARGO', '=', 'CARGO.ID_CARGO')
            ->leftJoin('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->select(
                'EMPLEADO.*',
                'EMPRESA.NOMBREEMPRESA as EMPRESA_NOMBRE',
                'DEPARTAMENTO.NOMBREDEPARTAMENTO as DEPARTAMENTO_NOMBRE',
                'CARGO.NOMBRECARGO as CARGO_NOMBRE',
                'TIPO_CONTRATACION.TIPOCONTRATACION as CONTRATACION_TIPO'
            )
            ->orderBy('EMPLEADO.ID_EMPLEADO', 'desc');
    }

    private function applyEmpleadoSearch($query, string $search): void
    {
        $like = '%' . $search . '%';
        $query->where(function ($q) use ($like) {
            $q->where('EMPLEADO.NOMBRES', 'like', $like)
                ->orWhere('EMPLEADO.APELLIDO_1', 'like', $like)
                ->orWhere('EMPLEADO.APELLIDO_2', 'like', $like)
                ->orWhere('EMPLEADO.CODIGOEMPLEADO', 'like', $like)
                ->orWhere('EMPLEADO.DUI', 'like', $like);
        });
    }

    private function formatEmpleadoLabel(object $e): string
    {
        $nombre = trim(($e->NOMBRES ?? '') . ' ' . ($e->APELLIDO_1 ?? '') . ' ' . ($e->APELLIDO_2 ?? ''));
        $codigo = $e->CODIGOEMPLEADO ?? '';

        return $codigo ? "{$codigo} — {$nombre}" : $nombre;
    }

    public function catalogs()
    {
        // Check and seed default Area, Departamento, and Cargo if empty
        $empresa = DB::table('EMPRESA')->first();
        $idEmpresa = $empresa ? $empresa->ID_EMPRESA : 1;

        if (DB::table('AREA')->count() === 0) {
            DB::table('AREA')->insert([
                'ID_AREA' => 1,
                'ID_EMPRESA' => $idEmpresa,
                'NOMBREAREA' => 'Administración',
                'ACTIVA' => true,
                'PRORRATEADA' => false
            ]);
        }

        if (DB::table('DEPARTAMENTO')->count() === 0) {
            DB::table('DEPARTAMENTO')->insert([
                'ID_DEPARTAMENTO' => 1,
                'ID_EMPRESA' => $idEmpresa,
                'ID_AREA' => 1,
                'NOMBREDEPARTAMENTO' => 'Administración y Finanzas',
                'DESCRIPCION' => 'Departamento de administración y finanzas',
                'MANO_OBRA_DIRECTA' => false
            ]);
        }

        if (DB::table('CARGO')->count() === 0) {
            DB::table('CARGO')->insert([
                'ID_CARGO' => 1,
                'ID_DEPARTAMENTO' => 1,
                'NOMBRECARGO' => 'Administrador',
                'CARGOESTADO' => true,
                'NIVEL_JERARQUICO' => 1
            ]);
        }

        if (DB::table('PERFIL_PAGO')->count() === 0) {
            DB::table('PERFIL_PAGO')->insert([
                'ID_PERFILPAGO' => 1,
                'PEFILPAGO' => 'Planilla General',
                'GRATIFICACIONES' => true,
                'EXTRA_GRATIFICACIONES' => true
            ]);
        }

        return response()->json([
            'empresas' => DB::table('EMPRESA')->where('EMPRESAACTIVA', true)->get(),
            'departamentos' => DB::table('DEPARTAMENTO')->get(),
            'cargos' => DB::table('CARGO')->where('CARGOESTADO', true)->get(),
            'tipos_contratacion' => DB::table('TIPO_CONTRATACION')->where('ESACTIVO', true)->get(),
            'afps' => DB::table('AFP')->where('ESACTIVO', true)->get(),
            'bancos' => DB::table('BANCO')->where('BANCOACTIVO', true)->get(),
            'departamentos_geograficos' => DB::table('DEPARTAMENTO_PAIS')->orderBy('NOMBREDEPARTAMENTO')->get(),
            'municipios' => DB::table('MUNICIPIO')->orderBy('NOMBREMUNICIPIO')->get(),
            'distritos' => DB::table('DISTRITO')->orderBy('NOMBREDISTRITO')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ID_DEPARTAMENTO' => 'required|integer',
            'ID_CARGO' => 'required|integer',
            'ID_TIPOCONTRATACION' => 'required|integer',
            'ID_DISTRITO' => 'required|integer',
            'CODIGOEMPLEADO' => 'required|string|max:50',
            'NOMBRES' => 'required|string|max:150',
            'APELLIDO_1' => 'required|string|max:100',
            'APELLIDO_2' => 'nullable|string|max:100',
            'DUI' => 'required|string|max:12|unique:EMPLEADO,DUI',
            'NIT' => 'nullable|string|max:20',
            'ISSS' => 'nullable|string|max:20',
            'NUP' => 'nullable|string|max:20',
            'GENERO' => 'required|string|max:1',
            'FECHANACIMIENTO' => 'required|date',
            'FECHAINGRESO' => 'required|date',
            'SALARIOMENSUAL' => 'required|numeric',
            'HORAS_EXTRAS_FIJAS_DIURAS' => 'nullable|numeric|min:0',
            'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 'nullable|numeric|min:0',
            'CORREOELECTRONICO' => 'nullable|email|max:100',
            'TELEFONOCELULAR' => 'nullable|string|max:15',
            'DIRECCION' => 'nullable|string|max:250',
            'NUMEROCUENTA' => 'nullable|string|max:50',
        ]);

        $maxId = DB::table('EMPLEADO')->max('ID_EMPLEADO') ?? 0;
        $id = $maxId + 1;

        $salarioMensual = $request->SALARIOMENSUAL;
        $salarioDiario = $salarioMensual / 30.0;

        DB::table('EMPLEADO')->insert([
            'ID_EMPLEADO' => $id,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'ID_DEPARTAMENTO' => $request->ID_DEPARTAMENTO,
            'ID_CARGO' => $request->ID_CARGO,
            'ID_TIPOCONTRATACION' => $request->ID_TIPOCONTRATACION,
            'ID_DISTRITO' => $request->ID_DISTRITO,
            'ID_AFP' => $request->ID_AFP ?? null,
            'ID_BANCO' => $request->ID_BANCO ?? null,
            'CODIGOEMPLEADO' => $request->CODIGOEMPLEADO,
            'NOMBRES' => $request->NOMBRES,
            'APELLIDO_1' => $request->APELLIDO_1,
            'APELLIDO_2' => $request->APELLIDO_2,
            'DUI' => $request->DUI,
            'NIT' => $request->NIT,
            'ISSS' => $request->ISSS,
            'NUP' => $request->NUP,
            'GENERO' => $request->GENERO,
            'FECHANACIMIENTO' => $request->FECHANACIMIENTO,
            'FECHAINGRESO' => $request->FECHAINGRESO,
            'SALARIOMENSUAL' => $salarioMensual,
            'SALARIODIARIO' => $salarioDiario,
            'HORAS_EXTRAS_FIJAS_DIURAS' => $request->HORAS_EXTRAS_FIJAS_DIURAS ?? 0,
            'HORAS_EXTRAS_FIJAS_NOCTURNAS' => $request->HORAS_EXTRAS_FIJAS_NOCTURNAS ?? 0,
            'CORREOELECTRONICO' => $request->CORREOELECTRONICO,
            'TELEFONOCELULAR' => $request->TELEFONOCELULAR,
            'DIRECCION' => $request->DIRECCION,
            'NUMEROCUENTA' => $request->NUMEROCUENTA,
            'ESACTIVO' => true,
            'JUBILADO' => false,
            'ID_PERFILPAGO' => 1
        ]);

        $this->isssMovimiento->registrarAlta($id);

        $this->audit->log(
            'EMPLEADO',
            $id,
            'create',
            null,
            $this->audit->sanitize(DB::table('EMPLEADO')->where('ID_EMPLEADO', $id)->first()),
            $request->user()?->ID_USUARIO,
            $request->ip()
        );

        return response()->json(['ID_EMPLEADO' => $id, 'message' => 'Empleado creado correctamente.'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ID_DEPARTAMENTO' => 'required|integer',
            'ID_CARGO' => 'required|integer',
            'ID_TIPOCONTRATACION' => 'required|integer',
            'ID_DISTRITO' => 'required|integer',
            'CODIGOEMPLEADO' => 'required|string|max:50',
            'NOMBRES' => 'required|string|max:150',
            'APELLIDO_1' => 'required|string|max:100',
            'APELLIDO_2' => 'nullable|string|max:100',
            'DUI' => 'required|string|max:12|unique:EMPLEADO,DUI,' . $id . ',ID_EMPLEADO',
            'NIT' => 'nullable|string|max:20',
            'ISSS' => 'nullable|string|max:20',
            'NUP' => 'nullable|string|max:20',
            'GENERO' => 'required|string|max:1',
            'FECHANACIMIENTO' => 'required|date',
            'FECHAINGRESO' => 'required|date',
            'SALARIOMENSUAL' => 'required|numeric',
            'HORAS_EXTRAS_FIJAS_DIURAS' => 'nullable|numeric|min:0',
            'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 'nullable|numeric|min:0',
            'CORREOELECTRONICO' => 'nullable|email|max:100',
            'TELEFONOCELULAR' => 'nullable|string|max:15',
            'DIRECCION' => 'nullable|string|max:250',
            'NUMEROCUENTA' => 'nullable|string|max:50',
            'ESACTIVO' => 'required|boolean'
        ]);

        $salarioMensual = $request->SALARIOMENSUAL;
        $salarioDiario = $salarioMensual / 30.0;

        $before = $this->audit->sanitize(DB::table('EMPLEADO')->where('ID_EMPLEADO', $id)->first());

        DB::table('EMPLEADO')
            ->where('ID_EMPLEADO', $id)
            ->update([
                'ID_EMPRESA' => $request->ID_EMPRESA,
                'ID_DEPARTAMENTO' => $request->ID_DEPARTAMENTO,
                'ID_CARGO' => $request->ID_CARGO,
                'ID_TIPOCONTRATACION' => $request->ID_TIPOCONTRATACION,
                'ID_DISTRITO' => $request->ID_DISTRITO,
                'ID_AFP' => $request->ID_AFP ?? null,
                'ID_BANCO' => $request->ID_BANCO ?? null,
                'CODIGOEMPLEADO' => $request->CODIGOEMPLEADO,
                'NOMBRES' => $request->NOMBRES,
                'APELLIDO_1' => $request->APELLIDO_1,
                'APELLIDO_2' => $request->APELLIDO_2,
                'DUI' => $request->DUI,
                'NIT' => $request->NIT,
                'ISSS' => $request->ISSS,
                'NUP' => $request->NUP,
                'GENERO' => $request->GENERO,
                'FECHANACIMIENTO' => $request->FECHANACIMIENTO,
                'FECHAINGRESO' => $request->FECHAINGRESO,
                'SALARIOMENSUAL' => $salarioMensual,
                'SALARIODIARIO' => $salarioDiario,
                'HORAS_EXTRAS_FIJAS_DIURAS' => $request->HORAS_EXTRAS_FIJAS_DIURAS ?? 0,
                'HORAS_EXTRAS_FIJAS_NOCTURNAS' => $request->HORAS_EXTRAS_FIJAS_NOCTURNAS ?? 0,
                'CORREOELECTRONICO' => $request->CORREOELECTRONICO,
                'TELEFONOCELULAR' => $request->TELEFONOCELULAR,
                'DIRECCION' => $request->DIRECCION,
                'NUMEROCUENTA' => $request->NUMEROCUENTA,
                'ESACTIVO' => $request->ESACTIVO
            ]);

        $this->audit->log(
            'EMPLEADO',
            $id,
            'update',
            $before,
            $this->audit->sanitize(DB::table('EMPLEADO')->where('ID_EMPLEADO', $id)->first()),
            $request->user()?->ID_USUARIO,
            $request->ip()
        );

        return response()->json(['message' => 'Empleado actualizado correctamente.']);
    }

    public function destroy(Request $request, $id)
    {
        $before = $this->audit->sanitize(DB::table('EMPLEADO')->where('ID_EMPLEADO', $id)->first());

        DB::table('EMPLEADO')
            ->where('ID_EMPLEADO', $id)
            ->update(['ESACTIVO' => false]);

        $this->isssMovimiento->registrarBaja((int) $id);

        $this->audit->log(
            'EMPLEADO',
            $id,
            'delete',
            $before,
            null,
            $request->user()?->ID_USUARIO,
            $request->ip()
        );

        return response()->json(['message' => 'Empleado inactivado correctamente.']);
    }
}
