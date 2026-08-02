<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Rol Admin
        DB::table('ROL')->updateOrInsert(
            ['ID_ROL' => 1],
            ['NOMBREROL' => 'SuperAdmin', 'DESCRIPCION' => 'Acceso Total al Sistema', 'ESACTIVO' => true]
        );

        // 2. Create Modulos
        $modulos = [
            ['ID_MODULO' => 1, 'NOMBREMODULO' => 'Geográfico', 'DESCRIPCION' => 'Mantenimiento Geográfico', 'RUTA_URL' => '/geografia', 'ICONO' => 'globe', 'ORDEN' => 1, 'ESACTIVO' => true],
            ['ID_MODULO' => 2, 'NOMBREMODULO' => 'Catálogos MH', 'DESCRIPCION' => 'Documentos y Actividades MH', 'RUTA_URL' => '/catalogos-mh', 'ICONO' => 'document', 'ORDEN' => 2, 'ESACTIVO' => true],
            ['ID_MODULO' => 3, 'NOMBREMODULO' => 'Corporativo', 'DESCRIPCION' => 'Estructura Empresarial', 'RUTA_URL' => '/corporativo', 'ICONO' => 'office', 'ORDEN' => 3, 'ESACTIVO' => true],
            ['ID_MODULO' => 4, 'NOMBREMODULO' => 'Salarial y Retenciones', 'DESCRIPCION' => 'Planilla, empleados, liquidaciones, aguinaldo', 'RUTA_URL' => '/planilla', 'ICONO' => 'cash', 'ORDEN' => 4, 'ESACTIVO' => true],
            ['ID_MODULO' => 5, 'NOMBREMODULO' => 'Deducciones e Ingresos', 'DESCRIPCION' => 'Préstamos, descuentos, incapacidades', 'RUTA_URL' => '/deducciones', 'ICONO' => 'calculator', 'ORDEN' => 5, 'ESACTIVO' => true],
            ['ID_MODULO' => 6, 'NOMBREMODULO' => 'Seguridad', 'DESCRIPCION' => 'Usuarios, Roles, Permisos', 'RUTA_URL' => '/seguridad', 'ICONO' => 'shield', 'ORDEN' => 6, 'ESACTIVO' => true],
            ['ID_MODULO' => 7, 'NOMBREMODULO' => 'Asistencia', 'DESCRIPCION' => 'Horarios, marcaciones y asistencia', 'RUTA_URL' => '/asistencia', 'ICONO' => 'clock', 'ORDEN' => 7, 'ESACTIVO' => true],
            ['ID_MODULO' => 8, 'NOMBREMODULO' => 'Contratos Laborales', 'DESCRIPCION' => 'Contratos, plantillas HTML y documentos laborales', 'RUTA_URL' => '/contratos', 'ICONO' => 'file-text', 'ORDEN' => 8, 'ESACTIVO' => true],
        ];

        foreach ($modulos as $mod) {
            DB::table('MODULOS')->updateOrInsert(['ID_MODULO' => $mod['ID_MODULO']], $mod);
        }

        // 3. Create Permisos
        $permisos = [
            // Geografico
            ['ID_PERMISO' => 1, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_VIEW', 'NOMBRE_PERMISO' => 'Ver Geografía', 'DESCRIPCION' => 'Permite ver países, departamentos, municipios, distritos'],
            ['ID_PERMISO' => 2, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_CREATE', 'NOMBRE_PERMISO' => 'Crear Geografía', 'DESCRIPCION' => 'Permite crear elementos geográficos'],
            ['ID_PERMISO' => 3, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Geografía', 'DESCRIPCION' => 'Permite editar elementos geográficos'],
            ['ID_PERMISO' => 4, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Geografía', 'DESCRIPCION' => 'Permite eliminar elementos geográficos'],

            // Catalogos MH
            ['ID_PERMISO' => 5, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_VIEW', 'NOMBRE_PERMISO' => 'Ver MH', 'DESCRIPCION' => 'Permite ver catálogos MH'],
            ['ID_PERMISO' => 6, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_CREATE', 'NOMBRE_PERMISO' => 'Crear MH', 'DESCRIPCION' => 'Permite crear catálogos MH'],
            ['ID_PERMISO' => 7, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_UPDATE', 'NOMBRE_PERMISO' => 'Editar MH', 'DESCRIPCION' => 'Permite editar catálogos MH'],
            ['ID_PERMISO' => 8, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_DELETE', 'NOMBRE_PERMISO' => 'Eliminar MH', 'DESCRIPCION' => 'Permite eliminar catálogos MH'],

            // Corporativo
            ['ID_PERMISO' => 9, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_VIEW', 'NOMBRE_PERMISO' => 'Ver Corporativo', 'DESCRIPCION' => 'Permite ver estructura corporativa'],
            ['ID_PERMISO' => 10, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_CREATE', 'NOMBRE_PERMISO' => 'Crear Corporativo', 'DESCRIPCION' => 'Permite crear estructura corporativa'],
            ['ID_PERMISO' => 11, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_UPDATE', 'NOMBRE_PERMISO' => 'Editar Corporativo', 'DESCRIPCION' => 'Permite editar estructura corporativa'],
            ['ID_PERMISO' => 12, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Corporativo', 'DESCRIPCION' => 'Permite inactivar estructura corporativa'],

            // Salarial y Retenciones
            ['ID_PERMISO' => 13, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_VIEW', 'NOMBRE_PERMISO' => 'Ver Salarial', 'DESCRIPCION' => 'Permite ver configuraciones salariales'],
            ['ID_PERMISO' => 14, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_CREATE', 'NOMBRE_PERMISO' => 'Crear Salarial', 'DESCRIPCION' => 'Permite crear configuraciones salariales'],
            ['ID_PERMISO' => 15, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_UPDATE', 'NOMBRE_PERMISO' => 'Editar Salarial', 'DESCRIPCION' => 'Permite editar configuraciones salariales'],
            ['ID_PERMISO' => 16, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Salarial', 'DESCRIPCION' => 'Permite eliminar configuraciones salariales'],

            // Deducciones e Ingresos
            ['ID_PERMISO' => 17, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_VIEW', 'NOMBRE_PERMISO' => 'Ver Deducciones', 'DESCRIPCION' => 'Permite ver deducciones e ingresos'],
            ['ID_PERMISO' => 18, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_CREATE', 'NOMBRE_PERMISO' => 'Crear Deducciones', 'DESCRIPCION' => 'Permite crear deducciones e ingresos'],
            ['ID_PERMISO' => 19, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_UPDATE', 'NOMBRE_PERMISO' => 'Editar Deducciones', 'DESCRIPCION' => 'Permite editar deducciones e ingresos'],
            ['ID_PERMISO' => 20, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Deducciones', 'DESCRIPCION' => 'Permite eliminar deducciones e ingresos'],

            // Seguridad
            ['ID_PERMISO' => 21, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_VIEW', 'NOMBRE_PERMISO' => 'Ver Seguridad', 'DESCRIPCION' => 'Permite ver usuarios, roles y permisos'],
            ['ID_PERMISO' => 22, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_CREATE', 'NOMBRE_PERMISO' => 'Crear Seguridad', 'DESCRIPCION' => 'Permite crear elementos de seguridad'],
            ['ID_PERMISO' => 23, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_UPDATE', 'NOMBRE_PERMISO' => 'Editar Seguridad', 'DESCRIPCION' => 'Permite editar elementos de seguridad'],
            ['ID_PERMISO' => 24, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Seguridad', 'DESCRIPCION' => 'Permite eliminar/inactivar elementos de seguridad'],

            // Asistencia (horarios, marcaciones, procesamiento)
            ['ID_PERMISO' => 25, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_VIEW', 'NOMBRE_PERMISO' => 'Ver Asistencia', 'DESCRIPCION' => 'Permite ver horarios, marcaciones y asistencia'],
            ['ID_PERMISO' => 26, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_CREATE', 'NOMBRE_PERMISO' => 'Crear Asistencia', 'DESCRIPCION' => 'Permite registrar marcaciones y horarios'],
            ['ID_PERMISO' => 27, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Asistencia', 'DESCRIPCION' => 'Permite procesar asistencia y editar horarios'],
            ['ID_PERMISO' => 28, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Asistencia', 'DESCRIPCION' => 'Permite inactivar horarios'],

            // Contratos Laborales
            ['ID_PERMISO' => 29, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_VIEW', 'NOMBRE_PERMISO' => 'Ver Contratos', 'DESCRIPCION' => 'Permite ver contratos laborales y plantillas'],
            ['ID_PERMISO' => 30, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_CREATE', 'NOMBRE_PERMISO' => 'Crear Contratos', 'DESCRIPCION' => 'Permite crear contratos y plantillas'],
            ['ID_PERMISO' => 31, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_UPDATE', 'NOMBRE_PERMISO' => 'Editar Contratos', 'DESCRIPCION' => 'Permite editar contratos y plantillas'],
            ['ID_PERMISO' => 32, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_DELETE', 'NOMBRE_PERMISO' => 'Anular Contratos', 'DESCRIPCION' => 'Permite anular contratos e inactivar plantillas'],
        ];

        foreach ($permisos as $perm) {
            DB::table('PERMISO')->updateOrInsert(['ID_PERMISO' => $perm['ID_PERMISO']], $perm);
        }

        // 4. Create SuperAdmin User
        DB::table('USUARIO')->updateOrInsert(
            ['ID_USUARIO' => 1],
            [
                'ID_EMPLEADO' => null,
                'USUARIO' => 'admin@rrhh.sv',
                'CONTRASENA_HASH' => Hash::make('Admin123!'),
                'EMAIL' => 'admin@rrhh.sv',
                'ESACTIVO' => true,
                'BLOQUEADO' => false,
            ]
        );

        // 5. Assign Role to User
        DB::table('USUARIO_ROL')->updateOrInsert(
            ['ID_USUARIO' => 1, 'ID_ROL' => 1]
        );

        // 6. Assign ALL permissions to SuperAdmin Role (ROL_PERMISO)
        foreach ($permisos as $perm) {
            DB::table('ROL_PERMISO')->updateOrInsert(
                ['ID_ROL' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                []
            );
        }

        // 7. Assign ALL permissions to admin user (USUARIO_PERMISO)
        foreach ($permisos as $perm) {
            DB::table('USUARIO_PERMISO')->updateOrInsert(
                ['ID_USUARIO' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                ['ES_CONCEDIDO' => true, 'USUARIO_ASIGNO' => 'SYSTEM']
            );
        }
    }
}
