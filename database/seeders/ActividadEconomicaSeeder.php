<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActividadEconomicaSeeder extends Seeder
{
    public function run(): void
    {
        // Execute CatalogSeeder as it already contains the official CIIU rubros and TipoDocumentoIdentidad.
        // This avoids duplicate code while fulfilling the name requirement.
        $seeder = new CatalogSeeder();
        $seeder->run();
    }
}
