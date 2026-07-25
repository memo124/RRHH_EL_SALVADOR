<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DESCUENTO_EMPLEADO', function (Blueprint $table) {
            $table->integer('ID_DESCUENTOEMPLEADO')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_TIPODESCUENTO');
            $table->decimal('MONTO', 18, 2)->default(0.00);
            $table->decimal('PORCENTAJE', 5, 2)->nullable();
            $table->boolean('ES_PORCENTAJE')->default(false);
            $table->timestamp('FECHAINICIO');
            $table->timestamp('FECHAFIN')->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->boolean('ES_RECURRENTE')->default(true);
            $table->string('OBSERVACIONES', 250)->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_TIPODESCUENTO')->references('ID_TIPODESCUENTO')->on('TIPO_DESCUENTO')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DESCUENTO_EMPLEADO');
    }
};
