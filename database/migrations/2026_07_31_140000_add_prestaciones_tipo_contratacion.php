<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TIPO_CONTRATACION', function (Blueprint $table) {
            if (!Schema::hasColumn('TIPO_CONTRATACION', 'APLICA_AGUINALDO')) {
                $table->boolean('APLICA_AGUINALDO')->default(false);
            }
            if (!Schema::hasColumn('TIPO_CONTRATACION', 'APLICA_QUINCENA_25')) {
                $table->boolean('APLICA_QUINCENA_25')->default(false);
            }
            if (!Schema::hasColumn('TIPO_CONTRATACION', 'ANIOS_MINIMOS_QUINCENA_25')) {
                $table->integer('ANIOS_MINIMOS_QUINCENA_25')->default(1);
            }
            if (!Schema::hasColumn('TIPO_CONTRATACION', 'PORCENTAJE_QUINCENA_25')) {
                $table->decimal('PORCENTAJE_QUINCENA_25', 5, 2)->default(50.00);
            }
        });

        DB::table('TIPO_CONTRATACION')->where('ID_TIPOCONTRATACION', 1)->update([
            'APLICA_AGUINALDO' => true,
            'APLICA_QUINCENA_25' => true,
            'ANIOS_MINIMOS_QUINCENA_25' => 1,
            'PORCENTAJE_QUINCENA_25' => 50.00,
        ]);

        DB::table('TIPO_CONTRATACION')->whereIn('ID_TIPOCONTRATACION', [2, 3])->update([
            'APLICA_AGUINALDO' => false,
            'APLICA_QUINCENA_25' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('TIPO_CONTRATACION', function (Blueprint $table) {
            foreach (['APLICA_AGUINALDO', 'APLICA_QUINCENA_25', 'ANIOS_MINIMOS_QUINCENA_25', 'PORCENTAJE_QUINCENA_25'] as $col) {
                if (Schema::hasColumn('TIPO_CONTRATACION', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
