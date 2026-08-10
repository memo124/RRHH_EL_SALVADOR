<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CANDIDATO', function (Blueprint $table) {
            if (!Schema::hasColumn('CANDIDATO', 'ID_EMPLEADO')) {
                $table->integer('ID_EMPLEADO')->nullable()->after('ID_ADJUNTO_CV');
                $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('CANDIDATO', function (Blueprint $table) {
            if (Schema::hasColumn('CANDIDATO', 'ID_EMPLEADO')) {
                $table->dropForeign(['ID_EMPLEADO']);
                $table->dropColumn('ID_EMPLEADO');
            }
        });
    }
};
