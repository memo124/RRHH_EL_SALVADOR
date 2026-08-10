<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ETAPA_RECLUTAMIENTO', function (Blueprint $table) {
            $table->integer('ID_ETAPA')->primary();
            $table->string('NOMBRE', 100);
            $table->integer('ORDEN')->default(1);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('VACANTE', function (Blueprint $table) {
            $table->integer('ID_VACANTE')->primary();
            $table->integer('ID_EMPRESA')->nullable();
            $table->integer('ID_DEPARTAMENTO')->nullable();
            $table->integer('ID_CARGO')->nullable();
            $table->string('TITULO', 250);
            $table->text('DESCRIPCION')->nullable();
            $table->text('REQUISITOS')->nullable();
            $table->string('ESTADO', 20)->default('abierta');
            $table->timestamp('FECHA_APERTURA')->useCurrent();
            $table->timestamp('FECHA_CIERRE')->nullable();
            $table->integer('PLAZAS')->default(1);
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('set null');
            $table->foreign('ID_DEPARTAMENTO')->references('ID_DEPARTAMENTO')->on('DEPARTAMENTO')->onDelete('set null');
            $table->foreign('ID_CARGO')->references('ID_CARGO')->on('CARGO')->onDelete('set null');
            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('CANDIDATO', function (Blueprint $table) {
            $table->integer('ID_CANDIDATO')->primary();
            $table->integer('ID_VACANTE');
            $table->string('NOMBRES', 150);
            $table->string('APELLIDOS', 150)->nullable();
            $table->string('EMAIL', 150)->nullable();
            $table->string('TELEFONO', 30)->nullable();
            $table->integer('ID_ETAPA_ACTUAL')->nullable();
            $table->string('ESTADO', 20)->default('activo');
            $table->integer('ID_ADJUNTO_CV')->nullable();
            $table->timestamp('FECHA_REGISTRO')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_VACANTE')->references('ID_VACANTE')->on('VACANTE')->onDelete('cascade');
            $table->foreign('ID_ETAPA_ACTUAL')->references('ID_ETAPA')->on('ETAPA_RECLUTAMIENTO')->onDelete('set null');
            $table->foreign('ID_ADJUNTO_CV')->references('ID_ADJUNTO')->on('ADJUNTO')->onDelete('set null');
        });

        Schema::create('CANDIDATO_ENTREVISTA', function (Blueprint $table) {
            $table->integer('ID_ENTREVISTA')->primary();
            $table->integer('ID_CANDIDATO');
            $table->timestamp('FECHA_HORA');
            $table->string('TIPO', 30)->default('presencial');
            $table->integer('ID_EMPLEADO_ENTREVISTADOR')->nullable();
            $table->string('RESULTADO', 20)->default('pendiente');
            $table->text('OBSERVACIONES')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_CANDIDATO')->references('ID_CANDIDATO')->on('CANDIDATO')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO_ENTREVISTADOR')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
        });

        Schema::create('EVALUACION_PERIODO', function (Blueprint $table) {
            $table->integer('ID_PERIODO')->primary();
            $table->string('NOMBRE', 200);
            $table->integer('ANIO');
            $table->timestamp('FECHA_INICIO')->nullable();
            $table->timestamp('FECHA_FIN')->nullable();
            $table->string('ESTADO', 20)->default('borrador');
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('EVALUACION_DESEMPENO', function (Blueprint $table) {
            $table->integer('ID_EVALUACION')->primary();
            $table->integer('ID_PERIODO');
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_EVALUADOR');
            $table->string('ESTADO', 20)->default('pendiente');
            $table->decimal('PUNTUACION_GLOBAL', 5, 2)->nullable();
            $table->text('COMENTARIOS_EVALUADOR')->nullable();
            $table->timestamp('FECHA_COMPLETADA')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_PERIODO')->references('ID_PERIODO')->on('EVALUACION_PERIODO')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_EVALUADOR')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('EVALUACION_META', function (Blueprint $table) {
            $table->integer('ID_META')->primary();
            $table->integer('ID_EVALUACION');
            $table->string('DESCRIPCION', 500);
            $table->decimal('PESO', 5, 2)->default(1);
            $table->decimal('VALOR_OBJETIVO', 10, 2)->nullable();
            $table->decimal('VALOR_ALCANZADO', 10, 2)->nullable();
            $table->decimal('PORCENTAJE_CUMPLIMIENTO', 5, 2)->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EVALUACION')->references('ID_EVALUACION')->on('EVALUACION_DESEMPENO')->onDelete('cascade');
        });

        $etapas = [
            [1, 'Recepción de CV', 1],
            [2, 'Entrevista RRHH', 2],
            [3, 'Entrevista técnica', 3],
            [4, 'Oferta laboral', 4],
            [5, 'Contratado', 5],
        ];
        foreach ($etapas as $e) {
            DB::table('ETAPA_RECLUTAMIENTO')->insert([
                'ID_ETAPA' => $e[0], 'NOMBRE' => $e[1], 'ORDEN' => $e[2], 'ESACTIVO' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('EVALUACION_META');
        Schema::dropIfExists('EVALUACION_DESEMPENO');
        Schema::dropIfExists('EVALUACION_PERIODO');
        Schema::dropIfExists('CANDIDATO_ENTREVISTA');
        Schema::dropIfExists('CANDIDATO');
        Schema::dropIfExists('VACANTE');
        Schema::dropIfExists('ETAPA_RECLUTAMIENTO');
    }
};
