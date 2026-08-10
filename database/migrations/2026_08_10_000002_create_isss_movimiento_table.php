<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ISSS_MOVIMIENTO', function (Blueprint $table) {
            $table->increments('ID_MOVIMIENTO');
            $table->integer('ID_EMPLEADO');
            $table->string('TIPO', 10);
            $table->date('FECHA');
            $table->string('ESTADO', 20)->default('pendiente');
            $table->json('DATOS_JSON')->nullable();
            $table->timestamp('FECHA_ENVIO')->nullable();
            $table->string('USUARIO_ENVIO', 100)->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->index(['TIPO', 'ESTADO']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ISSS_MOVIMIENTO');
    }
};
