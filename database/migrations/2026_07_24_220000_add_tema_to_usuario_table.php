<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('USUARIO', function (Blueprint $table) {
            $table->string('TEMA', 20)->default('auto')->after('BLOQUEADO');
        });

        DB::table('USUARIO')->update(['TEMA' => 'auto']);
    }

    public function down(): void
    {
        Schema::table('USUARIO', function (Blueprint $table) {
            $table->dropColumn('TEMA');
        });
    }
};
