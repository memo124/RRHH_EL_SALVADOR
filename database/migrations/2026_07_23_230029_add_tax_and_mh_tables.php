<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Nuevas tablas de catálogos y MH
        Schema::create('TIPO_DOCUMENTO_IDENTIDAD', function (Blueprint $table) {
            $table->integer('ID_TIPODOCUMENTO')->primary();
            $table->string('CODIGO_MH', 10);
            $table->string('NOMBREDOCUMENTO', 100);
            $table->string('MASCARA_FORMATO', 50)->nullable();
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('ACTIVIDAD_ECONOMICA', function (Blueprint $table) {
            $table->integer('ID_ACTIVIDAD_ECONOMICA')->primary();
            $table->string('CODIGO_MH', 10);
            $table->string('DESCRIPCION', 500);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('ESTABLECIMIENTO', function (Blueprint $table) {
            $table->integer('ID_ESTABLECIMIENTO')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_DISTRITO')->nullable();
            $table->string('CODIGO_MH_TIPO', 10)->default('01');
            $table->string('CODIGO_PUNTO_VENTA_MH', 10)->nullable();
            $table->string('NOMBRE_ESTABLECIMIENTO', 150);
            $table->string('DIRECCION', 250)->nullable();
            $table->string('TELEFONO', 25)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_DISTRITO')->references('ID_DISTRITO')->on('DISTRITO')->onDelete('set null');
        });

        // 2. Modificaciones incrementales en tablas existentes
        // Agregar columnas faltantes de geografía y MH a catálogos existentes
        Schema::table('PAIS', function (Blueprint $table) {
            if (!Schema::hasColumn('PAIS', 'CODIGO_MH')) {
                $table->string('CODIGO_MH', 10)->default('SV');
            }
        });

        Schema::table('DEPARTAMENTO_PAIS', function (Blueprint $table) {
            if (!Schema::hasColumn('DEPARTAMENTO_PAIS', 'CODIGO_MH')) {
                $table->string('CODIGO_MH', 10)->default('01');
            }
        });

        Schema::table('MUNICIPIO', function (Blueprint $table) {
            if (!Schema::hasColumn('MUNICIPIO', 'CODIGO_MH')) {
                $table->string('CODIGO_MH', 10)->default('01');
            }
        });

        Schema::table('DISTRITO', function (Blueprint $table) {
            if (!Schema::hasColumn('DISTRITO', 'CODIGO_MH')) {
                $table->string('CODIGO_MH', 10)->default('01');
            }
        });

        Schema::table('EMPRESA', function (Blueprint $table) {
            if (!Schema::hasColumn('EMPRESA', 'ID_ACTIVIDAD_ECONOMICA')) {
                $table->integer('ID_ACTIVIDAD_ECONOMICA')->nullable();
                $table->foreign('ID_ACTIVIDAD_ECONOMICA')->references('ID_ACTIVIDAD_ECONOMICA')->on('ACTIVIDAD_ECONOMICA')->onDelete('set null');
            }
            if (!Schema::hasColumn('EMPRESA', 'TIPO_PERSONA')) {
                $table->char('TIPO_PERSONA', 1)->default('J');
            }
            if (!Schema::hasColumn('EMPRESA', 'RAZON_SOCIAL')) {
                $table->string('RAZON_SOCIAL', 200)->nullable();
            }
        });

        Schema::table('EMPLEADO', function (Blueprint $table) {
            // Reemplazar la FK ID_SUCURSAL anterior por ID_ESTABLECIMIENTO
            if (Schema::hasColumn('EMPLEADO', 'ID_SUCURSAL')) {
                $table->dropColumn('ID_SUCURSAL');
            }
            if (!Schema::hasColumn('EMPLEADO', 'ID_ESTABLECIMIENTO')) {
                $table->integer('ID_ESTABLECIMIENTO')->nullable();
                $table->foreign('ID_ESTABLECIMIENTO')->references('ID_ESTABLECIMIENTO')->on('ESTABLECIMIENTO')->onDelete('set null');
            }
            if (!Schema::hasColumn('EMPLEADO', 'ID_ACTIVIDAD_ECONOMICA')) {
                $table->integer('ID_ACTIVIDAD_ECONOMICA')->nullable();
                $table->foreign('ID_ACTIVIDAD_ECONOMICA')->references('ID_ACTIVIDAD_ECONOMICA')->on('ACTIVIDAD_ECONOMICA')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('EMPLEADO', function (Blueprint $table) {
            $table->dropColumn(['ID_ESTABLECIMIENTO', 'ID_ACTIVIDAD_ECONOMICA']);
        });
        Schema::dropIfExists('ESTABLECIMIENTO');
        Schema::dropIfExists('ACTIVIDAD_ECONOMICA');
        Schema::dropIfExists('TIPO_DOCUMENTO_IDENTIDAD');
    }
};
