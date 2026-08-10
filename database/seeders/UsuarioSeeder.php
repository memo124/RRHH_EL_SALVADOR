<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        $permisos = RbacSeeder::permisos();

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

        DB::table('USUARIO_ROL')->updateOrInsert(
            ['ID_USUARIO' => 1, 'ID_ROL' => 1]
        );

        foreach ($permisos as $perm) {
            DB::table('USUARIO_PERMISO')->updateOrInsert(
                ['ID_USUARIO' => 1, 'ID_PERMISO' => $perm['ID_PERMISO']],
                ['ES_CONCEDIDO' => true, 'USUARIO_ASIGNO' => 'SYSTEM']
            );
        }

        $this->seedPortalEmployeeUser();
    }

    /**
     * Usuario demo del Portal Empleado, vinculado al primer empleado activo (si existe).
     */
    private function seedPortalEmployeeUser(): void
    {
        $primerEmpleado = DB::table('EMPLEADO')->where('ESACTIVO', true)->orderBy('ID_EMPLEADO')->first();

        if (!$primerEmpleado) {
            return;
        }

        $idUsuarioEmpleado = DB::table('USUARIO')->where('USUARIO', 'empleado@rrhh.sv')->value('ID_USUARIO')
            ?? ((DB::table('USUARIO')->max('ID_USUARIO') ?? 0) + 1);

        DB::table('USUARIO')->updateOrInsert(
            ['USUARIO' => 'empleado@rrhh.sv'],
            [
                'ID_USUARIO' => $idUsuarioEmpleado,
                'ID_EMPLEADO' => $primerEmpleado->ID_EMPLEADO,
                'CONTRASENA_HASH' => Hash::make('Empleado123!'),
                'EMAIL' => 'empleado@rrhh.sv',
                'ESACTIVO' => true,
                'BLOQUEADO' => false,
            ]
        );

        DB::table('USUARIO_ROL')->updateOrInsert(['ID_USUARIO' => $idUsuarioEmpleado, 'ID_ROL' => 2]);
    }
}
