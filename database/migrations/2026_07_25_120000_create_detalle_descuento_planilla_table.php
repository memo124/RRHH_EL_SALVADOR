<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DETALLE_DESCUENTO_PLANILLA', function (Blueprint $table) {
            $table->integer('ID_DETALLEDESCPLANILLA')->primary();
            $table->integer('ID_DETALLEPLANILLA');
            $table->integer('ID_TIPODESCUENTO')->nullable();
            $table->string('CONCEPTO', 150);
            $table->string('CATEGORIA', 20)->default('DESCUENTO');
            $table->decimal('MONTO', 18, 2);
            $table->foreign('ID_DETALLEPLANILLA')->references('ID_DETALLEPLANILLA')->on('DETALLE_PLANILLA')->onDelete('cascade');
            $table->foreign('ID_TIPODESCUENTO')->references('ID_TIPODESCUENTO')->on('TIPO_DESCUENTO')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DETALLE_DESCUENTO_PLANILLA');
    }
};
