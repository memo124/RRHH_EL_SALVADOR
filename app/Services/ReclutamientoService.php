<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReclutamientoService
{
    public function __construct(protected CalendarioEventoService $calendario) {}

    public function etapas(): array
    {
        return DB::table('ETAPA_RECLUTAMIENTO')->where('ESACTIVO', true)->orderBy('ORDEN')->get()->all();
    }

    public function getCandidatos(int $idVacante): array
    {
        return DB::table('CANDIDATO')
            ->leftJoin('ETAPA_RECLUTAMIENTO', 'CANDIDATO.ID_ETAPA_ACTUAL', '=', 'ETAPA_RECLUTAMIENTO.ID_ETAPA')
            ->leftJoin('ADJUNTO as CV', 'CANDIDATO.ID_ADJUNTO_CV', '=', 'CV.ID_ADJUNTO')
            ->where('CANDIDATO.ID_VACANTE', $idVacante)
            ->where('CANDIDATO.ESACTIVO', true)
            ->select(
                'CANDIDATO.*',
                'ETAPA_RECLUTAMIENTO.NOMBRE as ETAPA_NOMBRE',
                'CV.NOMBRE_ARCHIVO as CV_NOMBRE'
            )
            ->orderByDesc('CANDIDATO.FECHA_REGISTRO')
            ->get()
            ->all();
    }

    public function registrarCandidato(array $data): int
    {
        $maxId = DB::table('CANDIDATO')->max('ID_CANDIDATO') ?? 0;
        $id = $maxId + 1;
        $primeraEtapa = DB::table('ETAPA_RECLUTAMIENTO')->where('ESACTIVO', true)->orderBy('ORDEN')->value('ID_ETAPA');

        DB::table('CANDIDATO')->insert([
            'ID_CANDIDATO' => $id,
            'ID_VACANTE' => $data['ID_VACANTE'],
            'NOMBRES' => $data['NOMBRES'],
            'APELLIDOS' => $data['APELLIDOS'] ?? null,
            'EMAIL' => $data['EMAIL'] ?? null,
            'TELEFONO' => $data['TELEFONO'] ?? null,
            'ID_ETAPA_ACTUAL' => $data['ID_ETAPA_ACTUAL'] ?? $primeraEtapa,
            'ESTADO' => 'activo',
            'ID_ADJUNTO_CV' => $data['ID_ADJUNTO_CV'] ?? null,
            'FECHA_REGISTRO' => now(),
            'ESACTIVO' => true,
        ]);

        return $id;
    }

    public function avanzarEtapa(int $idCandidato, int $idEtapa): bool
    {
        return DB::table('CANDIDATO')->where('ID_CANDIDATO', $idCandidato)->update([
            'ID_ETAPA_ACTUAL' => $idEtapa,
        ]) > 0;
    }

    public function attachCv(int $idCandidato, int $idAdjunto): bool
    {
        return DB::table('CANDIDATO')->where('ID_CANDIDATO', $idCandidato)->update([
            'ID_ADJUNTO_CV' => $idAdjunto,
        ]) > 0;
    }

    public function programarEntrevista(array $data): int
    {
        $maxId = DB::table('CANDIDATO_ENTREVISTA')->max('ID_ENTREVISTA') ?? 0;
        $id = $maxId + 1;

        DB::table('CANDIDATO_ENTREVISTA')->insert([
            'ID_ENTREVISTA' => $id,
            'ID_CANDIDATO' => $data['ID_CANDIDATO'],
            'FECHA_HORA' => $data['FECHA_HORA'],
            'TIPO' => $data['TIPO'] ?? 'presencial',
            'ID_EMPLEADO_ENTREVISTADOR' => $data['ID_EMPLEADO_ENTREVISTADOR'] ?? null,
            'RESULTADO' => 'pendiente',
            'OBSERVACIONES' => $data['OBSERVACIONES'] ?? null,
            'ESACTIVO' => true,
        ]);

        $candidato = DB::table('CANDIDATO')->where('ID_CANDIDATO', $data['ID_CANDIDATO'])->first();
        $nombre = trim(($candidato->NOMBRES ?? '') . ' ' . ($candidato->APELLIDOS ?? ''));

        $this->calendario->syncFromOrigen('entrevista', $id, [
            'TIPO' => 'reunion_rrhh',
            'TITULO' => "Entrevista: {$nombre}",
            'DESCRIPCION' => 'Entrevista de reclutamiento',
            'FECHA_INICIO' => $data['FECHA_HORA'],
            'FECHA_FIN' => $data['FECHA_HORA'],
            'TODO_DIA' => false,
            'ID_EMPLEADO' => $data['ID_EMPLEADO_ENTREVISTADOR'] ?? null,
        ]);

        return $id;
    }

    public function getEntrevistas(int $idCandidato): array
    {
        return DB::table('CANDIDATO_ENTREVISTA')
            ->leftJoin('EMPLEADO', 'CANDIDATO_ENTREVISTA.ID_EMPLEADO_ENTREVISTADOR', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('CANDIDATO_ENTREVISTA.ID_CANDIDATO', $idCandidato)
            ->where('CANDIDATO_ENTREVISTA.ESACTIVO', true)
            ->select(
                'CANDIDATO_ENTREVISTA.*',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as ENTREVISTADOR_NOMBRE")
            )
            ->orderByDesc('CANDIDATO_ENTREVISTA.FECHA_HORA')
            ->get()
            ->all();
    }

    public function cerrarVacante(int $idVacante): bool
    {
        return DB::table('VACANTE')->where('ID_VACANTE', $idVacante)->update([
            'ESTADO' => 'cerrada',
            'FECHA_CIERRE' => now(),
        ]) > 0;
    }
}
