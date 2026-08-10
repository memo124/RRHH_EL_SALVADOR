<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Consultas de autoservicio para el Portal Empleado. Toda consulta se restringe
 * explícitamente al ID_EMPLEADO recibido (nunca expone datos de otros empleados).
 */
class PortalService
{
    public function __construct(protected EvaluacionDesempenoService $evaluacion) {}

    public function perfil(int $idEmpleado): ?object
    {
        return DB::table('EMPLEADO')
            ->leftJoin('EMPRESA', 'EMPLEADO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('DEPARTAMENTO', 'EMPLEADO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->leftJoin('CARGO', 'EMPLEADO.ID_CARGO', '=', 'CARGO.ID_CARGO')
            ->leftJoin('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->leftJoin('EMPLEADO as JEFE', 'EMPLEADO.ID_JEFE_INMEDIATO', '=', 'JEFE.ID_EMPLEADO')
            ->where('EMPLEADO.ID_EMPLEADO', $idEmpleado)
            ->select(
                'EMPLEADO.ID_EMPLEADO',
                'EMPLEADO.CODIGOEMPLEADO',
                'EMPLEADO.NOMBRES',
                'EMPLEADO.APELLIDO_1',
                'EMPLEADO.APELLIDO_2',
                'EMPLEADO.DUI',
                'EMPLEADO.NIT',
                'EMPLEADO.ISSS',
                'EMPLEADO.NUP',
                'EMPLEADO.GENERO',
                'EMPLEADO.FECHANACIMIENTO',
                'EMPLEADO.FECHAINGRESO',
                'EMPLEADO.CORREOELECTRONICO',
                'EMPLEADO.CORREOELECTRONICOEMPRESARIAL',
                'EMPLEADO.TELEFONOCELULAR',
                'EMPLEADO.DIRECCION',
                'EMPRESA.NOMBREEMPRESA',
                'DEPARTAMENTO.NOMBREDEPARTAMENTO',
                'CARGO.NOMBRECARGO',
                'TIPO_CONTRATACION.TIPOCONTRATACION',
                DB::raw('NULLIF(TRIM("JEFE"."NOMBRES" || \' \' || "JEFE"."APELLIDO_1"), \'\') as "JEFE_NOMBRE"')
            )
            ->first();
    }

    public function boletas(int $idEmpleado, int $perPage = 12)
    {
        return DB::table('DETALLE_PLANILLA')
            ->join('PLANILLA', 'DETALLE_PLANILLA.ID_PLANILLA', '=', 'PLANILLA.ID_PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->where('DETALLE_PLANILLA.ID_EMPLEADO', $idEmpleado)
            ->where('PLANILLA.CERRADA', true)
            ->where('PLANILLA.ANULADA', false)
            ->select(
                'DETALLE_PLANILLA.ID_DETALLEPLANILLA',
                'DETALLE_PLANILLA.ID_PLANILLA',
                'DETALLE_PLANILLA.TOTAL_DEVENGADO',
                'DETALLE_PLANILLA.TOTAL_DEDUCCIONES',
                'DETALLE_PLANILLA.LIQUIDO_A_RECIBIR',
                'PLANILLA.TITULO',
                'PLANILLA.FECHAPAGO',
                'TIPO_PLANILLA.TIPOPLANILLA'
            )
            ->orderByDesc('PLANILLA.FECHAPAGO')
            ->orderByDesc('DETALLE_PLANILLA.ID_DETALLEPLANILLA')
            ->paginate($perPage);
    }

    public function boletaDeEmpleado(int $idEmpleado, int $idDetalle): ?object
    {
        return DB::table('DETALLE_PLANILLA')
            ->where('ID_DETALLEPLANILLA', $idDetalle)
            ->where('ID_EMPLEADO', $idEmpleado)
            ->first();
    }

    public function misEvaluaciones(int $idEmpleado): array
    {
        return DB::table('EVALUACION_DESEMPENO')
            ->join('EVALUACION_PERIODO', 'EVALUACION_DESEMPENO.ID_PERIODO', '=', 'EVALUACION_PERIODO.ID_PERIODO')
            ->join('EMPLEADO as EVALUADOR', 'EVALUACION_DESEMPENO.ID_EVALUADOR', '=', 'EVALUADOR.ID_EMPLEADO')
            ->where('EVALUACION_DESEMPENO.ID_EMPLEADO', $idEmpleado)
            ->where('EVALUACION_DESEMPENO.ESACTIVO', true)
            ->select(
                'EVALUACION_DESEMPENO.*',
                'EVALUACION_PERIODO.NOMBRE as PERIODO_NOMBRE',
                'EVALUACION_PERIODO.ANIO',
                DB::raw('"EVALUADOR"."NOMBRES" || \' \' || "EVALUADOR"."APELLIDO_1" as "EVALUADOR_NOMBRE"')
            )
            ->orderByDesc('EVALUACION_PERIODO.ANIO')
            ->orderByDesc('EVALUACION_DESEMPENO.ID_EVALUACION')
            ->get()
            ->all();
    }

    public function evaluacionDetalle(int $idEmpleado, int $idEvaluacion): ?array
    {
        $eval = DB::table('EVALUACION_DESEMPENO')
            ->join('EVALUACION_PERIODO', 'EVALUACION_DESEMPENO.ID_PERIODO', '=', 'EVALUACION_PERIODO.ID_PERIODO')
            ->join('EMPLEADO as EVALUADOR', 'EVALUACION_DESEMPENO.ID_EVALUADOR', '=', 'EVALUADOR.ID_EMPLEADO')
            ->where('EVALUACION_DESEMPENO.ID_EVALUACION', $idEvaluacion)
            ->where('EVALUACION_DESEMPENO.ID_EMPLEADO', $idEmpleado)
            ->select(
                'EVALUACION_DESEMPENO.*',
                'EVALUACION_PERIODO.NOMBRE as PERIODO_NOMBRE',
                DB::raw('"EVALUADOR"."NOMBRES" || \' \' || "EVALUADOR"."APELLIDO_1" as "EVALUADOR_NOMBRE"')
            )
            ->first();

        if (!$eval) {
            return null;
        }

        return [
            'evaluacion' => $eval,
            'metas' => $this->evaluacion->getMetas($idEvaluacion),
        ];
    }
}
