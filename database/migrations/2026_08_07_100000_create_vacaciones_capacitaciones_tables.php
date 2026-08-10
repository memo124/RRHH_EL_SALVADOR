<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TIPO_PERMISO_LABORAL', function (Blueprint $table) {
            $table->integer('ID_TIPO_PERMISO')->primary();
            $table->string('CODIGO', 30)->unique();
            $table->string('NOMBRE', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->boolean('DESCUENTA_SALDO_VACACIONES')->default(false);
            $table->boolean('REQUIERE_APROBACION')->default(true);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('EMPLEADO_SALDO_VACACIONES', function (Blueprint $table) {
            $table->integer('ID_SALDO')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ANIO');
            $table->decimal('DIAS_ASIGNADOS', 5, 1)->default(15);
            $table->decimal('DIAS_USADOS', 5, 1)->default(0);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->unique(['ID_EMPLEADO', 'ANIO']);
        });

        Schema::create('SOLICITUD_PERMISO', function (Blueprint $table) {
            $table->integer('ID_SOLICITUD')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_TIPO_PERMISO');
            $table->date('FECHA_INICIO');
            $table->date('FECHA_FIN');
            $table->decimal('DIAS_SOLICITADOS', 5, 1);
            $table->text('MOTIVO')->nullable();
            $table->string('ESTADO', 20)->default('pendiente');
            $table->integer('ID_USUARIO_SOLICITA')->nullable();
            $table->integer('ID_USUARIO_APRUEBA')->nullable();
            $table->timestamp('FECHA_SOLICITUD')->useCurrent();
            $table->timestamp('FECHA_REVISION')->nullable();
            $table->text('MOTIVO_RECHAZO')->nullable();
            $table->boolean('INTEGRADO_PLANILLA')->default(false);
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_TIPO_PERMISO')->references('ID_TIPO_PERMISO')->on('TIPO_PERMISO_LABORAL')->onDelete('cascade');
            $table->foreign('ID_USUARIO_SOLICITA')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
            $table->foreign('ID_USUARIO_APRUEBA')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('CAPACITACION', function (Blueprint $table) {
            $table->integer('ID_CAPACITACION')->primary();
            $table->string('TITULO', 250);
            $table->text('DESCRIPCION')->nullable();
            $table->string('MODALIDAD', 30)->default('presencial');
            $table->timestamp('FECHA_INICIO')->nullable();
            $table->timestamp('FECHA_FIN')->nullable();
            $table->integer('CUPO_MAX')->nullable();
            $table->integer('ID_EMPRESA')->nullable();
            $table->string('LUGAR', 250)->nullable();
            $table->string('ESTADO', 20)->default('borrador');
            $table->integer('ID_USUARIO_CREACION')->nullable();
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('set null');
            $table->foreign('ID_USUARIO_CREACION')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
        });

        Schema::create('CAPACITACION_INSCRIPCION', function (Blueprint $table) {
            $table->integer('ID_INSCRIPCION')->primary();
            $table->integer('ID_CAPACITACION');
            $table->integer('ID_EMPLEADO');
            $table->string('ESTADO', 20)->default('inscrito');
            $table->timestamp('FECHA_INSCRIPCION')->useCurrent();
            $table->decimal('CALIFICACION', 5, 2)->nullable();
            $table->integer('ID_ADJUNTO_CERTIFICADO')->nullable();
            $table->boolean('ESACTIVO')->default(true);

            $table->foreign('ID_CAPACITACION')->references('ID_CAPACITACION')->on('CAPACITACION')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_ADJUNTO_CERTIFICADO')->references('ID_ADJUNTO')->on('ADJUNTO')->onDelete('set null');
            $table->unique(['ID_CAPACITACION', 'ID_EMPLEADO']);
        });

        Schema::create('CAPACITACION_ASISTENCIA', function (Blueprint $table) {
            $table->integer('ID_ASISTENCIA')->primary();
            $table->integer('ID_INSCRIPCION');
            $table->date('FECHA');
            $table->boolean('ASISTIO')->default(false);
            $table->string('OBSERVACIONES', 250)->nullable();

            $table->foreign('ID_INSCRIPCION')->references('ID_INSCRIPCION')->on('CAPACITACION_INSCRIPCION')->onDelete('cascade');
            $table->unique(['ID_INSCRIPCION', 'FECHA']);
        });

        $tipos = [
            [1, 'VACACIONES', 'Vacaciones', 'Vacaciones anuales remuneradas', true],
            [2, 'PERMISO_PERSONAL', 'Permiso personal', 'Permiso sin goce o con goce según política', false],
            [3, 'PERMISO_MEDICO', 'Permiso médico', 'Permiso por cita o tratamiento médico', false],
            [4, 'DUELo', 'Duelo', 'Permiso por fallecimiento de familiar', false],
            [5, 'MATERNIDAD_PATERNIDAD', 'Maternidad / Paternidad', 'Licencia por maternidad o paternidad', false],
        ];
        foreach ($tipos as $t) {
            DB::table('TIPO_PERMISO_LABORAL')->insert([
                'ID_TIPO_PERMISO' => $t[0],
                'CODIGO' => $t[1],
                'NOMBRE' => $t[2],
                'DESCRIPCION' => $t[3],
                'DESCUENTA_SALDO_VACACIONES' => $t[4],
                'REQUIERE_APROBACION' => true,
                'ESACTIVO' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('CAPACITACION_ASISTENCIA');
        Schema::dropIfExists('CAPACITACION_INSCRIPCION');
        Schema::dropIfExists('CAPACITACION');
        Schema::dropIfExists('SOLICITUD_PERMISO');
        Schema::dropIfExists('EMPLEADO_SALDO_VACACIONES');
        Schema::dropIfExists('TIPO_PERMISO_LABORAL');
    }
};
