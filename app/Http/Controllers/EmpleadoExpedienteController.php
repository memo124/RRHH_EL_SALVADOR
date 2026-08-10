<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpleadoExpedienteController extends Controller
{
    use PaginatesQueries;

    private function assertEmpleado(int $id): void
    {
        if (!DB::table('EMPLEADO')->where('ID_EMPLEADO', $id)->exists()) {
            abort(404, 'Empleado no encontrado.');
        }
    }

    // —— Educación ——

    public function indexEducacion(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $query = DB::table('EMPLEADO_EDUCACION as EE')
            ->leftJoin('EDUCACION_ACADEMICA as EA', 'EE.ID_EDUCACIONACADEMICA', '=', 'EA.ID_EDUCACIONACADEMICA')
            ->where('EE.ID_EMPLEADO', $id)
            ->where('EE.ESACTIVO', true)
            ->select('EE.*', 'EA.DESCRIPCION as EDUCACION_NOMBRE')
            ->orderByDesc('EE.ID_EMPLEADO_EDUCACION');

        return $this->paginateQuery($query, $request, ['EE.TITULO_OBTENIDO', 'EE.INSTITUCION']);
    }

    public function storeEducacion(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'ID_EDUCACIONACADEMICA' => 'nullable|integer',
            'TITULO_OBTENIDO' => 'required|string|max:200',
            'INSTITUCION' => 'nullable|string|max:200',
            'FECHA_GRADUACION' => 'nullable|date',
        ]);

        $newId = (DB::table('EMPLEADO_EDUCACION')->max('ID_EMPLEADO_EDUCACION') ?? 0) + 1;
        DB::table('EMPLEADO_EDUCACION')->insert([
            'ID_EMPLEADO_EDUCACION' => $newId,
            'ID_EMPLEADO' => (int) $id,
            'ID_EDUCACIONACADEMICA' => $request->ID_EDUCACIONACADEMICA,
            'TITULO_OBTENIDO' => $request->TITULO_OBTENIDO,
            'INSTITUCION' => $request->INSTITUCION,
            'FECHA_GRADUACION' => $request->FECHA_GRADUACION,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_EMPLEADO_EDUCACION' => $newId, 'message' => 'Educación registrada.'], 201);
    }

    public function updateEducacion(Request $request, $id, $eduId)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'ID_EDUCACIONACADEMICA' => 'nullable|integer',
            'TITULO_OBTENIDO' => 'required|string|max:200',
            'INSTITUCION' => 'nullable|string|max:200',
            'FECHA_GRADUACION' => 'nullable|date',
        ]);

        $updated = DB::table('EMPLEADO_EDUCACION')
            ->where('ID_EMPLEADO_EDUCACION', $eduId)
            ->where('ID_EMPLEADO', $id)
            ->where('ESACTIVO', true)
            ->update([
                'ID_EDUCACIONACADEMICA' => $request->ID_EDUCACIONACADEMICA,
                'TITULO_OBTENIDO' => $request->TITULO_OBTENIDO,
                'INSTITUCION' => $request->INSTITUCION,
                'FECHA_GRADUACION' => $request->FECHA_GRADUACION,
            ]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Educación actualizada.']);
    }

    public function destroyEducacion($id, $eduId)
    {
        $updated = DB::table('EMPLEADO_EDUCACION')
            ->where('ID_EMPLEADO_EDUCACION', $eduId)
            ->where('ID_EMPLEADO', $id)
            ->update(['ESACTIVO' => false]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Educación inactivada.']);
    }

    // —— Certificaciones ——

    public function indexCertificaciones(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $query = DB::table('EMPLEADO_CERTIFICACION')
            ->where('ID_EMPLEADO', $id)
            ->where('ESACTIVO', true)
            ->orderByDesc('ID_CERTIFICACION');

        return $this->paginateQuery($query, $request, ['NOMBRE', 'INSTITUCION']);
    }

    public function storeCertificacion(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'NOMBRE' => 'required|string|max:200',
            'INSTITUCION' => 'nullable|string|max:200',
            'FECHA_EMISION' => 'nullable|date',
            'FECHA_VENCIMIENTO' => 'nullable|date',
        ]);

        $newId = (DB::table('EMPLEADO_CERTIFICACION')->max('ID_CERTIFICACION') ?? 0) + 1;
        DB::table('EMPLEADO_CERTIFICACION')->insert([
            'ID_CERTIFICACION' => $newId,
            'ID_EMPLEADO' => (int) $id,
            'NOMBRE' => $request->NOMBRE,
            'INSTITUCION' => $request->INSTITUCION,
            'FECHA_EMISION' => $request->FECHA_EMISION,
            'FECHA_VENCIMIENTO' => $request->FECHA_VENCIMIENTO,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_CERTIFICACION' => $newId, 'message' => 'Certificación registrada.'], 201);
    }

    public function updateCertificacion(Request $request, $id, $certId)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'NOMBRE' => 'required|string|max:200',
            'INSTITUCION' => 'nullable|string|max:200',
            'FECHA_EMISION' => 'nullable|date',
            'FECHA_VENCIMIENTO' => 'nullable|date',
        ]);

        $updated = DB::table('EMPLEADO_CERTIFICACION')
            ->where('ID_CERTIFICACION', $certId)
            ->where('ID_EMPLEADO', $id)
            ->where('ESACTIVO', true)
            ->update([
                'NOMBRE' => $request->NOMBRE,
                'INSTITUCION' => $request->INSTITUCION,
                'FECHA_EMISION' => $request->FECHA_EMISION,
                'FECHA_VENCIMIENTO' => $request->FECHA_VENCIMIENTO,
            ]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Certificación actualizada.']);
    }

    public function destroyCertificacion($id, $certId)
    {
        $updated = DB::table('EMPLEADO_CERTIFICACION')
            ->where('ID_CERTIFICACION', $certId)
            ->where('ID_EMPLEADO', $id)
            ->update(['ESACTIVO' => false]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Certificación inactivada.']);
    }

    // —— Dependientes ——

    public function indexDependientes(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $query = DB::table('EMPLEADO_DEPENDIENTE')
            ->where('ID_EMPLEADO', $id)
            ->where('ESACTIVO', true)
            ->orderByDesc('ID_DEPENDIENTE');

        return $this->paginateQuery($query, $request, ['NOMBRES', 'APELLIDOS', 'PARENTESCO']);
    }

    public function storeDependiente(Request $request, $id)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'NOMBRES' => 'required|string|max:150',
            'APELLIDOS' => 'nullable|string|max:150',
            'PARENTESCO' => 'required|string|max:50',
            'FECHA_NACIMIENTO' => 'nullable|date',
            'DOCUMENTO_IDENTIDAD' => 'nullable|string|max:30',
        ]);

        $newId = (DB::table('EMPLEADO_DEPENDIENTE')->max('ID_DEPENDIENTE') ?? 0) + 1;
        DB::table('EMPLEADO_DEPENDIENTE')->insert([
            'ID_DEPENDIENTE' => $newId,
            'ID_EMPLEADO' => (int) $id,
            'NOMBRES' => $request->NOMBRES,
            'APELLIDOS' => $request->APELLIDOS,
            'PARENTESCO' => $request->PARENTESCO,
            'FECHA_NACIMIENTO' => $request->FECHA_NACIMIENTO,
            'DOCUMENTO_IDENTIDAD' => $request->DOCUMENTO_IDENTIDAD,
            'ESACTIVO' => true,
        ]);

        return response()->json(['ID_DEPENDIENTE' => $newId, 'message' => 'Dependiente registrado.'], 201);
    }

    public function updateDependiente(Request $request, $id, $depId)
    {
        $this->assertEmpleado((int) $id);
        $request->validate([
            'NOMBRES' => 'required|string|max:150',
            'APELLIDOS' => 'nullable|string|max:150',
            'PARENTESCO' => 'required|string|max:50',
            'FECHA_NACIMIENTO' => 'nullable|date',
            'DOCUMENTO_IDENTIDAD' => 'nullable|string|max:30',
        ]);

        $updated = DB::table('EMPLEADO_DEPENDIENTE')
            ->where('ID_DEPENDIENTE', $depId)
            ->where('ID_EMPLEADO', $id)
            ->where('ESACTIVO', true)
            ->update([
                'NOMBRES' => $request->NOMBRES,
                'APELLIDOS' => $request->APELLIDOS,
                'PARENTESCO' => $request->PARENTESCO,
                'FECHA_NACIMIENTO' => $request->FECHA_NACIMIENTO,
                'DOCUMENTO_IDENTIDAD' => $request->DOCUMENTO_IDENTIDAD,
            ]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Dependiente actualizado.']);
    }

    public function destroyDependiente($id, $depId)
    {
        $updated = DB::table('EMPLEADO_DEPENDIENTE')
            ->where('ID_DEPENDIENTE', $depId)
            ->where('ID_EMPLEADO', $id)
            ->update(['ESACTIVO' => false]);

        if (!$updated) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['message' => 'Dependiente inactivado.']);
    }
}
