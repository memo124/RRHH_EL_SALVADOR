<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Services\ReclutamientoOnboardingService;
use App\Services\ReclutamientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReclutamientoController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected ReclutamientoService $reclutamiento) {}

    public function catalogs()
    {
        return response()->json(['etapas' => $this->reclutamiento->etapas()]);
    }

    public function indexVacantes(Request $request)
    {
        $query = DB::table('VACANTE')
            ->leftJoin('EMPRESA', 'VACANTE.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('DEPARTAMENTO', 'VACANTE.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->leftJoin('CARGO', 'VACANTE.ID_CARGO', '=', 'CARGO.ID_CARGO')
            ->where('VACANTE.ESACTIVO', true)
            ->select(
                'VACANTE.*',
                'EMPRESA.NOMBREEMPRESA',
                'DEPARTAMENTO.NOMBREDEPARTAMENTO',
                'CARGO.NOMBRECARGO',
                DB::raw('(SELECT COUNT(*) FROM CANDIDATO WHERE CANDIDATO.ID_VACANTE = VACANTE.ID_VACANTE AND CANDIDATO.ESACTIVO = true) as TOTAL_CANDIDATOS')
            )
            ->orderByDesc('VACANTE.FECHA_APERTURA');

        if ($request->filled('estado')) {
            $query->where('VACANTE.ESTADO', $request->estado);
        }

        return $this->paginateQuery($query, $request, ['VACANTE.TITULO', 'VACANTE.DESCRIPCION']);
    }

    public function showVacante($id)
    {
        $vac = DB::table('VACANTE')
            ->leftJoin('EMPRESA', 'VACANTE.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->where('VACANTE.ID_VACANTE', $id)
            ->where('VACANTE.ESACTIVO', true)
            ->select('VACANTE.*', 'EMPRESA.NOMBREEMPRESA')
            ->first();

        if (!$vac) {
            return response()->json(['error' => 'Vacante no encontrada.'], 404);
        }

        return response()->json([
            'vacante' => $vac,
            'candidatos' => $this->reclutamiento->getCandidatos((int) $id),
        ]);
    }

    public function storeVacante(Request $request)
    {
        $request->validate([
            'TITULO' => 'required|string|max:250',
            'DESCRIPCION' => 'nullable|string',
            'REQUISITOS' => 'nullable|string',
            'ID_EMPRESA' => 'nullable|integer',
            'ID_DEPARTAMENTO' => 'nullable|integer',
            'ID_CARGO' => 'nullable|integer',
            'PLAZAS' => 'nullable|integer|min:1',
        ]);

        $maxId = DB::table('VACANTE')->max('ID_VACANTE') ?? 0;
        $id = $maxId + 1;

        DB::table('VACANTE')->insert([
            'ID_VACANTE' => $id,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'ID_DEPARTAMENTO' => $request->ID_DEPARTAMENTO,
            'ID_CARGO' => $request->ID_CARGO,
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'REQUISITOS' => $request->REQUISITOS,
            'ESTADO' => 'abierta',
            'PLAZAS' => $request->PLAZAS ?? 1,
            'ID_USUARIO_CREACION' => $request->user()->ID_USUARIO,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_VACANTE' => $id, 'message' => 'Vacante creada correctamente.'], 201);
    }

    public function updateVacante(Request $request, $id)
    {
        if (!DB::table('VACANTE')->where('ID_VACANTE', $id)->where('ESACTIVO', true)->exists()) {
            return response()->json(['error' => 'Vacante no encontrada.'], 404);
        }

        $update = array_filter([
            'TITULO' => $request->TITULO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'REQUISITOS' => $request->REQUISITOS,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'ID_DEPARTAMENTO' => $request->ID_DEPARTAMENTO,
            'ID_CARGO' => $request->ID_CARGO,
            'PLAZAS' => $request->PLAZAS,
        ], fn ($v) => $v !== null);

        if ($update !== []) {
            DB::table('VACANTE')->where('ID_VACANTE', $id)->update($update);
        }

        return response()->json(['message' => 'Vacante actualizada correctamente.']);
    }

    public function destroyVacante($id)
    {
        DB::table('VACANTE')->where('ID_VACANTE', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Vacante inactivada correctamente.']);
    }

    public function cerrarVacante($id)
    {
        $this->reclutamiento->cerrarVacante((int) $id);
        return response()->json(['message' => 'Vacante cerrada.']);
    }

    public function storeCandidato(Request $request)
    {
        $request->validate([
            'ID_VACANTE' => 'required|integer',
            'NOMBRES' => 'required|string|max:150',
            'APELLIDOS' => 'nullable|string|max:150',
            'EMAIL' => 'nullable|email|max:150',
            'TELEFONO' => 'nullable|string|max:30',
            'ID_ADJUNTO_CV' => 'nullable|integer',
        ]);

        $id = $this->reclutamiento->registrarCandidato($request->all());
        return response()->json(['ID_CANDIDATO' => $id, 'message' => 'Candidato registrado.'], 201);
    }

    public function avanzarEtapa(Request $request, $idCandidato)
    {
        $request->validate(['ID_ETAPA' => 'required|integer']);
        $this->reclutamiento->avanzarEtapa((int) $idCandidato, (int) $request->ID_ETAPA);

        if ($request->ESTADO === 'contratado') {
            DB::table('CANDIDATO')->where('ID_CANDIDATO', $idCandidato)->update(['ESTADO' => 'contratado']);
        }

        return response()->json(['message' => 'Etapa actualizada.']);
    }

    public function attachCv(Request $request, $idCandidato)
    {
        $request->validate(['ID_ADJUNTO_CV' => 'required|integer']);

        if (!DB::table('CANDIDATO')->where('ID_CANDIDATO', $idCandidato)->where('ESACTIVO', true)->exists()) {
            return response()->json(['error' => 'Candidato no encontrado.'], 404);
        }

        $this->reclutamiento->attachCv((int) $idCandidato, (int) $request->ID_ADJUNTO_CV);

        return response()->json(['message' => 'CV vinculado al candidato.']);
    }

    public function storeEntrevista(Request $request)
    {
        $request->validate([
            'ID_CANDIDATO' => 'required|integer',
            'FECHA_HORA' => 'required|date',
            'TIPO' => 'nullable|string|max:30',
            'ID_EMPLEADO_ENTREVISTADOR' => 'nullable|integer',
            'OBSERVACIONES' => 'nullable|string',
        ]);

        $id = $this->reclutamiento->programarEntrevista($request->all());
        return response()->json(['ID_ENTREVISTA' => $id, 'message' => 'Entrevista programada. Evento en calendario.'], 201);
    }

    public function entrevistasCandidato($idCandidato)
    {
        return response()->json($this->reclutamiento->getEntrevistas((int) $idCandidato));
    }

    public function previewContratar($idCandidato, ReclutamientoOnboardingService $onboarding)
    {
        try {
            return response()->json($onboarding->preview((int) $idCandidato));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }
    }

    public function contratar(Request $request, $idCandidato, ReclutamientoOnboardingService $onboarding)
    {
        $request->validate([
            'DUI' => 'required|string|max:12',
            'GENERO' => 'required|string|max:1',
            'FECHANACIMIENTO' => 'required|date',
            'FECHAINGRESO' => 'nullable|date',
            'SALARIOMENSUAL' => 'required|numeric|min:0',
            'ID_TIPOCONTRATACION' => 'required|integer',
            'ID_DISTRITO' => 'required|integer',
            'ID_EMPRESA' => 'nullable|integer',
            'ID_DEPARTAMENTO' => 'nullable|integer',
            'ID_CARGO' => 'nullable|integer',
            'CODIGOEMPLEADO' => 'nullable|string|max:50',
            'CORREOELECTRONICO' => 'nullable|email|max:100',
            'TELEFONOCELULAR' => 'nullable|string|max:15',
            'DIRECCION' => 'nullable|string|max:250',
            'NIT' => 'nullable|string|max:20',
            'ISSS' => 'nullable|string|max:20',
            'NUP' => 'nullable|string|max:20',
            'ID_AFP' => 'nullable|integer',
            'ID_BANCO' => 'nullable|integer',
            'NUMEROCUENTA' => 'nullable|string|max:50',
        ]);

        try {
            $result = $onboarding->contratar((int) $idCandidato, $request->all());
            return response()->json($result, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }
}
