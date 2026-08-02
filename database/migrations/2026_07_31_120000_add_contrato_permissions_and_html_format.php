<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('PLANTILLA_CONTRATO') && !Schema::hasColumn('PLANTILLA_CONTRATO', 'FORMATO')) {
            Schema::table('PLANTILLA_CONTRATO', function (Blueprint $table) {
                $table->string('FORMATO', 10)->default('HTML')->after('DESCRIPCION');
            });
        }

        DB::table('MODULOS')->updateOrInsert(
            ['ID_MODULO' => 8],
            [
                'NOMBREMODULO' => 'Contratos Laborales',
                'DESCRIPCION' => 'Contratos, plantillas HTML y documentos laborales',
                'RUTA_URL' => '/contratos',
                'ICONO' => 'file-text',
                'ORDEN' => 8,
                'ESACTIVO' => true,
            ]
        );

        $permisos = [
            ['ID_PERMISO' => 29, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_VIEW', 'NOMBRE_PERMISO' => 'Ver Contratos', 'DESCRIPCION' => 'Permite ver contratos laborales y plantillas'],
            ['ID_PERMISO' => 30, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_CREATE', 'NOMBRE_PERMISO' => 'Crear Contratos', 'DESCRIPCION' => 'Permite crear contratos y plantillas'],
            ['ID_PERMISO' => 31, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_UPDATE', 'NOMBRE_PERMISO' => 'Editar Contratos', 'DESCRIPCION' => 'Permite editar contratos y plantillas'],
            ['ID_PERMISO' => 32, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_DELETE', 'NOMBRE_PERMISO' => 'Anular Contratos', 'DESCRIPCION' => 'Permite anular contratos e inactivar plantillas'],
        ];

        foreach ($permisos as $perm) {
            DB::table('PERMISO')->updateOrInsert(['ID_PERMISO' => $perm['ID_PERMISO']], $perm);
        }

        $this->actualizarPlantillaBaseHtml();
    }

    public function down(): void
    {
        foreach ([29, 30, 31, 32] as $id) {
            DB::table('USUARIO_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('ROL_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('PERMISO')->where('ID_PERMISO', $id)->delete();
        }
        DB::table('MODULOS')->where('ID_MODULO', 8)->delete();

        if (Schema::hasColumn('PLANTILLA_CONTRATO', 'FORMATO')) {
            Schema::table('PLANTILLA_CONTRATO', function (Blueprint $table) {
                $table->dropColumn('FORMATO');
            });
        }
    }

    private function actualizarPlantillaBaseHtml(): void
    {
        if (!Schema::hasTable('PLANTILLA_CONTRATO')) {
            return;
        }

        $html = <<<'HTML'
<h1 style="text-align:center;font-size:18px;margin-bottom:24px;">CONTRATO DE TRABAJO INDIVIDUAL</h1>

<p>En la ciudad de San Salvador, a los <strong>{{fecha_actual}}</strong>, comparecen:</p>

<p>Por una parte, <strong>{{empresa.nombre}}</strong>, con NIT <strong>{{empresa.nit}}</strong>, con domicilio en {{empresa.direccion}}, representada por <strong>{{empresa.dueno}}</strong>, con DUI {{empresa.dueno_dui}}, a quien en adelante se le denominará <em>"EL PATRONO"</em>;</p>

<p>Y por la otra parte, <strong>{{empleado.nombre_completo}}</strong>, de nacionalidad salvadoreña, mayor de edad, con Documento Único de Identidad número <strong>{{empleado.dui}}</strong>, a quien en adelante se le denominará <em>"EL TRABAJADOR"</em>;</p>

<p>Ambas partes convienen celebrar el presente contrato de trabajo bajo las siguientes cláusulas:</p>

<ol>
  <li><strong>PRIMERA.</strong> EL PATRONO contrata los servicios de EL TRABAJADOR para desempeñar el cargo de <strong>{{empleado.cargo}}</strong> en el departamento de <strong>{{empleado.departamento}}</strong>.</li>
  <li><strong>SEGUNDA.</strong> La vigencia del presente contrato será {{contrato.vigencia}}.</li>
  <li><strong>TERCERA.</strong> EL TRABAJADOR percibirá un salario mensual de <strong>${{empleado.salario}}</strong> ({{empleado.salario_letras}}), pagadero conforme a la legislación laboral vigente.</li>
  <li><strong>CUARTA.</strong> EL TRABAJADOR se obliga a cumplir con las disposiciones internas de la empresa, reglamentos y normas de seguridad e higiene ocupacional.</li>
</ol>

<div>{{clausulas}}</div>

<p>En fe de lo cual, firman el presente contrato en dos ejemplares del mismo tenor.</p>

{{firmantes}}
HTML;

        $clausulasHtml = '<p><strong>QUINTA.</strong> Las demás condiciones no previstas en este contrato se regirán por el Código de Trabajo de El Salvador y demás leyes aplicables.</p>';

        if (DB::table('PLANTILLA_CONTRATO')->where('ID_PLANTILLA', 1)->exists()) {
            DB::table('PLANTILLA_CONTRATO')->where('ID_PLANTILLA', 1)->update([
                'CONTENIDO' => $html,
                'CLAUSULAS' => $clausulasHtml,
                'FORMATO' => 'HTML',
            ]);
        } else {
            DB::table('PLANTILLA_CONTRATO')->insert([
                'ID_PLANTILLA' => 1,
                'ID_EMPRESA' => null,
                'NOMBRE' => 'Contrato de Trabajo — Permanente',
                'DESCRIPCION' => 'Plantilla HTML base para contrato laboral permanente en El Salvador',
                'FORMATO' => 'HTML',
                'CONTENIDO' => $html,
                'CLAUSULAS' => $clausulasHtml,
                'ESACTIVO' => true,
                'FECHA_CREACION' => now(),
            ]);
        }
    }
};
