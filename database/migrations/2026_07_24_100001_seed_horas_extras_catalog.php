<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('HORAS_EXTRAS')->count() > 0) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\HorasExtrasSeeder',
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Catálogo de referencia; no se elimina en rollback.
    }
};
