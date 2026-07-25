<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('HORAS_EXTRAS', function (Blueprint $table) {
            $table->string('MODALIDAD', 20)->default('ADICIONAL')->after('FACTOR');
            $table->string('JORNADA', 20)->default('DIURNA')->after('MODALIDAD');
            $table->boolean('ES_DOMINICAL')->default(false)->after('JORNADA');
            $table->string('CODIGO', 40)->nullable()->after('ES_DOMINICAL');
        });

        Schema::table('EMPLEADO', function (Blueprint $table) {
            $table->decimal('HORAS_EXTRAS_FIJAS_DIURAS', 5, 2)->default(0)->after('SALARIODIARIO');
            $table->decimal('HORAS_EXTRAS_FIJAS_NOCTURNAS', 5, 2)->default(0)->after('HORAS_EXTRAS_FIJAS_DIURAS');
        });

        Schema::table('ASISTENCIA_DIARIA', function (Blueprint $table) {
            $table->boolean('ES_DIA_DESCANSO')->default(false)->after('HORAS_EXTRAS_NOCTURNAS');
            $table->decimal('HORAS_EXTRAS_FIJAS_DIURNAS', 5, 2)->default(0)->after('ES_DIA_DESCANSO');
            $table->decimal('HORAS_EXTRAS_ADICIONALES_DIURNAS', 5, 2)->default(0)->after('HORAS_EXTRAS_FIJAS_DIURNAS');
            $table->decimal('HORAS_EXTRAS_FIJAS_NOCTURNAS', 5, 2)->default(0)->after('HORAS_EXTRAS_ADICIONALES_DIURNAS');
            $table->decimal('HORAS_EXTRAS_ADICIONALES_NOCTURNAS', 5, 2)->default(0)->after('HORAS_EXTRAS_FIJAS_NOCTURNAS');
        });
    }

    public function down(): void
    {
        Schema::table('ASISTENCIA_DIARIA', function (Blueprint $table) {
            $table->dropColumn([
                'ES_DIA_DESCANSO',
                'HORAS_EXTRAS_FIJAS_DIURNAS',
                'HORAS_EXTRAS_ADICIONALES_DIURNAS',
                'HORAS_EXTRAS_FIJAS_NOCTURNAS',
                'HORAS_EXTRAS_ADICIONALES_NOCTURNAS',
            ]);
        });

        Schema::table('EMPLEADO', function (Blueprint $table) {
            $table->dropColumn(['HORAS_EXTRAS_FIJAS_DIURAS', 'HORAS_EXTRAS_FIJAS_NOCTURNAS']);
        });

        Schema::table('HORAS_EXTRAS', function (Blueprint $table) {
            $table->dropColumn(['MODALIDAD', 'JORNADA', 'ES_DOMINICAL', 'CODIGO']);
        });
    }
};
