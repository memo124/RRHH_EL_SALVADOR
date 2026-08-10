<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('NOTIFICACION', function (Blueprint $table) {
            $table->integer('ID_NOTIFICACION')->primary();
            $table->integer('ID_USUARIO');
            $table->string('TITULO', 200);
            $table->text('MENSAJE');
            $table->string('TIPO', 30)->default('info');
            $table->boolean('LEIDA')->default(false);
            $table->string('LINK', 250)->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('cascade');
            $table->index(['ID_USUARIO', 'LEIDA']);
        });

        Schema::create('NOTIFICACION_PREFERENCIA', function (Blueprint $table) {
            $table->integer('ID_USUARIO');
            $table->string('EVENTO', 60);
            $table->boolean('EMAIL')->default(false);
            $table->boolean('IN_APP')->default(true);

            $table->primary(['ID_USUARIO', 'EVENTO']);
            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NOTIFICACION_PREFERENCIA');
        Schema::dropIfExists('NOTIFICACION');
    }
};
