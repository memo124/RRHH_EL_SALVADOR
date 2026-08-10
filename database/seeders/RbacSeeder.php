<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ROL')->updateOrInsert(
            ['ID_ROL' => 1],
            ['NOMBREROL' => 'SuperAdmin', 'DESCRIPCION' => 'Acceso Total al Sistema', 'ESACTIVO' => true]
        );

        $modulos = [
            ['ID_MODULO' => 1, 'NOMBREMODULO' => 'Geográfico', 'DESCRIPCION' => 'Mantenimiento Geográfico', 'RUTA_URL' => '/geografia', 'ICONO' => 'globe', 'ORDEN' => 1, 'ESACTIVO' => true],
            ['ID_MODULO' => 2, 'NOMBREMODULO' => 'Catálogos MH', 'DESCRIPCION' => 'Documentos y Actividades MH', 'RUTA_URL' => '/catalogos-mh', 'ICONO' => 'document', 'ORDEN' => 2, 'ESACTIVO' => true],
            ['ID_MODULO' => 3, 'NOMBREMODULO' => 'Corporativo', 'DESCRIPCION' => 'Estructura Empresarial', 'RUTA_URL' => '/corporativo', 'ICONO' => 'office', 'ORDEN' => 3, 'ESACTIVO' => true],
            ['ID_MODULO' => 4, 'NOMBREMODULO' => 'Salarial y Retenciones', 'DESCRIPCION' => 'Planilla, empleados, liquidaciones, aguinaldo', 'RUTA_URL' => '/planilla', 'ICONO' => 'cash', 'ORDEN' => 4, 'ESACTIVO' => true],
            ['ID_MODULO' => 5, 'NOMBREMODULO' => 'Deducciones e Ingresos', 'DESCRIPCION' => 'Préstamos, descuentos, incapacidades', 'RUTA_URL' => '/deducciones', 'ICONO' => 'calculator', 'ORDEN' => 5, 'ESACTIVO' => true],
            ['ID_MODULO' => 6, 'NOMBREMODULO' => 'Seguridad', 'DESCRIPCION' => 'Usuarios, Roles, Permisos', 'RUTA_URL' => '/seguridad', 'ICONO' => 'shield', 'ORDEN' => 6, 'ESACTIVO' => true],
            ['ID_MODULO' => 7, 'NOMBREMODULO' => 'Asistencia', 'DESCRIPCION' => 'Horarios, marcaciones y asistencia', 'RUTA_URL' => '/asistencia', 'ICONO' => 'clock', 'ORDEN' => 7, 'ESACTIVO' => true],
            ['ID_MODULO' => 8, 'NOMBREMODULO' => 'Contratos Laborales', 'DESCRIPCION' => 'Contratos, plantillas HTML y documentos laborales', 'RUTA_URL' => '/contratos', 'ICONO' => 'file-text', 'ORDEN' => 8, 'ESACTIVO' => true],
            ['ID_MODULO' => 9, 'NOMBREMODULO' => 'Gestión Humana', 'DESCRIPCION' => 'Encuestas, calendario, formularios y documentos', 'RUTA_URL' => '/calendario', 'ICONO' => 'users', 'ORDEN' => 9, 'ESACTIVO' => true],
            ['ID_MODULO' => 10, 'NOMBREMODULO' => 'Portal Empleado', 'DESCRIPCION' => 'Autoservicio del empleado: boletas, permisos, encuestas y evaluaciones', 'RUTA_URL' => '/portal', 'ICONO' => 'user', 'ORDEN' => 10, 'ESACTIVO' => true],
        ];

        foreach ($modulos as $mod) {
            DB::table('MODULOS')->updateOrInsert(['ID_MODULO' => $mod['ID_MODULO']], $mod);
        }

        $permisos = $this->permisos();

        foreach ($permisos as $perm) {
            DB::table('PERMISO')->updateOrInsert(['ID_PERMISO' => $perm['ID_PERMISO']], $perm);
            DB::table('ROL_PERMISO')->updateOrInsert(
                ['ID_ROL' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                []
            );
        }

        DB::table('ROL')->updateOrInsert(
            ['ID_ROL' => 2],
            ['NOMBREROL' => 'EMPLEADO', 'DESCRIPCION' => 'Autoservicio del empleado (portal)', 'ESACTIVO' => true]
        );

        $portalPermisoIds = array_column(
            array_filter($permisos, fn ($p) => str_starts_with($p['CODIGO_PERMISO'], 'PORTAL_')),
            'ID_PERMISO'
        );

        // El rol EMPLEADO debe tener EXCLUSIVAMENTE los permisos de Portal Empleado.
        DB::table('ROL_PERMISO')->where('ID_ROL', 2)->whereNotIn('ID_PERMISO', $portalPermisoIds)->delete();

        foreach ($permisos as $perm) {
            if (!str_starts_with($perm['CODIGO_PERMISO'], 'PORTAL_')) {
                continue;
            }
            DB::table('ROL_PERMISO')->updateOrInsert(
                ['ID_ROL' => 2, 'ID_PERMISO' => $perm['ID_PERMISO']],
                []
            );
        }
    }

    /** @return list<array<string, mixed>> */
    public static function permisos(): array
    {
        return [
            ['ID_PERMISO' => 1, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_VIEW', 'NOMBRE_PERMISO' => 'Ver Geografía', 'DESCRIPCION' => 'Permite ver países, departamentos, municipios, distritos'],
            ['ID_PERMISO' => 2, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_CREATE', 'NOMBRE_PERMISO' => 'Crear Geografía', 'DESCRIPCION' => 'Permite crear elementos geográficos'],
            ['ID_PERMISO' => 3, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Geografía', 'DESCRIPCION' => 'Permite editar elementos geográficos'],
            ['ID_PERMISO' => 4, 'ID_MODULO' => 1, 'CODIGO_PERMISO' => 'GEOGRAFIA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Geografía', 'DESCRIPCION' => 'Permite eliminar elementos geográficos'],
            ['ID_PERMISO' => 5, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_VIEW', 'NOMBRE_PERMISO' => 'Ver MH', 'DESCRIPCION' => 'Permite ver catálogos MH'],
            ['ID_PERMISO' => 6, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_CREATE', 'NOMBRE_PERMISO' => 'Crear MH', 'DESCRIPCION' => 'Permite crear catálogos MH'],
            ['ID_PERMISO' => 7, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_UPDATE', 'NOMBRE_PERMISO' => 'Editar MH', 'DESCRIPCION' => 'Permite editar catálogos MH'],
            ['ID_PERMISO' => 8, 'ID_MODULO' => 2, 'CODIGO_PERMISO' => 'MH_DELETE', 'NOMBRE_PERMISO' => 'Eliminar MH', 'DESCRIPCION' => 'Permite eliminar catálogos MH'],
            ['ID_PERMISO' => 9, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_VIEW', 'NOMBRE_PERMISO' => 'Ver Corporativo', 'DESCRIPCION' => 'Permite ver estructura corporativa'],
            ['ID_PERMISO' => 10, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_CREATE', 'NOMBRE_PERMISO' => 'Crear Corporativo', 'DESCRIPCION' => 'Permite crear estructura corporativa'],
            ['ID_PERMISO' => 11, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_UPDATE', 'NOMBRE_PERMISO' => 'Editar Corporativo', 'DESCRIPCION' => 'Permite editar estructura corporativa'],
            ['ID_PERMISO' => 12, 'ID_MODULO' => 3, 'CODIGO_PERMISO' => 'CORP_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Corporativo', 'DESCRIPCION' => 'Permite inactivar estructura corporativa'],
            ['ID_PERMISO' => 13, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_VIEW', 'NOMBRE_PERMISO' => 'Ver Salarial', 'DESCRIPCION' => 'Permite ver configuraciones salariales'],
            ['ID_PERMISO' => 14, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_CREATE', 'NOMBRE_PERMISO' => 'Crear Salarial', 'DESCRIPCION' => 'Permite crear configuraciones salariales'],
            ['ID_PERMISO' => 15, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_UPDATE', 'NOMBRE_PERMISO' => 'Editar Salarial', 'DESCRIPCION' => 'Permite editar configuraciones salariales'],
            ['ID_PERMISO' => 16, 'ID_MODULO' => 4, 'CODIGO_PERMISO' => 'SALARIAL_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Salarial', 'DESCRIPCION' => 'Permite eliminar configuraciones salariales'],
            ['ID_PERMISO' => 17, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_VIEW', 'NOMBRE_PERMISO' => 'Ver Deducciones', 'DESCRIPCION' => 'Permite ver deducciones e ingresos'],
            ['ID_PERMISO' => 18, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_CREATE', 'NOMBRE_PERMISO' => 'Crear Deducciones', 'DESCRIPCION' => 'Permite crear deducciones e ingresos'],
            ['ID_PERMISO' => 19, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_UPDATE', 'NOMBRE_PERMISO' => 'Editar Deducciones', 'DESCRIPCION' => 'Permite editar deducciones e ingresos'],
            ['ID_PERMISO' => 20, 'ID_MODULO' => 5, 'CODIGO_PERMISO' => 'DEDUCCIONES_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Deducciones', 'DESCRIPCION' => 'Permite eliminar deducciones e ingresos'],
            ['ID_PERMISO' => 21, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_VIEW', 'NOMBRE_PERMISO' => 'Ver Seguridad', 'DESCRIPCION' => 'Permite ver usuarios, roles y permisos'],
            ['ID_PERMISO' => 22, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_CREATE', 'NOMBRE_PERMISO' => 'Crear Seguridad', 'DESCRIPCION' => 'Permite crear elementos de seguridad'],
            ['ID_PERMISO' => 23, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_UPDATE', 'NOMBRE_PERMISO' => 'Editar Seguridad', 'DESCRIPCION' => 'Permite editar elementos de seguridad'],
            ['ID_PERMISO' => 24, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'SEGURIDAD_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Seguridad', 'DESCRIPCION' => 'Permite eliminar/inactivar elementos de seguridad'],
            ['ID_PERMISO' => 25, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_VIEW', 'NOMBRE_PERMISO' => 'Ver Asistencia', 'DESCRIPCION' => 'Permite ver horarios, marcaciones y asistencia'],
            ['ID_PERMISO' => 26, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_CREATE', 'NOMBRE_PERMISO' => 'Crear Asistencia', 'DESCRIPCION' => 'Permite registrar marcaciones y horarios'],
            ['ID_PERMISO' => 27, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Asistencia', 'DESCRIPCION' => 'Permite procesar asistencia y editar horarios'],
            ['ID_PERMISO' => 28, 'ID_MODULO' => 7, 'CODIGO_PERMISO' => 'ASISTENCIA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Asistencia', 'DESCRIPCION' => 'Permite inactivar horarios'],
            ['ID_PERMISO' => 29, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_VIEW', 'NOMBRE_PERMISO' => 'Ver Contratos', 'DESCRIPCION' => 'Permite ver contratos laborales y plantillas'],
            ['ID_PERMISO' => 30, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_CREATE', 'NOMBRE_PERMISO' => 'Crear Contratos', 'DESCRIPCION' => 'Permite crear contratos y plantillas'],
            ['ID_PERMISO' => 31, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_UPDATE', 'NOMBRE_PERMISO' => 'Editar Contratos', 'DESCRIPCION' => 'Permite editar contratos y plantillas'],
            ['ID_PERMISO' => 32, 'ID_MODULO' => 8, 'CODIGO_PERMISO' => 'CONTRATO_DELETE', 'NOMBRE_PERMISO' => 'Anular Contratos', 'DESCRIPCION' => 'Permite anular contratos e inactivar plantillas'],
            ['ID_PERMISO' => 33, 'ID_MODULO' => 6, 'CODIGO_PERMISO' => 'ERROR_JOURNAL_VIEW', 'NOMBRE_PERMISO' => 'Ver bitácora de errores', 'DESCRIPCION' => 'Permite consultar la bitácora técnica de errores del sistema'],
            ['ID_PERMISO' => 34, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_VIEW', 'NOMBRE_PERMISO' => 'Ver Gestión Humana', 'DESCRIPCION' => 'Permite ver encuestas, calendario, formularios y documentos'],
            ['ID_PERMISO' => 35, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_CREATE', 'NOMBRE_PERMISO' => 'Crear Gestión Humana', 'DESCRIPCION' => 'Permite crear encuestas, eventos, campañas y adjuntos'],
            ['ID_PERMISO' => 36, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_UPDATE', 'NOMBRE_PERMISO' => 'Editar Gestión Humana', 'DESCRIPCION' => 'Permite editar y aprobar formularios, publicar encuestas'],
            ['ID_PERMISO' => 37, 'ID_MODULO' => 9, 'CODIGO_PERMISO' => 'GESTION_HUMANA_DELETE', 'NOMBRE_PERMISO' => 'Eliminar Gestión Humana', 'DESCRIPCION' => 'Permite inactivar encuestas, eventos y documentos'],
            ['ID_PERMISO' => 38, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_VIEW', 'NOMBRE_PERMISO' => 'Ver Portal Empleado', 'DESCRIPCION' => 'Permite acceder al portal de autoservicio del empleado'],
            ['ID_PERMISO' => 39, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_BOLETAS', 'NOMBRE_PERMISO' => 'Ver mis boletas de pago', 'DESCRIPCION' => 'Permite ver las boletas de pago propias de planillas cerradas'],
            ['ID_PERMISO' => 40, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_PERMISOS', 'NOMBRE_PERMISO' => 'Solicitar permisos y vacaciones', 'DESCRIPCION' => 'Permite ver y solicitar permisos/vacaciones propias'],
            ['ID_PERMISO' => 41, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_ENCUESTAS', 'NOMBRE_PERMISO' => 'Responder encuestas', 'DESCRIPCION' => 'Permite ver y responder encuestas asignadas'],
            ['ID_PERMISO' => 42, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_EVALUACIONES', 'NOMBRE_PERMISO' => 'Ver mis evaluaciones', 'DESCRIPCION' => 'Permite ver las evaluaciones de desempeño propias'],
            ['ID_PERMISO' => 43, 'ID_MODULO' => 10, 'CODIGO_PERMISO' => 'PORTAL_PERFIL', 'NOMBRE_PERMISO' => 'Ver mi perfil', 'DESCRIPCION' => 'Permite ver los datos personales propios (solo lectura)'],
        ];
    }
}
