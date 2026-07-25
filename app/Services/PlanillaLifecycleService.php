<?php

namespace App\Services;

use App\Models\Planilla;
use App\Services\PayrollPostingService;
use Illuminate\Support\Facades\DB;

class PlanillaLifecycleService
{
    protected $posting;

    public function __construct(PayrollPostingService $posting)
    {
        $this->posting = $posting;
    }

    public function cerrar(int $planillaId): void
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new \RuntimeException('Planilla no encontrada.');
        }
        if ($planilla->ANULADA) {
            throw new \RuntimeException('No se puede cerrar una planilla anulada.');
        }
        if (!$planilla->RECALCULADA) {
            throw new \RuntimeException('Debe calcular la planilla antes de cerrarla.');
        }

        DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update([
            'CERRADA' => true,
            'ESACTIVA' => false,
        ]);
    }

    public function anular(int $planillaId): void
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new \RuntimeException('Planilla no encontrada.');
        }
        if ($planilla->CONTABILIZADA) {
            throw new \RuntimeException('No se puede anular una planilla contabilizada.');
        }

        $this->posting->reverseLoanPayments($planillaId);

        DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update([
            'ANULADA' => true,
            'CERRADA' => false,
            'ESACTIVA' => false,
        ]);
    }

    public function contabilizar(int $planillaId, ?string $usuario = null): void
    {
        $planilla = DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->first();
        if (!$planilla) {
            throw new \RuntimeException('Planilla no encontrada.');
        }
        if (!$planilla->CERRADA) {
            throw new \RuntimeException('Debe cerrar la planilla antes de contabilizarla.');
        }
        if ($planilla->ANULADA) {
            throw new \RuntimeException('No se puede contabilizar una planilla anulada.');
        }

        DB::table('PLANILLA')->where('ID_PLANILLA', $planillaId)->update([
            'CONTABILIZADA' => true,
            'AUTORIZADAPOR' => $usuario,
        ]);
    }
}
