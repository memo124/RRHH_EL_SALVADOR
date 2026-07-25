<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Geografía e Idioma
        Schema::create('PAIS', function (Blueprint $table) {
            $table->integer('ID_PAIS')->primary();
            $table->string('NOMBREPAIS', 100);
        });

        Schema::create('DEPARTAMENTO_PAIS', function (Blueprint $table) {
            $table->integer('ID_DEPARTAMENTOPAIS')->primary();
            $table->integer('ID_PAIS');
            $table->string('NOMBREDEPARTAMENTO', 100);
            $table->foreign('ID_PAIS')->references('ID_PAIS')->on('PAIS')->onDelete('cascade');
        });

        Schema::create('MUNICIPIO', function (Blueprint $table) {
            $table->integer('ID_MUNICIPIO')->primary();
            $table->integer('ID_DEPARTAMENTOPAIS');
            $table->string('NOMBREMUNICIPIO', 100);
            $table->foreign('ID_DEPARTAMENTOPAIS')->references('ID_DEPARTAMENTOPAIS')->on('DEPARTAMENTO_PAIS')->onDelete('cascade');
        });

        Schema::create('DISTRITO', function (Blueprint $table) {
            $table->integer('ID_DISTRITO')->primary();
            $table->integer('ID_MUNICIPIO');
            $table->string('NOMBREDISTRITO', 100);
            $table->foreign('ID_MUNICIPIO')->references('ID_MUNICIPIO')->on('MUNICIPIO')->onDelete('cascade');
        });

        // 2. Estructura Empresarial y Organizativa
        Schema::create('EMPRESA', function (Blueprint $table) {
            $table->integer('ID_EMPRESA')->primary();
            $table->integer('ID_DISTRITO')->nullable();
            $table->string('NOMBREEMPRESA', 150);
            $table->string('ABREVIATURA', 20)->nullable();
            $table->string('NUMEROREGISTRO', 50)->nullable();
            $table->string('NUMERONIT', 20)->nullable();
            $table->string('GIRO', 500)->nullable();
            $table->boolean('EMPRESAACTIVA')->default(true);
            $table->string('DIRECCION', 500)->nullable();
            $table->string('URL_LOGO', 2048)->nullable();
            $table->binary('LOGO')->nullable();
            $table->string('NUMEROPATRONALISSS', 60)->nullable();
            $table->string('CENTROTRABAJO', 50)->nullable();
            $table->decimal('HORASLABORALES', 5, 2)->default(8.00);
            $table->string('ISSSCODIGOS', 50)->nullable();
            $table->string('TELEFONO', 25)->nullable();
            $table->foreign('ID_DISTRITO')->references('ID_DISTRITO')->on('DISTRITO')->onDelete('set null');
        });

        Schema::create('CENTRO_COSTO', function (Blueprint $table) {
            $table->integer('ID_CENTROCOSTO')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('CODIGO_CENTROCOSTO', 50);
            $table->string('NOMBRE_CENTROCOSTO', 200);
            $table->string('DESCRIPCION', 500)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('AREA', function (Blueprint $table) {
            $table->integer('ID_AREA')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('NOMBREAREA', 200);
            $table->boolean('ACTIVA')->default(true);
            $table->boolean('PRORRATEADA')->default(false);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('DEPARTAMENTO', function (Blueprint $table) {
            $table->integer('ID_DEPARTAMENTO')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_AREA');
            $table->integer('ID_CENTROCOSTO')->nullable();
            $table->string('NOMBREDEPARTAMENTO', 150);
            $table->string('DESCRIPCION', 500)->nullable();
            $table->string('CUENTACONTABLE', 50)->nullable();
            $table->boolean('MANO_OBRA_DIRECTA')->default(false);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_AREA')->references('ID_AREA')->on('AREA')->onDelete('cascade');
            $table->foreign('ID_CENTROCOSTO')->references('ID_CENTROCOSTO')->on('CENTRO_COSTO')->onDelete('set null');
        });

        Schema::create('CARGO', function (Blueprint $table) {
            $table->integer('ID_CARGO')->primary();
            $table->integer('ID_DEPARTAMENTO');
            $table->integer('ID_CENTROCOSTO')->nullable();
            $table->integer('ID_CARGO_PADRE')->nullable();
            $table->integer('NIVEL_JERARQUICO')->default(1);
            $table->string('NOMBRECARGO', 150);
            $table->boolean('CARGOESTADO')->default(true);
            $table->foreign('ID_DEPARTAMENTO')->references('ID_DEPARTAMENTO')->on('DEPARTAMENTO')->onDelete('cascade');
            $table->foreign('ID_CENTROCOSTO')->references('ID_CENTROCOSTO')->on('CENTRO_COSTO')->onDelete('set null');
        });

        Schema::create('SUCURSAL', function (Blueprint $table) {
            $table->integer('ID_SUCURSAL')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_DISTRITO')->nullable();
            $table->string('NOMBRESUCURSAL', 100);
            $table->string('DIRECCION', 250)->nullable();
            $table->boolean('ESACTIVA')->default(true);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_DISTRITO')->references('ID_DISTRITO')->on('DISTRITO')->onDelete('set null');
        });

        Schema::create('BODEGA', function (Blueprint $table) {
            $table->integer('ID_BODEGA')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('NOMBREBODEGA', 200);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('RUTA', function (Blueprint $table) {
            $table->integer('ID_RUTA')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_CENTROCOSTO')->nullable();
            $table->string('NOMBRERUTA', 100);
            $table->boolean('ESACTIVA')->default(true);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_CENTROCOSTO')->references('ID_CENTROCOSTO')->on('CENTRO_COSTO')->onDelete('set null');
        });

        // 3. Seguridad (RBAC)
        Schema::create('MODULOS', function (Blueprint $table) {
            $table->integer('ID_MODULO')->primary();
            $table->string('NOMBREMODULO', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->string('RUTA_URL', 250)->nullable();
            $table->string('ICONO', 50)->nullable();
            $table->integer('ORDEN')->default(0)->nullable();
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('PERMISO', function (Blueprint $table) {
            $table->integer('ID_PERMISO')->primary();
            $table->integer('ID_MODULO');
            $table->string('CODIGO_PERMISO', 50);
            $table->string('NOMBRE_PERMISO', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->foreign('ID_MODULO')->references('ID_MODULO')->on('MODULOS')->onDelete('cascade');
        });

        Schema::create('ROL', function (Blueprint $table) {
            $table->integer('ID_ROL')->primary();
            $table->string('NOMBREROL', 50);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->timestamp('FECHACREACION')->useCurrent();
        });

        Schema::create('ROL_PERMISO', function (Blueprint $table) {
            $table->integer('ID_ROL');
            $table->integer('ID_PERMISO');
            $table->primary(['ID_ROL', 'ID_PERMISO']);
            $table->foreign('ID_ROL')->references('ID_ROL')->on('ROL')->onDelete('cascade');
            $table->foreign('ID_PERMISO')->references('ID_PERMISO')->on('PERMISO')->onDelete('cascade');
        });

        // Catálogos de Contratación y Colaboradores
        Schema::create('TIPO_CONTRATACION', function (Blueprint $table) {
            $table->integer('ID_TIPOCONTRATACION')->primary();
            $table->string('TIPOCONTRATACION', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->boolean('ES_EVENTUAL')->default(false);
            $table->boolean('APLICA_ISSS')->default(true);
            $table->boolean('APLICA_AFP')->default(true);
            $table->boolean('APLICA_RENTA_TABLA')->default(true);
            $table->boolean('APLICA_RENTA_FIJA')->default(false);
            $table->decimal('PORCENTAJE_RENTA_FIJA', 5, 2)->default(0.00);
            $table->boolean('APLICA_INSAFORP')->default(true);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('AFP', function (Blueprint $table) {
            $table->integer('ID_AFP')->primary();
            $table->string('NOMBREAFP', 150);
            $table->string('CODIGOPREVISIONAL', 25)->nullable();
            $table->decimal('PORCENTAJEPATRONAL', 5, 2);
            $table->decimal('PORCENTAJEEMPLEADOR', 5, 2);
            $table->decimal('DEVENGADOMAXIMO', 18, 2)->nullable();
            $table->decimal('DEVENGADOMINIMO', 18, 2)->nullable();
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('BANCO', function (Blueprint $table) {
            $table->integer('ID_BANCO')->primary();
            $table->integer('ID_PAIS');
            $table->string('NOMBREBANCO', 100);
            $table->string('ALIAS', 25)->nullable();
            $table->boolean('BANCOACTIVO')->default(true);
            $table->foreign('ID_PAIS')->references('ID_PAIS')->on('PAIS')->onDelete('cascade');
        });

        Schema::create('ESTADO_CIVIL', function (Blueprint $table) {
            $table->integer('ID_ESTADOCIVIL')->primary();
            $table->string('NOMBREESTADOCIVIL', 50);
        });

        Schema::create('EDUCACION_ACADEMICA', function (Blueprint $table) {
            $table->integer('ID_EDUCACIONACADEMICA')->primary();
            $table->string('DESCRIPCION', 150);
            $table->boolean('ACTIVO')->default(true);
        });

        Schema::create('PROFESIONES_OFICIOS', function (Blueprint $table) {
            $table->integer('ID_PROFESIONES_OFICIOS')->primary();
            $table->string('PROFESION_OFICIO', 250);
        });

        Schema::create('PERFIL_PAGO', function (Blueprint $table) {
            $table->integer('ID_PERFILPAGO')->primary();
            $table->string('PEFILPAGO', 100);
            $table->boolean('GRATIFICACIONES')->default(true);
            $table->boolean('EXTRA_GRATIFICACIONES')->default(true);
        });

        Schema::create('HORARIOS', function (Blueprint $table) {
            $table->integer('ID_HORARIO')->primary();
            $table->integer('ID_EMPRESA');
            $table->string('NOMBRE_HORARIO', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->integer('TOLERANCIA_ENTRADA_MINUTOS')->default(10);
            $table->integer('TOLERANCIA_SALIDA_MINUTOS')->default(10);
            $table->boolean('ES_ROTATIVO')->default(false);
            $table->boolean('ESACTIVO')->default(true);
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('HORARIO_DETALLE', function (Blueprint $table) {
            $table->integer('ID_HORARIODETALLE')->primary();
            $table->integer('ID_HORARIO');
            $table->integer('DIA_SEMANA');
            $table->time('HORA_ENTRADA');
            $table->time('HORA_SALIDA');
            $table->time('HORA_INICIO_ALMUERZO')->nullable();
            $table->time('HORA_FIN_ALMUERZO')->nullable();
            $table->integer('TIEMPO_ALMUERZO_MINUTOS')->default(60);
            $table->boolean('ES_DIA_DESCANSO')->default(false);
            $table->foreign('ID_HORARIO')->references('ID_HORARIO')->on('HORARIOS')->onDelete('cascade');
        });

        Schema::create('EMPLEADO', function (Blueprint $table) {
            $table->integer('ID_EMPLEADO')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_DEPARTAMENTO');
            $table->integer('ID_CARGO');
            $table->integer('ID_JEFE_INMEDIATO')->nullable();
            $table->integer('ID_SUCURSAL')->nullable();
            $table->integer('ID_CENTROCOSTO')->nullable();
            $table->integer('ID_TIPOCONTRATACION');
            $table->integer('ID_AFP')->nullable();
            $table->integer('ID_BANCO')->nullable();
            $table->integer('ID_DISTRITO');
            $table->integer('ID_ESTADOCIVIL')->nullable();
            $table->integer('ID_HORARIO')->nullable();
            $table->integer('ID_PERFILPAGO')->default(1)->nullable();
            $table->integer('ID_RUTA')->nullable();
            $table->integer('ID_BODEGA')->nullable();
            
            $table->string('CODIGOEMPLEADO', 50);
            $table->string('NOMBRES', 150);
            $table->string('APELLIDO_1', 100);
            $table->string('APELLIDO_2', 100)->nullable();
            $table->string('DUI', 12)->unique();
            $table->string('NIT', 20)->nullable();
            $table->string('ISSS', 20)->nullable();
            $table->string('NUP', 20)->nullable();
            $table->char('GENERO', 1);
            $table->timestamp('FECHANACIMIENTO');
            $table->timestamp('FECHAINGRESO');
            
            $table->decimal('SALARIOMENSUAL', 18, 2);
            $table->decimal('SALARIODIARIO', 18, 4);
            $table->string('NUMEROCUENTA', 50)->nullable();
            
            $table->boolean('JUBILADO')->default(false);
            $table->boolean('APLICA_ISSS_OVERRIDE')->nullable();
            $table->boolean('APLICA_AFP_OVERRIDE')->nullable();
            $table->boolean('APLICA_RENTA_OVERRIDE')->nullable();
            $table->string('NUMERO_RESOLUCION_EXENCION', 100)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            
            $table->string('CORREOELECTRONICO', 100)->nullable();
            $table->string('CORREOELECTRONICOEMPRESARIAL', 100)->nullable();
            $table->string('TELEFONOCELULAR', 15)->nullable();
            $table->string('DIRECCION', 250)->nullable();
            $table->string('PERSONACONTACTO', 200)->nullable();

            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_DEPARTAMENTO')->references('ID_DEPARTAMENTO')->on('DEPARTAMENTO')->onDelete('cascade');
            $table->foreign('ID_CARGO')->references('ID_CARGO')->on('CARGO')->onDelete('cascade');
            $table->foreign('ID_SUCURSAL')->references('ID_SUCURSAL')->on('SUCURSAL')->onDelete('set null');
            $table->foreign('ID_CENTROCOSTO')->references('ID_CENTROCOSTO')->on('CENTRO_COSTO')->onDelete('set null');
            $table->foreign('ID_TIPOCONTRATACION')->references('ID_TIPOCONTRATACION')->on('TIPO_CONTRATACION')->onDelete('cascade');
            $table->foreign('ID_AFP')->references('ID_AFP')->on('AFP')->onDelete('set null');
            $table->foreign('ID_BANCO')->references('ID_BANCO')->on('BANCO')->onDelete('set null');
            $table->foreign('ID_DISTRITO')->references('ID_DISTRITO')->on('DISTRITO')->onDelete('cascade');
            $table->foreign('ID_ESTADOCIVIL')->references('ID_ESTADOCIVIL')->on('ESTADO_CIVIL')->onDelete('set null');
            $table->foreign('ID_HORARIO')->references('ID_HORARIO')->on('HORARIOS')->onDelete('set null');
            $table->foreign('ID_PERFILPAGO')->references('ID_PERFILPAGO')->on('PERFIL_PAGO')->onDelete('set null');
            $table->foreign('ID_RUTA')->references('ID_RUTA')->on('RUTA')->onDelete('set null');
            $table->foreign('ID_BODEGA')->references('ID_BODEGA')->on('BODEGA')->onDelete('set null');
        });

        // 5. Usuarios y Relaciones de Acceso
        Schema::create('USUARIO', function (Blueprint $table) {
            $table->integer('ID_USUARIO')->primary();
            $table->integer('ID_EMPLEADO')->nullable();
            $table->string('USUARIO', 50)->unique();
            $table->string('CONTRASENA_HASH', 255);
            $table->string('EMAIL', 100)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->boolean('BLOQUEADO')->default(false);
            $table->string('TEMA', 20)->default('auto');
            $table->timestamp('FECHACREACION')->useCurrent();
            $table->timestamp('FECHAULTIMOACCESO')->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
        });

        Schema::create('USUARIO_ROL', function (Blueprint $table) {
            $table->integer('ID_USUARIO');
            $table->integer('ID_ROL');
            $table->primary(['ID_USUARIO', 'ID_ROL']);
            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('cascade');
            $table->foreign('ID_ROL')->references('ID_ROL')->on('ROL')->onDelete('cascade');
        });

        Schema::create('USUARIO_PERMISO', function (Blueprint $table) {
            $table->integer('ID_USUARIO');
            $table->integer('ID_PERMISO');
            $table->boolean('ES_CONCEDIDO')->default(true);
            $table->timestamp('FECHA_ASIGNACION')->useCurrent();
            $table->string('USUARIO_ASIGNO', 100)->nullable();
            $table->primary(['ID_USUARIO', 'ID_PERMISO']);
            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('cascade');
            $table->foreign('ID_PERMISO')->references('ID_PERMISO')->on('PERMISO')->onDelete('cascade');
        });

        // 6. Horarios, Asistencias y Marcaciones
        Schema::create('ASIGNACION_HORARIO_EMPLEADO', function (Blueprint $table) {
            $table->integer('ID_ASIGNACION')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_HORARIO');
            $table->date('FECHA_INICIO');
            $table->date('FECHA_FIN')->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_HORARIO')->references('ID_HORARIO')->on('HORARIOS')->onDelete('cascade');
        });

        Schema::create('MARCACION_RAW', function (Blueprint $table) {
            $table->bigIncrements('ID_MARCACION');
            $table->integer('ID_EMPLEADO');
            $table->string('CODIGO_RELOJ', 50)->nullable();
            $table->timestamp('FECHA_HORA_MARCACION');
            $table->string('TIPO_MARCACION', 20);
            $table->string('ORIGEN', 20)->default('BIOMETRICO');
            $table->boolean('PROCESADO')->default(false);
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('ASISTENCIA_DIARIA', function (Blueprint $table) {
            $table->bigIncrements('ID_ASISTENCIA');
            $table->integer('ID_EMPLEADO');
            $table->date('FECHA');
            $table->integer('ID_HORARIO')->nullable();
            $table->time('HORA_ENTRADA_PROGRAMADA')->nullable();
            $table->time('HORA_SALIDA_PROGRAMADA')->nullable();
            $table->timestamp('HORA_ENTRADA_REAL')->nullable();
            $table->timestamp('HORA_SALIDA_REAL')->nullable();
            $table->integer('MINUTOS_LLEGADA_TARDE')->default(0);
            $table->integer('MINUTOS_SALIDA_TEMPRANO')->default(0);
            $table->decimal('HORAS_TRABAJADAS', 5, 2)->default(0.00);
            $table->decimal('HORAS_EXTRAS_DIURNAS', 5, 2)->default(0.00);
            $table->decimal('HORAS_EXTRAS_NOCTURNAS', 5, 2)->default(0.00);
            $table->boolean('ES_INASISTENCIA')->default(false);
            $table->boolean('ES_INCAPACIDAD')->default(false);
            $table->boolean('ES_PERMISO')->default(false);
            $table->string('OBSERVACIONES', 250)->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_HORARIO')->references('ID_HORARIO')->on('HORARIOS')->onDelete('set null');
        });

        // 7. Incapacidades y Subsidios ISSS
        Schema::create('TIPO_INCAPACIDAD', function (Blueprint $table) {
            $table->integer('ID_TIPOINCAPACIDAD')->primary();
            $table->string('NOMBRE_TIPO', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->decimal('PORCENTAJE_SUBSIDIO_ISSS', 5, 2)->default(75.00);
            $table->decimal('PORCENTAJE_PAGO_PATRONO', 5, 2)->default(100.00);
            $table->integer('DIAS_INICIO_SUBSIDIO_ISSS')->default(4);
            $table->integer('DIAS_MAXIMOS_COBERTURA_PATRONO')->default(3);
            $table->boolean('ES_MATERNIDAD')->default(false);
            $table->boolean('ES_ACCIDENTE_TRABAJO')->default(false);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('INCAPACIDAD', function (Blueprint $table) {
            $table->increments('ID_INCAPACIDAD');
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_TIPOINCAPACIDAD');
            $table->string('NUMERO_CERTIFICADO_ISSS', 50);
            $table->date('FECHA_EMISION');
            $table->date('FECHA_INICIO');
            $table->date('FECHA_FIN');
            $table->integer('DIAS_TOTALES');
            $table->integer('DIAS_PAGADOS_PATRONO')->default(0);
            $table->integer('DIAS_SUBSIDIADOS_ISSS')->default(0);
            $table->integer('DIAS_NO_PAGADOS')->default(0);
            $table->string('CODIGO_DIAGNOSTICO_CIE', 20)->nullable();
            $table->string('NOMBRE_DOCTOR_ISSS', 150)->nullable();
            $table->string('URL_DOCUMENTO_ADJUNTO', 2048)->nullable();
            $table->string('ESTADO_INCAPACIDAD', 20)->default('REGISTRADA');
            $table->string('OBSERVACIONES', 500)->nullable();
            $table->timestamp('FECHA_REGISTRO')->useCurrent();
            $table->string('USUARIO_REGISTRO', 100)->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_TIPOINCAPACIDAD')->references('ID_TIPOINCAPACIDAD')->on('TIPO_INCAPACIDAD')->onDelete('cascade');
        });

        // 8. Periodos, Cuentas y Planilla
        Schema::create('PERIODO_LABORAL', function (Blueprint $table) {
            $table->integer('ID_PERIODO')->primary();
            $table->timestamp('FECHAINICIO');
            $table->timestamp('FECHAFIN');
            $table->integer('DIAS');
            $table->string('CALPERIODO', 50);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('FRECUENCIA_PAGO', function (Blueprint $table) {
            $table->integer('ID_FRECUENCIAPAGO')->primary();
            $table->string('NOMBREFRECUENCIA', 50);
            $table->integer('NUMERODIAS');
        });

        Schema::create('CUENTA', function (Blueprint $table) {
            $table->integer('ID_CUENTA')->primary();
            $table->integer('ID_BANCO')->nullable();
            $table->string('CONCEPTOCUENTA', 150)->nullable();
            $table->string('NUMEROCUENTA', 100)->nullable();
            $table->string('CENTROCOSTO_CODIGO', 50)->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->foreign('ID_BANCO')->references('ID_BANCO')->on('BANCO')->onDelete('set null');
        });

        Schema::create('TIPO_PLANILLA', function (Blueprint $table) {
            $table->integer('ID_TIPOPLANILLA')->primary();
            $table->string('TIPOPLANILLA', 100);
            $table->string('DESCRIPCION', 250)->nullable();
            $table->boolean('APLICA_ISSS')->default(true);
            $table->boolean('APLICA_AFP')->default(true);
            $table->boolean('APLICA_RENTA')->default(true);
            $table->boolean('APLICA_INSAFORP')->default(true);
            $table->decimal('TOPE_SALARIAL_APLICABLE', 18, 2)->nullable();
            $table->boolean('APLICA_RENTA_SOBRE_EXCEDENTE')->default(false);
            $table->boolean('ES_EVENTUAL')->default(false);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('PLANILLA', function (Blueprint $table) {
            $table->integer('ID_PLANILLA')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('ID_TIPOPLANILLA');
            $table->integer('ID_PERIODO');
            $table->integer('ID_FRECUENCIAPAGO');
            $table->integer('ID_CUENTA');
            $table->string('TITULO', 250);
            $table->timestamp('FECHAPAGO');
            $table->string('FORMAPAGO', 50);
            $table->string('AUTORIZADAPOR', 150)->nullable();
            $table->string('OBSERVACION', 500)->nullable();
            $table->boolean('ESACTIVA')->default(true);
            $table->boolean('CERRADA')->default(false);
            $table->boolean('ANULADA')->default(false);
            $table->boolean('RECALCULADA')->default(false);
            $table->boolean('CONTABILIZADA')->default(false);
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->timestamp('FECHAULTIMAMODIFICACION')->nullable();
            $table->string('USUARIOMODIFICO', 100)->nullable();
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
            $table->foreign('ID_TIPOPLANILLA')->references('ID_TIPOPLANILLA')->on('TIPO_PLANILLA')->onDelete('cascade');
            $table->foreign('ID_PERIODO')->references('ID_PERIODO')->on('PERIODO_LABORAL')->onDelete('cascade');
            $table->foreign('ID_FRECUENCIAPAGO')->references('ID_FRECUENCIAPAGO')->on('FRECUENCIA_PAGO')->onDelete('cascade');
        });

        Schema::create('SUBSIDIO_ISSS', function (Blueprint $table) {
            $table->increments('ID_SUBSIDIO');
            $table->integer('ID_INCAPACIDAD');
            $table->integer('ID_PLANILLA')->nullable();
            $table->decimal('SALARIO_DIARIO_PROMEDIO', 18, 4);
            $table->decimal('MONTO_SUBSIDIO_CALCULADO_ISSS', 18, 2);
            $table->decimal('MONTO_PAGADO_POR_PATRONO', 18, 2)->default(0.00);
            $table->string('ESTADO_SUBSIDIO', 30)->default('PENDIENTE');
            $table->date('FECHA_COBRO_ISSS')->nullable();
            $table->string('COMPROBANTE_PAGO_ISSS', 50)->nullable();
            $table->foreign('ID_INCAPACIDAD')->references('ID_INCAPACIDAD')->on('INCAPACIDAD')->onDelete('cascade');
            $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('set null');
        });

        Schema::create('DETALLE_PLANILLA', function (Blueprint $table) {
            $table->integer('ID_DETALLEPLANILLA')->primary();
            $table->integer('ID_PLANILLA');
            $table->integer('ID_EMPLEADO');
            $table->string('NOM_EMPLEADO', 200);
            $table->string('AREA', 100)->nullable();
            $table->string('DEPARTAMENTO', 100)->nullable();
            $table->string('CARGO', 100)->nullable();
            $table->string('TIPO_CONTRATACION_NOM', 100)->nullable();
            $table->string('CODIGO_CENTROCOSTO', 50)->nullable();
            $table->boolean('ES_EVENTUAL');
            $table->boolean('JUBILADO');
            $table->boolean('APLICA_ISSS');
            $table->boolean('APLICA_AFP');
            $table->boolean('APLICA_RENTA_TABLA');
            $table->boolean('APLICA_RENTA_FIJA');
            $table->decimal('PORCENTAJE_RENTA_FIJA', 5, 2)->default(0.00)->nullable();
            $table->boolean('APLICA_INSAFORP');
            $table->decimal('SALARIO_BASE', 18, 2);
            $table->decimal('DIASLABORADOS', 5, 2);
            $table->decimal('SALARIO_DIAS', 18, 2);
            $table->decimal('HORAEXTRAS', 18, 2)->default(0.00)->nullable();
            $table->decimal('PRODUCTIVIDAD', 18, 2)->default(0.00)->nullable();
            $table->decimal('COMISION', 18, 2)->default(0.00)->nullable();
            $table->decimal('OTROS_INGRESOS', 18, 2)->default(0.00)->nullable();
            $table->decimal('DEVENGADO_GRAVADO', 18, 2);
            $table->decimal('DEVENGADO_EXENTO', 18, 2)->default(0.00)->nullable();
            $table->decimal('TOTAL_DEVENGADO', 18, 2);
            $table->decimal('AFP_EMPLEADO', 18, 2)->default(0.00)->nullable();
            $table->decimal('ISSS_EMPLEADO', 18, 2)->default(0.00)->nullable();
            $table->decimal('RENTA_EMPLEADO', 18, 2)->default(0.00)->nullable();
            $table->decimal('DESCUENTOS_LEY', 18, 2)->default(0.00)->nullable();
            $table->decimal('OTRO_DESCUENTOS', 18, 2)->default(0.00)->nullable();
            $table->decimal('PRESTAMOS', 18, 2)->default(0.00)->nullable();
            $table->decimal('ANTICIPO', 18, 2)->default(0.00)->nullable();
            $table->decimal('TOTAL_DEDUCCIONES', 18, 2);
            $table->decimal('LIQUIDO_A_RECIBIR', 18, 2);
            $table->decimal('AFP_PATRONAL', 18, 2)->default(0.00)->nullable();
            $table->decimal('ISSS_PATRONAL', 18, 2)->default(0.00)->nullable();
            $table->decimal('INSAFORP_PATRONAL', 18, 2)->default(0.00)->nullable();
            $table->integer('CORRELATIVO')->nullable();
            $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        // 9. Préstamos, Descuentos e Ingresos Adicionales
        Schema::create('TIPO_DESCUENTO', function (Blueprint $table) {
            $table->integer('ID_TIPODESCUENTO')->primary();
            $table->string('NOMBRETIPODESC', 100);
            $table->string('DESCRIPCIONTIPODESC', 200)->nullable();
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('TIPO_PRESTAMO', function (Blueprint $table) {
            $table->integer('ID_TIPOPRESTAMO')->primary();
            $table->string('NOMBREPRESTAMO', 150);
            $table->string('OBSERVACIONES', 250)->nullable();
        });

        Schema::create('PRESTAMOS', function (Blueprint $table) {
            $table->integer('ID_PRESTAMO')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_TIPODESCUENTO');
            $table->integer('ID_TIPOPRESTAMO');
            $table->decimal('MONTOPRESTAMO', 18, 2);
            $table->decimal('CUOTA', 18, 2);
            $table->integer('NUMCUOTAS');
            $table->decimal('SALDO_ACTUAL', 18, 2);
            $table->timestamp('FECHAINICIO');
            $table->timestamp('FECHAFINALIZACION')->nullable();
            $table->boolean('PRESTAMOESTADO')->default(true);
            $table->text('OBSERVACIONES')->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_TIPODESCUENTO')->references('ID_TIPODESCUENTO')->on('TIPO_DESCUENTO')->onDelete('cascade');
            $table->foreign('ID_TIPOPRESTAMO')->references('ID_TIPOPRESTAMO')->on('TIPO_PRESTAMO')->onDelete('cascade');
        });

        Schema::create('PRESTAMO_ABONO', function (Blueprint $table) {
            $table->integer('ID_PRESTAMOABONO')->primary();
            $table->integer('ID_PRESTAMO');
            $table->integer('ID_DETALLEPLANILLA')->nullable();
            $table->timestamp('FECHAABONO');
            $table->decimal('MONTOABONADO', 18, 2);
            $table->string('CONCEPTO', 250)->nullable();
            $table->boolean('FUERA_PLANILLA')->default(false);
            $table->foreign('ID_PRESTAMO')->references('ID_PRESTAMO')->on('PRESTAMOS')->onDelete('cascade');
            $table->foreign('ID_DETALLEPLANILLA')->references('ID_DETALLEPLANILLA')->on('DETALLE_PLANILLA')->onDelete('set null');
        });

        Schema::create('HORAS_EXTRAS', function (Blueprint $table) {
            $table->integer('ID_HORASEXTRAS')->primary();
            $table->string('TIPOHORAEXTRA', 100);
            $table->decimal('PORCENTAJEEXTRA', 5, 2);
            $table->decimal('FACTOR', 8, 4);
        });

        Schema::create('DETALLES_HORASEXTRAS', function (Blueprint $table) {
            $table->integer('ID_DETALLEHORAEXTRA')->primary();
            $table->integer('ID_HORASEXTRAS');
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_PLANILLA');
            $table->decimal('CANTIDADHORAS', 5, 2);
            $table->decimal('MONTOAPAGAR', 18, 2);
            $table->foreign('ID_HORASEXTRAS')->references('ID_HORASEXTRAS')->on('HORAS_EXTRAS')->onDelete('cascade');
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('cascade');
        });

        Schema::create('TIPO_INGRESO', function (Blueprint $table) {
            $table->integer('ID_TIPOINGRESO')->primary();
            $table->string('TIPOINGRESO', 150);
            $table->boolean('ESACTIVO')->default(true);
        });

        Schema::create('OTRO_INGRESO', function (Blueprint $table) {
            $table->integer('ID_OTROINGRESO')->primary();
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_TIPOINGRESO');
            $table->decimal('MONTOINGRESO', 18, 2);
            $table->timestamp('FECHAINICIO');
            $table->timestamp('FECHAFIN')->nullable();
            $table->boolean('ESACTIVO')->default(true);
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_TIPOINGRESO')->references('ID_TIPOINGRESO')->on('TIPO_INGRESO')->onDelete('cascade');
        });

        // 10. Leyes Fiscales y Recálculo ISR
        Schema::create('FRECUENCIA_ISR', function (Blueprint $table) {
            $table->integer('ID_FRECUENCIAISR')->primary();
            $table->string('FRECUENCIAISR', 100);
            $table->integer('NUMERODIAS');
        });

        Schema::create('RETENCION_ISR', function (Blueprint $table) {
            $table->integer('ID_RETENCIONISR')->primary();
            $table->integer('ID_FRECUENCIAISR');
            $table->integer('ID_PAIS');
            $table->string('NUMEROTRAMO', 10);
            $table->decimal('MONTOINICIAL', 18, 2);
            $table->decimal('MONTOFINA', 18, 2);
            $table->decimal('PORCENTAJEAPLICA', 5, 2);
            $table->decimal('SOBREEXCESO', 18, 2);
            $table->decimal('CUOTAFIJA', 18, 2);
            $table->string('FECHAINICIAL', 10);
            $table->string('FECHAFINAL', 10);
            $table->foreign('ID_FRECUENCIAISR')->references('ID_FRECUENCIAISR')->on('FRECUENCIA_ISR')->onDelete('cascade');
            $table->foreign('ID_PAIS')->references('ID_PAIS')->on('PAIS')->onDelete('cascade');
        });

        Schema::create('RETENCION_LEY', function (Blueprint $table) {
            $table->integer('ID_RETENCIONLEY')->primary();
            $table->integer('ID_PAIS');
            $table->integer('ID_TIPODESCUENTO');
            $table->decimal('APORTACIONPATRONAL', 5, 2);
            $table->decimal('APORTACIONEMPLEADO', 5, 2);
            $table->decimal('SALARIOMINIMO', 18, 2);
            $table->decimal('SALARIOMAXIMO', 18, 2);
            $table->foreign('ID_PAIS')->references('ID_PAIS')->on('PAIS')->onDelete('cascade');
            $table->foreign('ID_TIPODESCUENTO')->references('ID_TIPODESCUENTO')->on('TIPO_DESCUENTO')->onDelete('cascade');
        });

        Schema::create('ACUMULADO_RECALCULO', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('ID_EMPLEADO');
            $table->integer('ID_PLANILLA');
            $table->decimal('MSR', 18, 2);
            $table->decimal('RENTA', 18, 2);
            $table->decimal('RENTA_PENDIENTE_APLICAR', 18, 2)->default(0.00)->nullable();
            $table->integer('MES');
            $table->integer('ANIO');
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
            $table->foreign('ID_PLANILLA')->references('ID_PLANILLA')->on('PLANILLA')->onDelete('cascade');
        });

        Schema::create('PARAMETROS_AGUINALDOS', function (Blueprint $table) {
            $table->integer('ID_PARAMETRO_AGUINALDO')->primary();
            $table->integer('ID_EMPRESA');
            $table->integer('DESDE_ANOS');
            $table->integer('HASTA_ANOS');
            $table->integer('NUMERO_DIAS');
            $table->decimal('SOBRE_EXCEDENTE', 18, 2)->default(0.00)->nullable();
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('cascade');
        });

        Schema::create('LIQUIDACIONES', function (Blueprint $table) {
            $table->integer('ID_LIQUIDACION')->primary();
            $table->integer('ID_EMPLEADO');
            $table->timestamp('FECHA_CONTRATACION');
            $table->timestamp('FECHA_LIQUIDACION');
            $table->decimal('SUELDO', 18, 2);
            $table->decimal('VACACION_PROPORCIONAL', 18, 2)->default(0.00)->nullable();
            $table->decimal('AGUINALDO_PROPORCIONAL', 18, 2)->default(0.00)->nullable();
            $table->decimal('INDEMNIZACION_PROPORCIONAL', 18, 2)->default(0.00)->nullable();
            $table->decimal('DEVENGADO', 18, 2);
            $table->decimal('ISSS', 18, 2)->default(0.00)->nullable();
            $table->decimal('AFP', 18, 2)->default(0.00)->nullable();
            $table->decimal('RENTA', 18, 2)->default(0.00)->nullable();
            $table->decimal('TOTAL_DESCUENTOS', 18, 2);
            $table->decimal('LIQUIDO_A_RECIBIR', 18, 2);
            $table->timestamp('FECHA_CREACION')->useCurrent();
            $table->string('USUARIO_CREACION', 100)->nullable();
            $table->foreign('ID_EMPLEADO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('cascade');
        });

        Schema::create('CALENDARIO_FISCAL', function (Blueprint $table) {
            $table->increments('ID_CALENDARIO_FISCAL');
            $table->integer('ID_EMPRESA')->nullable();
            $table->date('FECHA_VENCIMIENTO');
            $table->string('TIPO_EVENTO', 50);
            $table->string('DESCRIPCION', 500);
            $table->string('FORMULARIO_ASOCIADO', 50);
            $table->string('ESTADO', 20)->default('PENDIENTE');
            $table->timestamp('FECHA_REGISTRO')->useCurrent();
            $table->string('USUARIO_REGISTRO', 100)->nullable();
            $table->foreign('ID_EMPRESA')->references('ID_EMPRESA')->on('EMPRESA')->onDelete('set null');
        });

        // 11. Auditoría
        Schema::create('LOG_TRANSACCIONES', function (Blueprint $table) {
            $table->bigIncrements('ID_LOG');
            $table->char('TIPOTRN', 1);
            $table->string('TABLA', 128);
            $table->string('PK_AFECTADA', 250);
            $table->string('CAMPO', 128)->nullable();
            $table->text('VALOR_ORIGINAL')->nullable();
            $table->text('VALOR_NUEVO')->nullable();
            $table->timestamp('FECHA_TRN')->useCurrent();
        });

        // Add self-referential foreign key to CARGO
        Schema::table('CARGO', function (Blueprint $table) {
            $table->foreign('ID_CARGO_PADRE')->references('ID_CARGO')->on('CARGO')->onDelete('set null');
        });

        // Add self-referential foreign key to EMPLEADO
        Schema::table('EMPLEADO', function (Blueprint $table) {
            $table->foreign('ID_JEFE_INMEDIATO')->references('ID_EMPLEADO')->on('EMPLEADO')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LOG_TRANSACCIONES');
        Schema::dropIfExists('CALENDARIO_FISCAL');
        Schema::dropIfExists('LIQUIDACIONES');
        Schema::dropIfExists('PARAMETROS_AGUINALDOS');
        Schema::dropIfExists('ACUMULADO_RECALCULO');
        Schema::dropIfExists('RETENCION_LEY');
        Schema::dropIfExists('RETENCION_ISR');
        Schema::dropIfExists('FRECUENCIA_ISR');
        Schema::dropIfExists('OTRO_INGRESO');
        Schema::dropIfExists('TIPO_INGRESO');
        Schema::dropIfExists('DETALLES_HORASEXTRAS');
        Schema::dropIfExists('HORAS_EXTRAS');
        Schema::dropIfExists('PRESTAMO_ABONO');
        Schema::dropIfExists('PRESTAMOS');
        Schema::dropIfExists('TIPO_PRESTAMO');
        Schema::dropIfExists('TIPO_DESCUENTO');
        Schema::dropIfExists('DETALLE_PLANILLA');
        Schema::dropIfExists('SUBSIDIO_ISSS');
        Schema::dropIfExists('PLANILLA');
        Schema::dropIfExists('TIPO_PLANILLA');
        Schema::dropIfExists('CUENTA');
        Schema::dropIfExists('FRECUENCIA_PAGO');
        Schema::dropIfExists('PERIODO_LABORAL');
        Schema::dropIfExists('INCAPACIDAD');
        Schema::dropIfExists('TIPO_INCAPACIDAD');
        Schema::dropIfExists('ASISTENCIA_DIARIA');
        Schema::dropIfExists('MARCACION_RAW');
        Schema::dropIfExists('ASIGNACION_HORARIO_EMPLEADO');
        Schema::dropIfExists('USUARIO_PERMISO');
        Schema::dropIfExists('USUARIO_ROL');
        Schema::dropIfExists('USUARIO');
        Schema::dropIfExists('EMPLEADO');
        Schema::dropIfExists('HORARIO_DETALLE');
        Schema::dropIfExists('HORARIOS');
        Schema::dropIfExists('PERFIL_PAGO');
        Schema::dropIfExists('PROFESIONES_OFICIOS');
        Schema::dropIfExists('EDUCACION_ACADEMICA');
        Schema::dropIfExists('ESTADO_CIVIL');
        Schema::dropIfExists('BANCO');
        Schema::dropIfExists('AFP');
        Schema::dropIfExists('TIPO_CONTRATACION');
        Schema::dropIfExists('ROL_PERMISO');
        Schema::dropIfExists('ROL');
        Schema::dropIfExists('PERMISO');
        Schema::dropIfExists('MODULOS');
        Schema::dropIfExists('RUTA');
        Schema::dropIfExists('BODEGA');
        Schema::dropIfExists('SUCURSAL');
        Schema::dropIfExists('CARGO');
        Schema::dropIfExists('DEPARTAMENTO');
        Schema::dropIfExists('AREA');
        Schema::dropIfExists('CENTRO_COSTO');
        Schema::dropIfExists('EMPRESA');
        Schema::dropIfExists('DISTRITO');
        Schema::dropIfExists('MUNICIPIO');
        Schema::dropIfExists('DEPARTAMENTO_PAIS');
        Schema::dropIfExists('PAIS');
    }
};
