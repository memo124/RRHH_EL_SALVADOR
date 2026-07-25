<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('TIPO_CONTRATACION', 'GRUPO_NOMINA')) {
            Schema::table('TIPO_CONTRATACION', function (Blueprint $table) {
                $table->string('GRUPO_NOMINA', 20)->default('PLANILLA')->after('ES_EVENTUAL');
            });
        }

        if (!Schema::hasColumn('TIPO_PLANILLA', 'GRUPO_NOMINA')) {
            Schema::table('TIPO_PLANILLA', function (Blueprint $table) {
                $table->string('GRUPO_NOMINA', 20)->nullable()->after('ES_EVENTUAL');
            });
        }

        if (!Schema::hasColumn('ACUMULADO_RECALCULO', 'DEVENGADO_ACUMULADO')) {
            Schema::table('ACUMULADO_RECALCULO', function (Blueprint $table) {
                $table->decimal('DEVENGADO_ACUMULADO', 18, 2)->default(0)->nullable();
                $table->decimal('ISSS_EMPLEADO_ACUMULADO', 18, 2)->default(0)->nullable();
                $table->decimal('ISSS_PATRONAL_ACUMULADO', 18, 2)->default(0)->nullable();
                $table->decimal('INSAFORP_ACUMULADO', 18, 2)->default(0)->nullable();
            });
        }

        DB::table('TIPO_CONTRATACION')->where('ID_TIPOCONTRATACION', 1)->update(['GRUPO_NOMINA' => 'PLANILLA']);
        DB::table('TIPO_CONTRATACION')->where('ID_TIPOCONTRATACION', 2)->update(['GRUPO_NOMINA' => 'HONORARIOS']);
        DB::table('TIPO_CONTRATACION')->where('ID_TIPOCONTRATACION', 3)->update(['GRUPO_NOMINA' => 'COMERCIAL']);

        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', 1)->update(['GRUPO_NOMINA' => 'PLANILLA']);
        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', 2)->update(['GRUPO_NOMINA' => 'PLANILLA']);
        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', 3)->update(['GRUPO_NOMINA' => 'PLANILLA']);
        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', 4)->update(['GRUPO_NOMINA' => 'PLANILLA']);

        $extraTipos = [
            [
                'ID_TIPOPLANILLA' => 5,
                'TIPOPLANILLA' => 'Honorarios',
                'DESCRIPCION' => 'Planilla exclusiva para servicios profesionales / honorarios.',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA' => true,
                'APLICA_INSAFORP' => false,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'HONORARIOS',
                'ESACTIVO' => true,
            ],
            [
                'ID_TIPOPLANILLA' => 6,
                'TIPOPLANILLA' => 'Comercial',
                'DESCRIPCION' => 'Planilla exclusiva para relaciones comerciales independientes.',
                'APLICA_ISSS' => false,
                'APLICA_AFP' => false,
                'APLICA_RENTA' => false,
                'APLICA_INSAFORP' => false,
                'TOPE_SALARIAL_APLICABLE' => null,
                'APLICA_RENTA_SOBRE_EXCEDENTE' => false,
                'ES_EVENTUAL' => true,
                'GRUPO_NOMINA' => 'COMERCIAL',
                'ESACTIVO' => true,
            ],
        ];

        foreach ($extraTipos as $tipo) {
            DB::table('TIPO_PLANILLA')->updateOrInsert(['ID_TIPOPLANILLA' => $tipo['ID_TIPOPLANILLA']], $tipo);
        }
    }

    public function down(): void
    {
        DB::table('TIPO_PLANILLA')->whereIn('ID_TIPOPLANILLA', [5, 6])->delete();

        if (Schema::hasColumn('ACUMULADO_RECALCULO', 'DEVENGADO_ACUMULADO')) {
            Schema::table('ACUMULADO_RECALCULO', function (Blueprint $table) {
                $table->dropColumn([
                    'DEVENGADO_ACUMULADO',
                    'ISSS_EMPLEADO_ACUMULADO',
                    'ISSS_PATRONAL_ACUMULADO',
                    'INSAFORP_ACUMULADO',
                ]);
            });
        }

        if (Schema::hasColumn('TIPO_PLANILLA', 'GRUPO_NOMINA')) {
            Schema::table('TIPO_PLANILLA', function (Blueprint $table) {
                $table->dropColumn('GRUPO_NOMINA');
            });
        }

        if (Schema::hasColumn('TIPO_CONTRATACION', 'GRUPO_NOMINA')) {
            Schema::table('TIPO_CONTRATACION', function (Blueprint $table) {
                $table->dropColumn('GRUPO_NOMINA');
            });
        }
    }
};
