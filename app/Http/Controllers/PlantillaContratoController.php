<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use App\Models\PlantillaContrato;
use App\Services\ContratoTemplateService;
use Illuminate\Http\Request;

class PlantillaContratoController extends Controller
{
    use PaginatesQueries;

    public function __construct(protected ContratoTemplateService $templates)
    {
    }

    public function campos()
    {
        return response()->json($this->templates->camposDisponibles());
    }

    public function index(Request $request)
    {
        $query = PlantillaContrato::query()
            ->leftJoin('EMPRESA', 'PLANTILLA_CONTRATO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('PLANTILLA_CONTRATO.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('PLANTILLA_CONTRATO.ID_PLANTILLA');

        if ($request->filled('ID_EMPRESA')) {
            $query->where(function ($q) use ($request) {
                $q->where('PLANTILLA_CONTRATO.ID_EMPRESA', $request->ID_EMPRESA)
                    ->orWhereNull('PLANTILLA_CONTRATO.ID_EMPRESA');
            });
        }

        return $this->paginateQuery($query, $request, ['NOMBRE', 'DESCRIPCION', 'NOMBREEMPRESA']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'NOMBRE' => 'required|string|max:150',
            'DESCRIPCION' => 'nullable|string|max:500',
            'FORMATO' => 'nullable|string|in:HTML,TEXTO',
            'CONTENIDO' => 'required|string',
            'CLAUSULAS' => 'nullable|string',
            'ID_EMPRESA' => 'nullable|integer',
            'ESACTIVO' => 'boolean',
        ]);

        $maxId = PlantillaContrato::max('ID_PLANTILLA') ?? 0;

        $plantilla = new PlantillaContrato();
        $plantilla->ID_PLANTILLA = $maxId + 1;
        $plantilla->NOMBRE = $request->NOMBRE;
        $plantilla->DESCRIPCION = $request->DESCRIPCION;
        $plantilla->FORMATO = strtoupper($request->FORMATO ?? 'HTML');
        $plantilla->CONTENIDO = $request->CONTENIDO;
        $plantilla->CLAUSULAS = $request->CLAUSULAS;
        $plantilla->ID_EMPRESA = $request->ID_EMPRESA;
        $plantilla->ESACTIVO = $request->ESACTIVO ?? true;
        $plantilla->FECHA_CREACION = now();
        $plantilla->save();

        return response()->json($plantilla, 201);
    }

    public function update(Request $request, $id)
    {
        $plantilla = PlantillaContrato::findOrFail($id);

        $request->validate([
            'NOMBRE' => 'required|string|max:150',
            'DESCRIPCION' => 'nullable|string|max:500',
            'FORMATO' => 'nullable|string|in:HTML,TEXTO',
            'CONTENIDO' => 'required|string',
            'CLAUSULAS' => 'nullable|string',
            'ID_EMPRESA' => 'nullable|integer',
            'ESACTIVO' => 'boolean',
        ]);

        $plantilla->NOMBRE = $request->NOMBRE;
        $plantilla->DESCRIPCION = $request->DESCRIPCION;
        $plantilla->FORMATO = strtoupper($request->FORMATO ?? 'HTML');
        $plantilla->CONTENIDO = $request->CONTENIDO;
        $plantilla->CLAUSULAS = $request->CLAUSULAS;
        $plantilla->ID_EMPRESA = $request->ID_EMPRESA;
        $plantilla->ESACTIVO = $request->ESACTIVO ?? true;
        $plantilla->save();

        return response()->json($plantilla);
    }

    public function destroy($id)
    {
        $plantilla = PlantillaContrato::findOrFail($id);
        $plantilla->ESACTIVO = false;
        $plantilla->save();

        return response()->json(['message' => 'Plantilla inactivada correctamente.']);
    }

    public function preview($id)
    {
        $plantilla = PlantillaContrato::findOrFail($id);

        return response()->json([
            'contenido' => $this->templates->preview($plantilla),
        ]);
    }
}
