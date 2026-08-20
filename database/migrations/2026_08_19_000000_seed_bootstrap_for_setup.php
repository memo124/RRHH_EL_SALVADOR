<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('PAIS') || DB::table('PAIS')->count() > 0) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\BootstrapSeeder',
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Catálogos de referencia; no se elimina en rollback.
    }
};
