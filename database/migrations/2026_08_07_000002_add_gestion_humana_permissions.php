<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('MODULOS')->updateOrInsert(
            ['ID_MODULO' => 9],
            [
                'NOMBREMODULO' => 'Gestión Humana',
                'DESCRIPCION' => 'Encuestas, calendario, formularios y documentos',
                'RUTA_URL' => '/calendario',
                'ICONO' => 'users',
                'ORDEN' => 9,
                'ESACTIVO' => true,
            ]
        );

        $permisos = [
            ['ID_PERMISO' => 34, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_VIEW', 'NOMBRE_PERMISO' => 'Ver Gestión Humana', 'DESCRIPCION' => 'Permite ver encuestas, calendario, formularios y documentos'],
            ['ID_PERMISO' => 35, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_CREATE', 'NOMBRE_PERMISO' => 'Crear Gestión Humana', 'DESCRIPCION' => 'Permite crear encuestas, eventos, campañas y adjuntos'],
            ['ID_PERMISO' => 36, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Gestión Humana', 'DESCRIPCION' => 'Permite editar y aprobar formularios, publicar encuestas'],
            ['ID_PERMISO' => 37, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Gestión Humana', 'DESCRIPCION' => 'Permite inactivar encuestas, eventos y documentos'],
        ];

        foreach ($permisos as $perm) {
            DB::table('PERMISO')->updateOrInsert(['ID_PERMISO' => $perm['ID_PERMISO']], $perm);
            DB::table('ROL_PERMISO')->updateOrInsert(
                ['ID_ROL' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                []
            );
            if (DB::table('USUARIO')->where('ID_USUARIO', 1)->exists()) {
                DB::table('USUARIO_PERMISO')->updateOrInsert(
                    ['ID_USUARIO' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                    ['ES_CONCEDIDO' => true, 'USUARIO_ASIGNO' => 'SYSTEM']
                );
            }
        }
    }

    public function down(): void
    {
        foreach ([34, 35, 36, 37] as $id) {
            DB::table('USUARIO_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('ROL_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('PERMISO')->where('ID_PERMISO', $id)->delete();
        }
        DB::table('MODULOS')->where('ID_MODULO', 9)->delete();
    }
};
