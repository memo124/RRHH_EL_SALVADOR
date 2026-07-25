<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDocumentoIdentidadSeeder extends Seeder
{
    public function run(): void
    {
        $documentos = [
            ['ID_TIPODOCUMENTO' => 1, 'CODIGO_MH' => '13', 'NOMBREDOCUMENTO' => 'DUI (Documento Único de Identidad)', 'MASCARA_FORMATO' => '00000000-0', 'ESACTIVO' => true],
            ['ID_TIPODOCUMENTO' => 2, 'CODIGO_MH' => '36', 'NOMBREDOCUMENTO' => 'NIT (Número de Identificación Tributaria)', 'MASCARA_FORMATO' => '0000-000000-000-0', 'ESACTIVO' => true],
            ['ID_TIPODOCUMENTO' => 3, 'CODIGO_MH' => '03', 'NOMBREDOCUMENTO' => 'Pasaporte', 'MASCARA_FORMATO' => null, 'ESACTIVO' => true],
            ['ID_TIPODOCUMENTO' => 4, 'CODIGO_MH' => '02', 'NOMBREDOCUMENTO' => 'Carnet de Residente', 'MASCARA_FORMATO' => null, 'ESACTIVO' => true],
            ['ID_TIPODOCUMENTO' => 5, 'CODIGO_MH' => '37', 'NOMBREDOCUMENTO' => 'Otro Documento de Identificación', 'MASCARA_FORMATO' => null, 'ESACTIVO' => true]
        ];

        foreach ($documentos as $doc) {
            DB::table('TIPO_DOCUMENTO_IDENTIDAD')->updateOrInsert(['ID_TIPODOCUMENTO' => $doc['ID_TIPODOCUMENTO']], $doc);
        }
    }
}
