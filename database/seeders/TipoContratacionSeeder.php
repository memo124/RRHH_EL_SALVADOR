<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoContratacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'ID_TIPOCONTRATACION' => 1,
                'TIPOCONTRATACION' => 'Permanente / Ley de Salarios',
                'DESCRIPCION' => 'Contrato por tiempo indefinido con todas las prestaciones de ley.',
                'ES_EVENTUAL' => false,
                'GRUPO_NOMINA' => 'PLANILLA',
                'APLICA_ISSS' => true,
                'APLICA_AFP' => true,
                'APLICA_RENTA_TABLA' => true,
                'APLICA_RENTA_FIJA' => false,
                'PORCENTAJE_RENTA_FIJA' => 0.00,
                'APLICA_INSAFORP' => true,
                'APLICA_AGUINALDO' => true,
                'APLICA_QUINCENA_25' => true,
                'ANIOS_MINIMOS_QUINCENA_25' => 1,
                'PORCENTAJE_QUINCENA_25' => 50.00,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOCONTRATACION' => 2,
                'TIPOCONTRATACION' => 'Servicios Profesionales / Honorarios',
                'DESCRIPCION' => 'Servicios profesionales independientes con retención del 10% de renta fija sin prestaciones.',
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'HONORARIOS',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA_TABLA' => false,
                'APLICA_RENTA_FIJA' => true,
                'PORCENTAJE_RENTA_FIJA' => 10.00,
                'APLICA_INSAFORP' => false,
                'APLICA_AGUINALDO' => false,
                'APLICA_QUINCENA_25' => false,
                'ANIOS_MINIMOS_QUINCENA_25' => 1,
                'PORCENTAJE_QUINCENA_25' => 50.00,
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOCONTRATACION' => 3,
                'TIPOCONTRATACION' => 'Comercial Independiente',
                'DESCRIPCION' => 'Relación de carácter mercantil sin dependencia laboral y exento de retenciones.',
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'COMERCIAL',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA_TABLA' => false,
                'APLICA_RENTA_FIJA' => false,
                'PORCENTAJE_RENTA_FIJA' => 0.00,
                'APLICA_INSAFORP' => false,
                'APLICA_AGUINALDO' => false,
                'APLICA_QUINCENA_25' => false,
                'ESACTIVO' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('TIPO_CONTRATACION')->updateOrInsert(['ID_TIPOCONTRATACION' => $tipo['ID_TIPOCONTRATACION']], $tipo);
        }
    }
}
