<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('MODULOS')->updateOrInsert(
            ['ID_MODULO' => 10],
            [
                'NOMBREMODULO' => 'Portal Empleado',
                'DESCRIPCION' => 'Autoservicio del empleado: boletas, permisos, encuestas y evaluaciones',
                'RUTA_URL' => '/portal',
                'ICONO' => 'user',
                'ORDEN' => 10,
                'ESACTIVO' => true,
            ]
        );

        $permisos = [
            ['ID_PERMISO' => 38, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_VIEW', 'NOMBRE_PERMISO' => 'Ver Portal Empleado', 'DESCRIPCION' => 'Permite acceder al portal de autoservicio del empleado'],
            ['ID_PERMISO' => 39, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_BOLETAS', 'NOMBRE_PERMISO' => 'Ver mis boletas de pago', 'DESCRIPCION' => 'Permite ver las boletas de pago propias de planillas cerradas'],
            ['ID_PERMISO' => 40, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_PERMISOS', 'NOMBRE_PERMISO' => 'Solicitar permisos y vacaciones', 'DESCRIPCION' => 'Permite ver y solicitar permisos/vacaciones propias'],
            ['ID_PERMISO' => 41, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_ENCUESTAS', 'NOMBRE_PERMISO' => 'Responder encuestas', 'DESCRIPCION' => 'Permite ver y responder encuestas asignadas'],
            ['ID_PERMISO' => 42, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_EVALUACIONES', 'NOMBRE_PERMISO' => 'Ver mis evaluaciones', 'DESCRIPCION' => 'Permite ver las evaluaciones de desempeño propias'],
            ['ID_PERMISO' => 43, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_PERFIL', 'NOMBRE_PERMISO' => 'Ver mi perfil', 'DESCRIPCION' => 'Permite ver los datos personales propios (solo lectura)'],
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

        DB::table('ROL')->updateOrInsert(
            ['ID_ROL' => 2],
            ['NOMBREROL' => 'EMPLEADO', 'DESCRIPCION' => 'Autoservicio del empleado (portal)', 'ESACTIVO' => true]
        );

        $portalPermisoIds = array_column($permisos, 'ID_PERMISO');

        // El rol EMPLEADO debe tener EXCLUSIVAMENTE los permisos de Portal Empleado.
        // Se limpia cualquier permiso ajeno que pudiera existir en ID_ROL=2 (p. ej. si el
        // identificador era usado previamente por un rol distinto).
        DB::table('ROL_PERMISO')->where('ID_ROL', 2)->whereNotIn('ID_PERMISO', $portalPermisoIds)->delete();

        foreach ($permisos as $perm) {
            DB::table('ROL_PERMISO')->updateOrInsert(
                ['ID_ROL' => 2, 'ID_PERMISO' => $perm['ID_PERMISO']],
                []
            );
        }
    }

    public function down(): void
    {
        $permisoIds = [38, 39, 40, 41, 42, 43];

        DB::table('ROL_PERMISO')->where('ID_ROL', 2)->whereIn('ID_PERMISO', $permisoIds)->delete();
        DB::table('ROL')->where('ID_ROL', 2)->delete();

        foreach ($permisoIds as $id) {
            DB::table('USUARIO_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('ROL_PERMISO')->where('ID_PERMISO', $id)->delete();
            DB::table('PERMISO')->where('ID_PERMISO', $id)->delete();
        }

        DB::table('MODULOS')->where('ID_MODULO', 10)->delete();
    }
};
