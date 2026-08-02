<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('EMPRESA', function (Blueprint $table) {
            $table->string('NOMBRE_DUENO', 200)->nullable()->after('RAZON_SOCIAL');
            $table->string('DUI_DUENO', 12)->nullable()->after('NOMBRE_DUENO');
        });

        Schema::create('EMPRESA_FIRMANTE', function (Blueprint $table) {
            $table->integer('ID_FIRMANTE')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('NOMBRE', 200);
            $table->string('CARGO', 150)->nullable();
            $table->string('DUI', 12)->nullable();
            $table->unsignedTinyInteger('ORDEN')->default(1);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA');
        });

        Schema::create('PLANTILLA_CONTRATO', function (Blueprint $table) {
            $table->integer('ID_PLANTILLA')->primary();
            $table->integer('ID_EMPRESA')->nullable();
            $table->string('NOMBRE', 150);
            $table->string('DESCRIPCION', 500)->nullable();
            $table->longText('CONTENIDO');
            $table->longText('CLAUSULAS')->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->dateTime('FECHA_CREACION')->nullable();

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA');
        });

        Schema::create('CONTRATO', function (Blueprint $table) {
            $table->integer('ID_CONTRATO')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_EMPRESA');
            $table->integer('ID_PLANTILLA')->nullable();
            $table->string('NUMERO_CONTRATO', 50)->nullable();
            $table->date('FECHA_INICIO')->nullable();
            $table->date('FECHA_FIN')->nullable();
            $table->boolean('SIN_FECHA_DEFINIDA')->default(false);
            $table->decimal('SALARIO', 18, 2)->nullable();
            $table->longText('CONTENIDO_GENERADO')->nullable();
            $table->json('CAMPOS_EXTRA')->nullable();
            $table->string('ESTADO', 20)->default('VIGENTE');
            $table->text('OBSERVACIONES')->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->dateTime('FECHA_CREACION')->nullable();

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO');
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA');
            $table->foreign('ID_PLANTILLA')->references('ID_PLANTILLA')->on('PLANTILLA_CONTRATO');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CONTRATO');
        Schema::dropIfExists('PLANTILLA_CONTRATO');
        Schema::dropIfExists('EMPRESA_FIRMANTE');

        Schema::table('EMPRESA', function (Blueprint $table) {
            $table->dropColumn(['NOMBRE_DUENO', 'DUI_DUENO']);
        });
    }
};
