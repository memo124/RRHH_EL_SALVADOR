<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permiso = [
            'ID_PERMISO' => 33,
            'ID_MODULO' => 6,
            'CODIGO_PERMISO' => 'ERROR_JOURNAL_VIEW',
            'NOMBRE_PERMISO' => 'Ver bitácora de errores',
            'DESCRIPCION' => 'Permite consultar la bitácora técnica de errores del sistema',
        ];

        DB::table('PERMISO')->updateOrInsert(['ID_PERMISO' => 33], $permiso);

        DB::table('ROL_PERMISO')->updateOrInsert(
            ['ID_ROL' => 1, 'ID_PERMISO' => 33],
            []
        );

        DB::table('USUARIO_PERMISO')->updateOrInsert(
            ['ID_USUARIO' => 1, 'ID_PERMISO' => 33],
            ['ES_CONCEDIDO' => true, 'USUARIO_ASIGNO' => 'SYSTEM']
        );
    }

    public function down(): void
    {
        DB::table('USUARIO_PERMISO')->where('ID_PERMISO', 33)->delete();
        DB::table('ROL_PERMISO')->where('ID_PERMISO', 33)->delete();
        DB::table('PERMISO')->where('ID_PERMISO', 33)->delete();
    }
};
