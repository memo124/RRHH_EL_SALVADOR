<?php

namespace App\Services\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Consulta base de planillas cerradas (RECALCULADA + CERRADA, no anuladas), usada
 * por los reportes de cumplimiento legal (ISSS, AFP, INSAFORP, Retención 10%) para
 * asegurar que solo se exporten periodos ya definitivos.
 */
trait ListsClosedPlanillas
{
    protected function closedPlanillasQuery(?int $empresaId = null): Builder
    {
        $query = DB::table('PLANILLA')
            ->join('TIPO_PLANILLA', 'PLANILLA.ID_TIPOPLANILLA', '=', 'TIPO_PLANILLA.ID_TIPOPLANILLA')
            ->join('PERIODO_LABORAL', 'PLANILLA.ID_PERIODO', '=', 'PERIODO_LABORAL.ID_PERIODO')
            ->join('EMPRESA', 'PLANILLA.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->where('PLANILLA.CERRADA', true)
            ->where('PLANILLA.ANULADA', false)
            ->select(
                'PLANILLA.ID_PLANILLA',
                'PLANILLA.TITULO',
                'PLANILLA.FECHAPAGO',
                'PLANILLA.ID_EMPRESA',
                'EMPRESA.NOMBREEMPRESA',
                'TIPO_PLANILLA.TIPOPLANILLA',
                'TIPO_PLANILLA.GRUPO_NOMINA',
                'PERIODO_LABORAL.CALPERIODO'
            )
            ->orderByDesc('PLANILLA.FECHAPAGO');

        if ($empresaId) {
            $query->where('PLANILLA.ID_EMPRESA', $empresaId);
        }

        return $query;
    }
}
