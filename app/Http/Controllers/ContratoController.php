<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\Contrato;
use App\Models\PlantillaContrato;
use App\Services\ContratoLoteService;
use App\Services\ContratoTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratoController extends Controller
{
    use PaginatesQueries;

    public function __construct(
        protected ContratoTemplateService $templates,
        protected ContratoLoteService $lote,
    ) {
    }

    public function index(Request $request)
    {
        $nomEmpleado = $this->nomEmpleadoSql();

        $query = DB::table('CONTRATO')
            ->join('EMPLEADO', 'CONTRATO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->join('EMPRESA', 'CONTRATO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('PLANTILLA_CONTRATO', 'CONTRATO.ID_PLANTILLA', '=', 'PLANTILLA_CONTRATO.ID_PLANTILLA')
            ->leftJoin('CARGO', 'EMPLEADO.ID_CARGO', '=', 'CARGO.ID_CARGO')
            ->select(
                'CONTRATO.*',
                DB::raw("{$nomEmpleado} as NOM_EMPLEADO"),
                'EMPRESA.NOMBREEMPRESA',
                'PLANTILLA_CONTRATO.NOMBRE as NOMBRE_PLANTILLA',
                'CARGO.NOMBRECARGO'
            )
            ->orderByDesc('CONTRATO.ID_CONTRATO');

        if ($request->filled('ID_EMPRESA')) {
            $query->where('CONTRATO.ID_EMPRESA', $request->ID_EMPRESA);
        }
        if ($request->filled('ID_EMPLEADO')) {
            $query->where('CONTRATO.ID_EMPLEADO', $request->ID_EMPLEADO);
        }

        return $this->paginateQuery($query, $request, [
            'CONTRATO.NUMERO_CONTRATO',
            'EMPRESA.NOMBREEMPRESA',
            'PLANTILLA_CONTRATO.NOMBRE',
            'EMPLEADO.NOMBRES',
            'EMPLEADO.APELLIDO_1',
            'EMPLEADO.APELLIDO_2',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPLEADO' => 'required|integer',
            'ID_EMPRESA' => 'required|integer',
            'ID_PLANTILLA' => 'nullable|integer',
            'NUMERO_CONTRATO' => 'nullable|string|max:50',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date|after_or_equal:FECHA_INICIO',
            'SIN_FECHA_DEFINIDA' => 'boolean',
            'SALARIO' => 'nullable|numeric|min:0',
            'OBSERVACIONES' => 'nullable|string',
            'CAMPOS_EXTRA' => 'nullable|array',
            'generar_contenido' => 'boolean',
        ]);

        if (!$request->boolean('SIN_FECHA_DEFINIDA') && !$request->FECHA_INICIO && !$request->FECHA_FIN) {
            // Permitido: contrato sin fechas definidas
        }

        $maxId = Contrato::max('ID_CONTRATO') ?? 0;

        $contrato = new Contrato();
        $contrato->ID_CONTRATO = $maxId + 1;
        $contrato->ID_EMPLEADO = $request->ID_EMPLEADO;
        $contrato->ID_EMPRESA = $request->ID_EMPRESA;
        $contrato->ID_PLANTILLA = $request->ID_PLANTILLA;
        $contrato->NUMERO_CONTRATO = $request->NUMERO_CONTRATO ?? $this->generarNumero($request->ID_EMPRESA);
        $contrato->FECHA_INICIO = $request->FECHA_INICIO;
        $contrato->FECHA_FIN = $request->boolean('SIN_FECHA_DEFINIDA') ? null : $request->FECHA_FIN;
        $contrato->SIN_FECHA_DEFINIDA = $request->boolean('SIN_FECHA_DEFINIDA');
        $contrato->SALARIO = $request->SALARIO;
        $contrato->OBSERVACIONES = $request->OBSERVACIONES;
        $contrato->CAMPOS_EXTRA = $request->CAMPOS_EXTRA;
        $contrato->ESTADO = 'VIGENTE';
        $contrato->ESACTIVO = true;
        $contrato->FECHA_CREACION = now();

        if ($request->boolean('generar_contenido', true) && $contrato->ID_PLANTILLA) {
            $plantilla = PlantillaContrato::findOrFail($contrato->ID_PLANTILLA);
            $contrato->CONTENIDO_GENERADO = $this->templates->render(
                $plantilla,
                $contrato,
                $request->CAMPOS_EXTRA ?? []
            );
        }

        $contrato->save();

        return response()->json($contrato, 201);
    }

    public function update(Request $request, $id)
    {
        $contrato = Contrato::findOrFail($id);

        $request->validate([
            'ID_PLANTILLA' => 'nullable|integer',
            'NUMERO_CONTRATO' => 'nullable|string|max:50',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date|after_or_equal:FECHA_INICIO',
            'SIN_FECHA_DEFINIDA' => 'boolean',
            'SALARIO' => 'nullable|numeric|min:0',
            'OBSERVACIONES' => 'nullable|string',
            'ESTADO' => 'nullable|string|in:VIGENTE,VENCIDO,ANULADO',
            'CAMPOS_EXTRA' => 'nullable|array',
            'ESACTIVO' => 'boolean',
            'regenerar_contenido' => 'boolean',
        ]);

        if ($request->has('ID_PLANTILLA')) {
            $contrato->ID_PLANTILLA = $request->ID_PLANTILLA;
        }
        if ($request->has('NUMERO_CONTRATO')) {
            $contrato->NUMERO_CONTRATO = $request->NUMERO_CONTRATO;
        }
        if ($request->has('FECHA_INICIO')) {
            $contrato->FECHA_INICIO = $request->FECHA_INICIO;
        }
        if ($request->has('SIN_FECHA_DEFINIDA')) {
            $contrato->SIN_FECHA_DEFINIDA = $request->boolean('SIN_FECHA_DEFINIDA');
            if ($contrato->SIN_FECHA_DEFINIDA) {
                $contrato->FECHA_FIN = null;
            }
        }
        if ($request->has('FECHA_FIN') && !$contrato->SIN_FECHA_DEFINIDA) {
            $contrato->FECHA_FIN = $request->FECHA_FIN;
        }
        if ($request->has('SALARIO')) {
            $contrato->SALARIO = $request->SALARIO;
        }
        if ($request->has('OBSERVACIONES')) {
            $contrato->OBSERVACIONES = $request->OBSERVACIONES;
        }
        if ($request->has('ESTADO')) {
            $contrato->ESTADO = $request->ESTADO;
        }
        if ($request->has('ESACTIVO')) {
            $contrato->ESACTIVO = $request->boolean('ESACTIVO');
        }
        if ($request->has('CAMPOS_EXTRA')) {
            $contrato->CAMPOS_EXTRA = $request->CAMPOS_EXTRA;
        }

        if ($request->boolean('regenerar_contenido') && $contrato->ID_PLANTILLA) {
            $plantilla = PlantillaContrato::findOrFail($contrato->ID_PLANTILLA);
            $contrato->CONTENIDO_GENERADO = $this->templates->render(
                $plantilla,
                $contrato,
                $contrato->CAMPOS_EXTRA ?? []
            );
        }

        $contrato->save();

        return response()->json($contrato);
    }

    public function destroy($id)
    {
        $contrato = Contrato::findOrFail($id);
        $contrato->ESACTIVO = false;
        $contrato->ESTADO = 'ANULADO';
        $contrato->save();

        return response()->json(['message' => 'Contrato anulado correctamente.']);
    }

    public function regenerar($id)
    {
        $contrato = Contrato::findOrFail($id);
        if (!$contrato->ID_PLANTILLA) {
            return response()->json(['error' => 'El contrato no tiene plantilla asociada.'], 422);
        }

        $plantilla = PlantillaContrato::findOrFail($contrato->ID_PLANTILLA);
        $contrato->CONTENIDO_GENERADO = $this->templates->render(
            $plantilla,
            $contrato,
            $contrato->CAMPOS_EXTRA ?? []
        );
        $contrato->save();

        return response()->json($contrato);
    }

    public function numeroALetras(Request $request)
    {
        $request->validate(['monto' => 'required|numeric|min:0']);

        $service = app(\App\Services\NumeroALetrasService::class);

        return response()->json([
            'letras' => $service->convertir($request->monto),
        ]);
    }

    public function previewLote(Request $request)
    {
        $params = $this->validarParamsLote($request);

        return response()->json($this->lote->preview($params));
    }

    public function generarLote(Request $request)
    {
        $params = $this->validarParamsLote($request, requirePlantilla: true);

        $resultado = $this->lote->generar($params);

        return response()->json($resultado, $resultado['generados'] > 0 ? 201 : 422);
    }

    private function validarParamsLote(Request $request, bool $requirePlantilla = false): array
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'ID_TIPOCONTRATACION' => 'required|integer',
            'ID_PLANTILLA' => ($requirePlantilla ? 'required' : 'nullable') . '|integer',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN' => 'nullable|date|after_or_equal:FECHA_INICIO',
            'SIN_FECHA_DEFINIDA' => 'boolean',
            'SALARIO' => 'nullable|numeric|min:0',
            'OBSERVACIONES' => 'nullable|string',
            'PREFIJO_NUMERO' => 'nullable|string|max:30',
            'RENOVAR_VENCIDOS' => 'boolean',
            'SOLO_SIN_VIGENTE' => 'boolean',
            'MARCAR_ANTERIORES_VENCIDOS' => 'boolean',
            'LIMITE_DETALLE' => 'nullable|integer|min:1|max:500',
        ]);

        return [
            'ID_EMPRESA' => (int) $request->ID_EMPRESA,
            'ID_TIPOCONTRATACION' => (int) $request->ID_TIPOCONTRATACION,
            'ID_PLANTILLA' => $request->ID_PLANTILLA ? (int) $request->ID_PLANTILLA : null,
            'FECHA_INICIO' => $request->FECHA_INICIO,
            'FECHA_FIN' => $request->boolean('SIN_FECHA_DEFINIDA') ? null : $request->FECHA_FIN,
            'SIN_FECHA_DEFINIDA' => $request->boolean('SIN_FECHA_DEFINIDA'),
            'SALARIO' => $request->SALARIO,
            'OBSERVACIONES' => $request->OBSERVACIONES,
            'PREFIJO_NUMERO' => $request->PREFIJO_NUMERO,
            'RENOVAR_VENCIDOS' => $request->boolean('RENOVAR_VENCIDOS', true),
            'SOLO_SIN_VIGENTE' => $request->boolean('SOLO_SIN_VIGENTE', false),
            'MARCAR_ANTERIORES_VENCIDOS' => $request->boolean('MARCAR_ANTERIORES_VENCIDOS', true),
            'LIMITE_DETALLE' => $request->input('LIMITE_DETALLE', 100),
        ];
    }

    private function generarNumero(int $empresaId): string
    {
        $count = Contrato::where('ID_EMPRESA', $empresaId)->count() + 1;

        return sprintf('CONT-%d-%04d', $empresaId, $count);
    }

    /**
     * Nombre completo del empleado compatible con PostgreSQL (identificadores entre comillas).
     */
    private function nomEmpleadoSql(): string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return 'TRIM("EMPLEADO"."NOMBRES" || \' \' || "EMPLEADO"."APELLIDO_1" || COALESCE(\' \' || NULLIF(TRIM("EMPLEADO"."APELLIDO_2"), \'\'), \'\'))';
        }

        return 'CONCAT(EMPLEADO.NOMBRES, \' \', EMPLEADO.APELLIDO_1, COALESCE(CONCAT(\' \', EMPLEADO.APELLIDO_2), \'\'))';
    }
}
