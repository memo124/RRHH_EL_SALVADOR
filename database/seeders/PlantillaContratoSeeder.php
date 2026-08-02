<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantillaContratoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('PLANTILLA_CONTRATO')->exists()) {
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

        DB::table('PLANTILLA_CONTRATO')->insert([
            'ID_PLANTILLA' => 1,
            'ID_EMPRESA' => null,
            'NOMBRE' => 'Contrato de Trabajo — Permanente',
            'DESCRIPCION' => 'Plantilla HTML base para contrato laboral permanente en El Salvador',
            'FORMATO' => 'HTML',
            'CONTENIDO' => $html,
            'CLAUSULAS' => '<p><strong>QUINTA.</strong> Las demás condiciones no previstas en este contrato se regirán por el Código de Trabajo de El Salvador y demás leyes aplicables.</p>',
            'ESACTIVO' => true,
            'FECHA_CREACION' => now(),
        ]);
    }
}
