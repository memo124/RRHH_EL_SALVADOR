<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Instalación limpia: catálogos legales + RBAC sin empresa demo ni usuarios.
 * Uso: php artisan migrate --seed --class=BootstrapSeeder
 * Luego completar /setup en el navegador (empresa + administrador).
 */
class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PaisSeeder::class,
            GeografiaSeeder::class,
            TipoDocumentoIdentidadSeeder::class,
            ActividadEconomicaSeeder::class,
            TipoContratacionSeeder::class,
            IncapacitySeeder::class,
            HorasExtrasSeeder::class,
            TipoPlanillaSeeder::class,
            CatalogosSueldosSeeder::class,
            TaxSeeder::class,
            IsrFrecuenciaSeeder::class,
            RbacSeeder::class,
            PlantillaContratoSeeder::class,
        ]);
    }
}
