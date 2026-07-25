<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncapacitySeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'ID_TIPOINCAPACIDAD' => 1,
                'NOMBRE_TIPO' => 'Enfermedad Común',
                'DESCRIPCION' => 'Incapacidad médica por enfermedad general',
                'PORCENTAJE_SUBSIDIO_ISSS' => 75.00,
                'PORCENTAJE_PAGO_PATRONO' => 100.00,
                'DIAS_INICIO_SUBSIDIO_ISSS' => 4,
                'DIAS_MAXIMOS_COBERTURA_PATRONO' => 3,
                'ES_MATERNIDAD' => false,
                'ES_ACCIDENTE_TRABAJO' => false,
                'ESACTIVO' => true
            ],
            [
                'ID_TIPOINCAPACIDAD' => 2,
                'NOMBRE_TIPO' => 'Maternidad',
                'DESCRIPCION' => 'Licencia por maternidad obligatoria (112 días)',
                'PORCENTAJE_SUBSIDIO_ISSS' => 100.00,
                'PORCENTAJE_PAGO_PATRONO' => 0.00,
                'DIAS_INICIO_SUBSIDIO_ISSS' => 1,
                'DIAS_MAXIMOS_COBERTURA_PATRONO' => 0,
                'ES_MATERNIDAD' => true,
                'ES_ACCIDENTE_TRABAJO' => false,
                'ESACTIVO' => true
            ],
            [
                'ID_TIPOINCAPACIDAD' => 3,
                'NOMBRE_TIPO' => 'Accidente de Trabajo',
                'DESCRIPCION' => 'Incapacidad por riesgo laboral',
                'PORCENTAJE_SUBSIDIO_ISSS' => 75.00,
                'PORCENTAJE_PAGO_PATRONO' => 100.00,
                'DIAS_INICIO_SUBSIDIO_ISSS' => 4,
                'DIAS_MAXIMOS_COBERTURA_PATRONO' => 3,
                'ES_MATERNIDAD' => false,
                'ES_ACCIDENTE_TRABAJO' => true,
                'ESACTIVO' => true
            ]
        ];

        foreach ($tipos as $tipo) {
            DB::table('TIPO_INCAPACIDAD')->updateOrInsert(['ID_TIPOINCAPACIDAD' => $tipo['ID_TIPOINCAPACIDAD']], $tipo);
        }
    }
}
