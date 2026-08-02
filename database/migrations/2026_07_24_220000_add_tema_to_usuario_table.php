<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('USUARIO', 'TEMA')) {
            Schema::table('USUARIO', function (Blueprint $table) {
                $table->string('TEMA', 20)->default('auto');
            });
        }

        DB::table('USUARIO')->whereNull('TEMA')->update(['TEMA' => 'auto']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('USUARIO', 'TEMA')) {
            Schema::table('USUARIO', function (Blueprint $table) {
                $table->dropColumn('TEMA');
            });
        }
    }
};
