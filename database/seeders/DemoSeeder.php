<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos de demostración (nómina, empleados, ejercicios ISR).
 * Uso opcional: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoNominaSeeder::class,
            DemoNominaMasivaSeeder::class,
            DemoQuincenalIsssSeeder::class,
            DemoQuincenalJunioRecalculoSeeder::class,
            UsuarioSeeder::class,
        ]);
    }
}
