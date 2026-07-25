<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
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
            UsuarioSeeder::class,
        ]);
    }
}
