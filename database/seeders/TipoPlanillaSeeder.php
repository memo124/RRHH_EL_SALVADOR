<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPlanillaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'ID_TIPOPLANILLA' => 1,
                'TIPOPLANILLA' => 'Ordinaria',
                'DESCRIPCION' => 'Planilla regular mensual o quincenal.',
                'APLICA_ISSS' => true,
                'APLICA_AFP' => true,
                'APLICA_RENTA' => true,
                'APLICA_INSAFORP' => true,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => false,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 2,
                'TIPOPLANILLA' => 'Vacaciones',
                'DESCRIPCION' => 'Planilla para pago de vacaciones anuales colectivas o individuales.',
                'APLICA_ISSS' => true,
                'APLICA_AFP' => true,
                'APLICA_RENTA' => true,
                'APLICA_INSAFORP' => true,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => false,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 3,
                'TIPOPLANILLA' => 'Aguinaldo',
                'DESCRIPCION' => 'Planilla para el pago del aguinaldo legal de fin de año.',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA' => false,
                'APLICA_INSAFORP' => false,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => false,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 4,
                'TIPOPLANILLA' => 'Extraordinaria',
                'DESCRIPCION' => 'Planilla para pagos adicionales, bonos y comisiones especiales.',
                'APLICA_ISSS' => true,
                'APLICA_AFP' => true,
                'APLICA_RENTA' => true,
                'APLICA_INSAFORP' => true,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => true,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 5,
                'TIPOPLANILLA' => 'Honorarios',
                'DESCRIPCION' => 'Planilla exclusiva para servicios profesionales / honorarios.',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA' => true,
                'APLICA_INSAFORP' => false,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'HONORARIOS',
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 6,
                'TIPOPLANILLA' => 'Comercial',
                'DESCRIPCION' => 'Planilla exclusiva para relaciones comerciales independientes.',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA' => false,
                'APLICA_INSAFORP' => false,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'COMERCIAL',
                'ESACTIVO' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('TIPO_PLANILLA')->updateOrInsert(['ID_TIPOPLANILLA' => $tipo['ID_TIPOPLANILLA']], $tipo);
        }

        DB::table('TIPO_PLANILLA')->whereIn('ID_TIPOPLANILLA', [1, 2, 3, 4])->update(['GRUPO_NOMINA' => 'PLANILLA']);
    }
}
