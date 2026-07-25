<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // Registrar Tipos de Descuento primero para evitar errores de FK
        DB::table('TIPO_DESCUENTO')->updateOrInsert(
            ['ID_TIPODESCUENTO' => 1],
            ['NOMBRETIPODESC' => 'ISSS', 'DESCRIPCIONTIPODESC' => 'Instituto Salvadoreño del Seguro Social', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true]
        );
        DB::table('TIPO_DESCUENTO')->updateOrInsert(
            ['ID_TIPODESCUENTO' => 2],
            ['NOMBRETIPODESC' => 'AFP', 'DESCRIPCIONTIPODESC' => 'Administradora de Fondos de Pensiones', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true]
        );

        // Limites salariales mínimos y máximos para cotizaciones 2026
        DB::table('RETENCION_LEY')->updateOrInsert(
            ['ID_RETENCIONLEY' => 1],
            [
                'ID_PAIS' => 503,
                'ID_TIPODESCUENTO' => 1, // ISSS
                'APORTACIONPATRONAL' => 7.50,
                'APORTACIONEMPLEADO' => 3.00,
                'SALARIOMINIMO' => 365.00,
                'SALARIOMAXIMO' => 1000.00
            ]
        );

        DB::table('RETENCION_LEY')->updateOrInsert(
            ['ID_RETENCIONLEY' => 2],
            [
                'ID_PAIS' => 503,
                'ID_TIPODESCUENTO' => 2, // AFP
                'APORTACIONPATRONAL' => 8.75,
                'APORTACIONEMPLEADO' => 7.25,
                'SALARIOMINIMO' => 365.00,
                'SALARIOMAXIMO' => 7028.29
            ]
        );
    }
}
