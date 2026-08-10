<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TIPO: gasto|pasivo|banco|isss|afp|renta|otros
        Schema::create('CUENTA_CONTABLE_EMPRESA', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('CODIGO', 30);
            $table->string('NOMBRE', 200);
            $table->string('TIPO', 20);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('ASIENTO_CONTABLE', function (Blueprint $table) {
            $table->integer('ID_ASIENTO')->primary();
            $table->integer('ID_PLANILLA');
            $table->integer('ID_EMPRESA');
            $table->date('FECHA');
            $table->string('CONCEPTO', 500);
            $table->decimal('TOTAL_DEBE', 18, 2)->default(0);
            $table->decimal('TOTAL_HABER', 18, 2)->default(0);
            $table->integer('ID_USUARIO')->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('cascade');
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('DETALLE_ASIENTO', function (Blueprint $table) {
            $table->integer('ID_DETALLE')->primary();
            $table->integer('ID_ASIENTO');
            $table->string('CUENTA', 30);
            $table->string('DESCRIPCION', 250);
            $table->decimal('DEBE', 18, 2)->default(0);
            $table->decimal('HABER', 18, 2)->default(0);
            $table->integer('ORDEN')->default(1);

            $table->foreign('ID_ASIENTO')->references('ID_ASIENTO')->on('ASIENTO_CONTABLE')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DETALLE_ASIENTO');
        Schema::dropIfExists('ASIENTO_CONTABLE');
        Schema::dropIfExists('CUENTA_CONTABLE_EMPRESA');
    }
};
