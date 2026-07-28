<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        $query = DB::table('HORARIOS')
            ->join('EMPRESA', 'HORARIOS.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('HORARIOS.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('HORARIOS.ID_HORARIO');

        if ($request->filled('ID_EMPRESA')) {
            $query->where('HORARIOS.ID_EMPRESA', $request->ID_EMPRESA);
        }

        $this->applySearch($query, $request, ['NOMBREHORARIO', 'NOMBREEMPRESA']);
        $paginated = $query->paginate($this->perPage($request));

        foreach ($paginated->items() as $h) {
            $h->detalle = DB::table('HORARIO_DETALLE')
                ->where('ID_HORARIO', $h->ID_HORARIO)
                ->orderBy('DIA_SEMANA')
                ->get();
        }

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'NOMBRE_HORARIO' => 'required|string|max:100',
            'DESCRIPCION' => 'nullable|string|max:250',
            'TOLERANCIA_ENTRADA_MINUTOS' => 'integer|min:0',
            'TOLERANCIA_SALIDA_MINUTOS' => 'integer|min:0',
            'detalle' => 'required|array|min:1',
            'detalle.*.DIA_SEMANA' => 'required|integer|min:1|max:7',
            'detalle.*.HORA_ENTRADA' => 'required',
            'detalle.*.HORA_SALIDA' => 'required',
        ]);

        $maxId = DB::table('HORARIOS')->max('ID_HORARIO') ?? 0;
        $id = $maxId + 1;

        DB::table('HORARIOS')->insert([
            'ID_HORARIO' => $id,
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'NOMBRE_HORARIO' => $request->NOMBRE_HORARIO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'TOLERANCIA_ENTRADA_MINUTOS' => $request->TOLERANCIA_ENTRADA_MINUTOS ?? 10,
            'TOLERANCIA_SALIDA_MINUTOS' => $request->TOLERANCIA_SALIDA_MINUTOS ?? 10,
            'ES_ROTATIVO' => $request->ES_ROTATIVO ?? false,
            'ESACTIVO' => true,
        ]);

        $this->saveDetalle($id, $request->detalle);

        return response()->json(['ID_HORARIO' => $id], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'NOMBRE_HORARIO' => 'required|string|max:100',
            'detalle' => 'required|array|min:1',
        ]);

        DB::table('HORARIOS')->where('ID_HORARIO', $id)->update([
            'NOMBRE_HORARIO' => $request->NOMBRE_HORARIO,
            'DESCRIPCION' => $request->DESCRIPCION,
            'TOLERANCIA_ENTRADA_MINUTOS' => $request->TOLERANCIA_ENTRADA_MINUTOS ?? 10,
            'TOLERANCIA_SALIDA_MINUTOS' => $request->TOLERANCIA_SALIDA_MINUTOS ?? 10,
            'ESACTIVO' => $request->ESACTIVO ?? true,
        ]);

        DB::table('HORARIO_DETALLE')->where('ID_HORARIO', $id)->delete();
        $this->saveDetalle($id, $request->detalle);

        return response()->json(['message' => 'Horario actualizado.']);
    }

    public function destroy($id)
    {
        DB::table('HORARIOS')->where('ID_HORARIO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Horario inactivado.']);
    }

    private function saveDetalle(int $horarioId, array $detalle): void
    {
        $maxDetId = DB::table('HORARIO_DETALLE')->max('ID_HORARIODETALLE') ?? 0;

        foreach ($detalle as $d) {
            $maxDetId++;
            DB::table('HORARIO_DETALLE')->insert([
                'ID_HORARIODETALLE' => $maxDetId,
                'ID_HORARIO' => $horarioId,
                'DIA_SEMANA' => $d['DIA_SEMANA'],
                'HORA_ENTRADA' => $d['HORA_ENTRADA'],
                'HORA_SALIDA' => $d['HORA_SALIDA'],
                'HORA_INICIO_ALMUERZO' => $d['HORA_INICIO_ALMUERZO'] ?? null,
                'HORA_FIN_ALMUERZO' => $d['HORA_FIN_ALMUERZO'] ?? null,
                'TIEMPO_ALMUERZO_MINUTOS' => $d['TIEMPO_ALMUERZO_MINUTOS'] ?? 60,
                'ES_DIA_DESCANSO' => $d['ES_DIA_DESCANSO'] ?? false,
            ]);
        }
    }
}
