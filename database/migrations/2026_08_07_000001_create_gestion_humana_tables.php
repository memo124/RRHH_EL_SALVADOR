<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TIPO_DOCUMENTO_ADJUNTO', function (Blueprint $table) {
            $table->integer('ID_TIPO_DOCUMENTO_ADJUNTO')->primary();
            $table->string('CODIGO', 50)->unique();
            $table->string('NOMBRE', 150);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('ADJUNTO', function (Blueprint $table) {
            $table->integer('ID_ADJUNTO')->primary();
            $table->integer('ID_EMPLEADO')->nullable();
            $table->integer('ID_TIPO_DOCUMENTO_ADJUNTO')->nullable();
            $table->string('NOMBRE_ARCHIVO', 255);
            $table->string('RUTA_STORAGE', 500);
            $table->string('MIME_TYPE', 100)->nullable();
            $table->bigInteger('TAMANO_BYTES')->default(0);
            $table->timestamp('FECHA_SUBIDA')->useCurrent();
            $table->integer('ID_USUARIO_SUBIDA')->nullable();
            $table->string('ORIGEN', 30)->default('manual');
            $table->integer('ID_ORIGEN')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
            $table->foreign('ID_TIPO_DOCUMENTO_ADJUNTO')->references('ID_TIPO_DOCUMENTO_ADJUNTO')->on('TIPO_DOCUMENTO_ADJUNTO')->onDelete('set null');
            $table->foreign('ID_USUARIO_SUBIDA')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('EMPLEADO_EDUCACION', function (Blueprint $table) {
            $table->integer('ID_EMPLEADO_EDUCACION')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_EDUCACIONACADEMICA')->nullable();
            $table->string('TITULO_OBTENIDO', 250)->nullable();
            $table->string('INSTITUCION', 250)->nullable();
            $table->date('FECHA_GRADUACION')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_EDUCACIONACADEMICA')->references('ID_EDUCACIONACADEMICA')->on('EDUCACION_ACADEMICA')->onDelete('set null');
        });

        Schema::create('EMPLEADO_CERTIFICACION', function (Blueprint $table) {
            $table->integer('ID_CERTIFICACION')->primary();
            $table->integer('ID_EMPLEADO');
            $table->string('NOMBRE', 250);
            $table->string('INSTITUCION', 250)->nullable();
            $table->date('FECHA_EMISION')->nullable();
            $table->date('FECHA_VENCIMIENTO')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('EMPLEADO_DEPENDIENTE', function (Blueprint $table) {
            $table->integer('ID_DEPENDIENTE')->primary();
            $table->integer('ID_EMPLEADO');
            $table->string('NOMBRES', 150);
            $table->string('APELLIDOS', 150)->nullable();
            $table->string('PARENTESCO', 50);
            $table->date('FECHA_NACIMIENTO')->nullable();
            $table->string('DOCUMENTO_IDENTIDAD', 30)->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('ENCUESTA', function (Blueprint $table) {
            $table->integer('ID_ENCUESTA')->primary();
            $table->string('TITULO', 250);
            $table->text('DESCRIPCION')->nullable();
            $table->string('ESTADO', 20)->default('borrador');
            $table->timestamp('FECHA_INICIO')->nullable();
            $table->timestamp('FECHA_FIN')->nullable();
            $table->boolean('ANONIMA')->default(false);
            $table->boolean('ENVIAR_RECORDATORIOS')->default(false);
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('ENCUESTA_PREGUNTA', function (Blueprint $table) {
            $table->integer('ID_PREGUNTA')->primary();
            $table->integer('ID_ENCUESTA');
            $table->integer('ORDEN')->default(1);
            $table->string('TIPO', 30);
            $table->text('ENUNCIADO');
            $table->json('OPCIONES')->nullable();
            $table->boolean('REQUERIDA')->default(true);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_ENCUESTA')->references('ID_ENCUESTA')->on('ENCUESTA')->onDelete('cascade');
        });

        Schema::create('ENCUESTA_ASIGNACION', function (Blueprint $table) {
            $table->integer('ID_ASIGNACION')->primary();
            $table->integer('ID_ENCUESTA');
            $table->string('TIPO_AUDIENCIA', 30);
            $table->integer('ID_REFERENCIA')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_ENCUESTA')->references('ID_ENCUESTA')->on('ENCUESTA')->onDelete('cascade');
        });

        Schema::create('ENCUESTA_RESPUESTA', function (Blueprint $table) {
            $table->integer('ID_RESPUESTA')->primary();
            $table->integer('ID_ENCUESTA');
            $table->integer('ID_EMPLEADO')->nullable();
            $table->timestamp('FECHA_RESPUESTA')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_ENCUESTA')->references('ID_ENCUESTA')->on('ENCUESTA')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
        });

        Schema::create('ENCUESTA_RESPUESTA_DETALLE', function (Blueprint $table) {
            $table->integer('ID_DETALLE')->primary();
            $table->integer('ID_RESPUESTA');
            $table->integer('ID_PREGUNTA');
            $table->text('VALOR_TEXTO')->nullable();
            $table->string('VALOR_OPCION', 250)->nullable();
            $table->integer('ID_ADJUNTO')->nullable();

            $table->foreign('ID_RESPUESTA')->references('ID_RESPUESTA')->on('ENCUESTA_RESPUESTA')->onDelete('cascade');
            $table->foreign('ID_PREGUNTA')->references('ID_PREGUNTA')->on('ENCUESTA_PREGUNTA')->onDelete('cascade');
            $table->foreign('ID_ADJUNTO')->references('ID_ADJUNTO')->on('ADJUNTO')->onDelete('set null');
        });

        Schema::create('FORMULARIO_PLANTILLA', function (Blueprint $table) {
            $table->integer('ID_PLANTILLA')->primary();
            $table->string('NOMBRE', 200);
            $table->text('DESCRIPCION')->nullable();
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('FORMULARIO_CAMPO', function (Blueprint $table) {
            $table->integer('ID_CAMPO')->primary();
            $table->integer('ID_PLANTILLA');
            $table->integer('ORDEN')->default(1);
            $table->string('ETIQUETA', 200);
            $table->string('TIPO_CAMPO', 30);
            $table->string('MAPEO_TABLA', 50)->nullable();
            $table->string('MAPEO_COLUMNA', 100)->nullable();
            $table->json('OPCIONES')->nullable();
            $table->boolean('REQUERIDO')->default(false);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_PLANTILLA')->references('ID_PLANTILLA')->on('FORMULARIO_PLANTILLA')->onDelete('cascade');
        });

        Schema::create('FORMULARIO_CAMPANA', function (Blueprint $table) {
            $table->integer('ID_CAMPANA')->primary();
            $table->integer('ID_PLANTILLA');
            $table->string('NOMBRE', 200);
            $table->text('DESCRIPCION')->nullable();
            $table->timestamp('FECHA_INICIO')->nullable();
            $table->timestamp('FECHA_FIN')->nullable();
            $table->string('ESTADO', 20)->default('borrador');
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_PLANTILLA')->references('ID_PLANTILLA')->on('FORMULARIO_PLANTILLA')->onDelete('cascade');
            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('FORMULARIO_INVITACION', function (Blueprint $table) {
            $table->integer('ID_INVITACION')->primary();
            $table->integer('ID_CAMPANA');
            $table->integer('ID_EMPLEADO');
            $table->string('TOKEN', 64)->unique();
            $table->timestamp('FECHA_EXPIRACION')->nullable();
            $table->string('ESTADO', 20)->default('pendiente');
            $table->timestamp('FECHA_COMPLETADA')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_CAMPANA')->references('ID_CAMPANA')->on('FORMULARIO_CAMPANA')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('FORMULARIO_RESPUESTA', function (Blueprint $table) {
            $table->integer('ID_RESPUESTA')->primary();
            $table->integer('ID_CAMPANA');
            $table->integer('ID_INVITACION');
            $table->integer('ID_EMPLEADO');
            $table->string('ESTADO', 30)->default('pendiente_aprobacion');
            $table->timestamp('FECHA_ENVIO')->useCurrent();
            $table->timestamp('FECHA_REVISION')->nullable();
            $table->integer('ID_USUARIO_REVISION')->nullable();
            $table->text('MOTIVO_RECHAZO')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_CAMPANA')->references('ID_CAMPANA')->on('FORMULARIO_CAMPANA')->onDelete('cascade');
            $table->foreign('ID_INVITACION')->references('ID_INVITACION')->on('FORMULARIO_INVITACION')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_USUARIO_REVISION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('FORMULARIO_RESPUESTA_CAMPO', function (Blueprint $table) {
            $table->integer('ID_RESPUESTA_CAMPO')->primary();
            $table->integer('ID_RESPUESTA');
            $table->integer('ID_CAMPO');
            $table->text('VALOR_TEXTO')->nullable();
            $table->json('VALOR_JSON')->nullable();
            $table->integer('ID_ADJUNTO')->nullable();

            $table->foreign('ID_RESPUESTA')->references('ID_RESPUESTA')->on('FORMULARIO_RESPUESTA')->onDelete('cascade');
            $table->foreign('ID_CAMPO')->references('ID_CAMPO')->on('FORMULARIO_CAMPO')->onDelete('cascade');
            $table->foreign('ID_ADJUNTO')->references('ID_ADJUNTO')->on('ADJUNTO')->onDelete('set null');
        });

        Schema::create('CALENDARIO_EVENTO', function (Blueprint $table) {
            $table->integer('ID_EVENTO')->primary();
            $table->string('TIPO', 40);
            $table->string('TITULO', 250);
            $table->text('DESCRIPCION')->nullable();
            $table->timestamp('FECHA_INICIO');
            $table->timestamp('FECHA_FIN')->nullable();
            $table->boolean('TODO_DIA')->default(false);
            $table->string('COLOR', 20)->nullable();
            $table->integer('ID_EMPLEADO')->nullable();
            $table->integer('ID_EMPRESA')->nullable();
            $table->integer('ID_DEPARTAMENTO')->nullable();
            $table->string('ORIGEN_TIPO', 40)->nullable();
            $table->integer('ORIGEN_ID')->nullable();
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('set null');
            $table->foreign('ID_DEPARTAMENTO')->references('ID_DEPARTAMENTO')->on('DEPARTAMENTO')->onDelete('set null');
            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        $tipos = [
            [1, 'TITULO_UNIVERSITARIO', 'Título universitario', 'Diploma o título de grado'],
            [2, 'CERTIFICADO', 'Certificado profesional', 'Certificación o curso'],
            [3, 'DUI', 'DUI', 'Documento único de identidad'],
            [4, 'NIT', 'NIT', 'Número de identificación tributaria'],
            [5, 'PARTIDA_NACIMIENTO', 'Partida de nacimiento', 'Partida de nacimiento de dependiente'],
            [6, 'OTRO', 'Otro documento', 'Documento general'],
        ];
        foreach ($tipos as $t) {
            DB::table('TIPO_DOCUMENTO_ADJUNTO')->insert([
                'ID_TIPO_DOCUMENTO_ADJUNTO' => $t[0],
                'CODIGO' => $t[1],
                'NOMBRE' => $t[2],
                'DESCRIPCION' => $t[3],
                'ESACTIVO' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('CALENDARIO_EVENTO');
        Schema::dropIfExists('FORMULARIO_RESPUESTA_CAMPO');
        Schema::dropIfExists('FORMULARIO_RESPUESTA');
        Schema::dropIfExists('FORMULARIO_INVITACION');
        Schema::dropIfExists('FORMULARIO_CAMPANA');
        Schema::dropIfExists('FORMULARIO_CAMPO');
        Schema::dropIfExists('FORMULARIO_PLANTILLA');
        Schema::dropIfExists('ENCUESTA_RESPUESTA_DETALLE');
        Schema::dropIfExists('ENCUESTA_RESPUESTA');
        Schema::dropIfExists('ENCUESTA_ASIGNACION');
        Schema::dropIfExists('ENCUESTA_PREGUNTA');
        Schema::dropIfExists('ENCUESTA');
        Schema::dropIfExists('EMPLEADO_DEPENDIENTE');
        Schema::dropIfExists('EMPLEADO_CERTIFICACION');
        Schema::dropIfExists('EMPLEADO_EDUCACION');
        Schema::dropIfExists('ADJUNTO');
        Schema::dropIfExists('TIPO_DOCUMENTO_ADJUNTO');
    }
};
