<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HorasExtrasSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            // Días laborables — Art. 178-179 Código de Trabajo (recargo 100% diurna, 125% nocturna)
            ['ID_HORASEXTRAS' => 1, 'TIPOHORAEXTRA' => 'HE Fija Diurna', 'PORCENTAJEEXTRA' => 100.00, 'FACTOR' => 2.00, 'MODALIDAD' => 'FIJA', 'JORNADA' => 'DIURNA', 'ES_DOMINICAL' => false, 'CODIGO' => 'HE_FIJA_DIURNA'],
            ['ID_HORASEXTRAS' => 2, 'TIPOHORAEXTRA' => 'HE Adicional Diurna', 'PORCENTAJEEXTRA' => 100.00, 'FACTOR' => 2.00, 'MODALIDAD' => 'ADICIONAL', 'JORNADA' => 'DIURNA', 'ES_DOMINICAL' => false, 'CODIGO' => 'HE_ADICIONAL_DIURNA'],
            ['ID_HORASEXTRAS' => 3, 'TIPOHORAEXTRA' => 'HE Fija Nocturna', 'PORCENTAJEEXTRA' => 125.00, 'FACTOR' => 2.25, 'MODALIDAD' => 'FIJA', 'JORNADA' => 'NOCTURNA', 'ES_DOMINICAL' => false, 'CODIGO' => 'HE_FIJA_NOCTURNA'],
            ['ID_HORASEXTRAS' => 4, 'TIPOHORAEXTRA' => 'HE Adicional Nocturna', 'PORCENTAJEEXTRA' => 125.00, 'FACTOR' => 2.25, 'MODALIDAD' => 'ADICIONAL', 'JORNADA' => 'NOCTURNA', 'ES_DOMINICAL' => false, 'CODIGO' => 'HE_ADICIONAL_NOCTURNA'],

            // Día de descanso semanal / dominical
            ['ID_HORASEXTRAS' => 5, 'TIPOHORAEXTRA' => 'HE Fija Diurna Dominical', 'PORCENTAJEEXTRA' => 100.00, 'FACTOR' => 2.00, 'MODALIDAD' => 'FIJA', 'JORNADA' => 'DIURNA', 'ES_DOMINICAL' => true, 'CODIGO' => 'HE_FIJA_DIURNA_DOM'],
            ['ID_HORASEXTRAS' => 6, 'TIPOHORAEXTRA' => 'HE Adicional Diurna Dominical', 'PORCENTAJEEXTRA' => 100.00, 'FACTOR' => 2.00, 'MODALIDAD' => 'ADICIONAL', 'JORNADA' => 'DIURNA', 'ES_DOMINICAL' => true, 'CODIGO' => 'HE_ADICIONAL_DIURNA_DOM'],
            ['ID_HORASEXTRAS' => 7, 'TIPOHORAEXTRA' => 'HE Fija Nocturna Dominical', 'PORCENTAJEEXTRA' => 200.00, 'FACTOR' => 3.00, 'MODALIDAD' => 'FIJA', 'JORNADA' => 'NOCTURNA', 'ES_DOMINICAL' => true, 'CODIGO' => 'HE_FIJA_NOCTURNA_DOM'],
            ['ID_HORASEXTRAS' => 8, 'TIPOHORAEXTRA' => 'HE Adicional Nocturna Dominical', 'PORCENTAJEEXTRA' => 200.00, 'FACTOR' => 3.00, 'MODALIDAD' => 'ADICIONAL', 'JORNADA' => 'NOCTURNA', 'ES_DOMINICAL' => true, 'CODIGO' => 'HE_ADICIONAL_NOCTURNA_DOM'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('HORAS_EXTRAS')->updateOrInsert(['ID_HORASEXTRAS' => $tipo['ID_HORASEXTRAS']], $tipo);
        }
    }
}
