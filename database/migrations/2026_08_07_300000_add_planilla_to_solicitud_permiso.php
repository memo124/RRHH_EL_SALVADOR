<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('SOLICITUD_PERMISO') && !Schema::hasColumn('SOLICITUD_PERMISO', 'ID_PLANILLA')) {
            Schema::table('SOLICITUD_PERMISO', function (Blueprint $table) {
                $table->integer('ID_PLANILLA')->nullable()->after('INTEGRADO_PLANILLA');
                $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('SOLICITUD_PERMISO') && Schema::hasColumn('SOLICITUD_PERMISO', 'ID_PLANILLA')) {
            Schema::table('SOLICITUD_PERMISO', function (Blueprint $table) {
                $table->dropForeign(['ID_PLANILLA']);
                $table->dropColumn('ID_PLANILLA');
            });
        }
    }
};
