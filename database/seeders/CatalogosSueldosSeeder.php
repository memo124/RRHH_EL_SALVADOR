<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSueldosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. AFP (Crecer, Confía, IPSFA)
        $afps = [
            [
                'ID_AFP' => 1,
                'NOMBREAFP' => 'Crecer',
                'CODIGOPREVISIONAL' => '01',
                'PORCENTAJEPATRONAL' => 8.75,
                'PORCENTAJEEMPLEADOR' => 7.25,
                'DEVENGADOMAXIMO' => 7028.29,
                'DEVENGADOMINIMO' => 365.00,
                'ESACTIVO' => true
            ],
            [
                'ID_AFP' => 2,
                'NOMBREAFP' => 'Confía',
                'CODIGOPREVISIONAL' => '02',
                'PORCENTAJEPATRONAL' => 8.75,
                'PORCENTAJEEMPLEADOR' => 7.25,
                'DEVENGADOMAXIMO' => 7028.29,
                'DEVENGADOMINIMO' => 365.00,
                'ESACTIVO' => true
            ],
            [
                'ID_AFP' => 3,
                'NOMBREAFP' => 'IPSFA',
                'CODIGOPREVISIONAL' => '03',
                'PORCENTAJEPATRONAL' => 8.75,
                'PORCENTAJEEMPLEADOR' => 7.25,
                'DEVENGADOMAXIMO' => 7028.29,
                'DEVENGADOMINIMO' => 365.00,
                'ESACTIVO' => true
            ]
        ];
        foreach ($afps as $afp) {
            DB::table('AFP')->updateOrInsert(['ID_AFP' => $afp['ID_AFP']], $afp);
        }

        // Ensure Pais 61 and 503 exist
        DB::table('PAIS')->updateOrInsert(['ID_PAIS' => 61], ['CODIGO_MH' => 'SV', 'NOMBREPAIS' => 'El Salvador']);
        DB::table('PAIS')->updateOrInsert(['ID_PAIS' => 503], ['CODIGO_MH' => 'SV', 'NOMBREPAIS' => 'El Salvador (MH)']);

        // 2. BANCO
        $bancos = [
            ['ID_BANCO' => 1, 'ID_PAIS' => 61, 'NOMBREBANCO' => 'Banco Agrícola', 'ALIAS' => 'AGR', 'BANCOACTIVO' => true],
            ['ID_BANCO' => 2, 'ID_PAIS' => 61, 'NOMBREBANCO' => 'Banco Cuscatlán', 'ALIAS' => 'CUS', 'BANCOACTIVO' => true],
            ['ID_BANCO' => 3, 'ID_PAIS' => 61, 'NOMBREBANCO' => 'Banco Davivienda', 'ALIAS' => 'DAV', 'BANCOACTIVO' => true],
            ['ID_BANCO' => 4, 'ID_PAIS' => 61, 'NOMBREBANCO' => 'Banco de América Central', 'ALIAS' => 'BAC', 'BANCOACTIVO' => true]
        ];
        foreach ($bancos as $banco) {
            DB::table('BANCO')->updateOrInsert(['ID_BANCO' => $banco['ID_BANCO']], $banco);
        }

        // 3. TIPO_DESCUENTO (ley, préstamos y descuentos voluntarios)
        $this->call(TipoDescuentoSeeder::class);

        // 4. TIPO_PRESTAMO
        $prestamos = [
            ['ID_TIPOPRESTAMO' => 1, 'NOMBREPRESTAMO' => 'Préstamo Hipotecario', 'OBSERVACIONES' => 'Descuento para adquisición de vivienda.'],
            ['ID_TIPOPRESTAMO' => 2, 'NOMBREPRESTAMO' => 'Préstamo Personal', 'OBSERVACIONES' => 'Créditos de consumo.'],
            ['ID_TIPOPRESTAMO' => 3, 'NOMBREPRESTAMO' => 'Adelanto de Salario', 'OBSERVACIONES' => 'Anticipo solicitado por el empleado.']
        ];
        foreach ($prestamos as $prest) {
            DB::table('TIPO_PRESTAMO')->updateOrInsert(['ID_TIPOPRESTAMO' => $prest['ID_TIPOPRESTAMO']], $prest);
        }

        // 5. TIPO_INGRESO
        $ingresos = [
            ['ID_TIPOINGRESO' => 1, 'TIPOINGRESO' => 'Bono de Ventas', 'ESACTIVO' => true],
            ['ID_TIPOINGRESO' => 2, 'TIPOINGRESO' => 'Comisión', 'ESACTIVO' => true],
            ['ID_TIPOINGRESO' => 3, 'TIPOINGRESO' => 'Horas Extras', 'ESACTIVO' => true],
            ['ID_TIPOINGRESO' => 4, 'TIPOINGRESO' => 'Aguinaldo Extraordinario', 'ESACTIVO' => true]
        ];
        foreach ($ingresos as $ing) {
            DB::table('TIPO_INGRESO')->updateOrInsert(['ID_TIPOINGRESO' => $ing['ID_TIPOINGRESO']], $ing);
        }

        DB::table('PERFIL_PAGO')->updateOrInsert(
            ['ID_PERFILPAGO' => 1],
            [
                'PEFILPAGO' => 'Estándar',
                'GRATIFICACIONES' => true,
                'EXTRA_GRATIFICACIONES' => true,
            ]
        );

        // Call IsrFrecuenciaSeeder and TaxSeeder for Retenciones/Frecuencias
        $isrSeeder = new IsrFrecuenciaSeeder();
        $isrSeeder->run();

        $taxSeeder = new TaxSeeder();
        $taxSeeder->run();
    }
}
