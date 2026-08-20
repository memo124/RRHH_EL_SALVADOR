<?php

namespace App\Services;

use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetupService
{
    public function isRequired(): bool
    {
        return DB::table('EMPRESA')->count() === 0;
    }

    public function status(): array
    {
        $empresas = (int) DB::table('EMPRESA')->count();
        $usuarios = (int) DB::table('USUARIO')->where('ESACTIVO', true)->count();

        return [
            'setup_required' => $empresas === 0,
            'empresas' => $empresas,
            'usuarios_activos' => $usuarios,
        ];
    }

    public function complete(array $data): array
    {
        if (!$this->isRequired()) {
            throw new \RuntimeException('La configuración inicial ya fue completada.');
        }

        if (DB::table('USUARIO')->where('USUARIO', $data['USUARIO'])->exists()) {
            throw new \InvalidArgumentException('El nombre de usuario ya existe.');
        }

        return DB::transaction(function () use ($data) {
            $empresaId = $this->crearEmpresa($data);
            $this->crearEstructuraMinima($empresaId);
            $cuentaId = $this->crearCuentaBancaria($empresaId, $data['NOMBREEMPRESA']);
            $usuarioId = $this->crearUsuarioAdmin($data);

            return [
                'ID_EMPRESA' => $empresaId,
                'ID_USUARIO' => $usuarioId,
                'ID_CUENTA' => $cuentaId,
                'message' => 'Configuración inicial completada. Ya puede iniciar sesión.',
            ];
        });
    }

    protected function crearEmpresa(array $data): int
    {
        $maxId = DB::table('EMPRESA')->max('ID_EMPRESA') ?? 0;
        $id = $maxId + 1;

        DB::table('EMPRESA')->insert([
            'ID_EMPRESA' => $id,
            'NOMBREEMPRESA' => $data['NOMBREEMPRESA'],
            'ABREVIATURA' => $data['ABREVIATURA'] ?? null,
            'NUMERONIT' => $data['NUMERONIT'] ?? null,
            'DIRECCION' => $data['DIRECCION'] ?? null,
            'TELEFONO' => $data['TELEFONO'] ?? null,
            'GIRO' => $data['GIRO'] ?? null,
            'EMPRESAACTIVA' => true,
        ]);

        return $id;
    }

    protected function crearEstructuraMinima(int $empresaId): void
    {
        $ccId = (DB::table('CENTRO_COSTO')->max('ID_CENTROCOSTO') ?? 0) + 1;
        DB::table('CENTRO_COSTO')->insert([
            'ID_CENTROCOSTO' => $ccId,
            'ID_EMPRESA' => $empresaId,
            'CODIGO_CENTROCOSTO' => 'CC-001',
            'NOMBRE_CENTROCOSTO' => 'Administración',
            'DESCRIPCION' => 'Centro de costo principal',
            'ESACTIVO' => true,
        ]);

        $areaId = (DB::table('AREA')->max('ID_AREA') ?? 0) + 1;
        DB::table('AREA')->insert([
            'ID_AREA' => $areaId,
            'ID_EMPRESA' => $empresaId,
            'NOMBREAREA' => 'Administración',
            'ACTIVA' => true,
            'PRORRATEADA' => false,
        ]);

        $deptoId = (DB::table('DEPARTAMENTO')->max('ID_DEPARTAMENTO') ?? 0) + 1;
        DB::table('DEPARTAMENTO')->insert([
            'ID_DEPARTAMENTO' => $deptoId,
            'ID_EMPRESA' => $empresaId,
            'ID_AREA' => $areaId,
            'ID_CENTROCOSTO' => $ccId,
            'NOMBREDEPARTAMENTO' => 'Recursos Humanos',
        ]);

        $cargoId = (DB::table('CARGO')->max('ID_CARGO') ?? 0) + 1;
        DB::table('CARGO')->insert([
            'ID_CARGO' => $cargoId,
            'ID_DEPARTAMENTO' => $deptoId,
            'ID_CENTROCOSTO' => $ccId,
            'NOMBRECARGO' => 'Administrador',
            'CARGOESTADO' => true,
        ]);
    }

    protected function crearCuentaBancaria(int $empresaId, string $nombreEmpresa): int
    {
        $maxId = DB::table('CUENTA')->max('ID_CUENTA') ?? 0;
        $id = $maxId + 1;

        DB::table('CUENTA')->insert([
            'ID_CUENTA' => $id,
            'CONCEPTOCUENTA' => 'Cuenta planilla — ' . $nombreEmpresa,
            'NUMEROCUENTA' => '0000000000',
            'ESACTIVO' => true,
        ]);

        return $id;
    }

    protected function crearUsuarioAdmin(array $data): int
    {
        $this->ensureRbac();

        $maxId = DB::table('USUARIO')->max('ID_USUARIO') ?? 0;
        $id = $maxId + 1;

        DB::table('USUARIO')->insert([
            'ID_USUARIO' => $id,
            'ID_EMPLEADO' => null,
            'USUARIO' => $data['USUARIO'],
            'CONTRASENA_HASH' => Hash::make($data['CONTRASENA']),
            'EMAIL' => $data['EMAIL'] ?? $data['USUARIO'],
            'ESACTIVO' => true,
            'BLOQUEADO' => false,
        ]);

        DB::table('USUARIO_ROL')->insert([
            'ID_USUARIO' => $id,
            'ID_ROL' => 1,
        ]);

        foreach (RbacSeeder::permisos() as $perm) {
            DB::table('USUARIO_PERMISO')->insert([
                'ID_USUARIO' => $id,
                'ID_PERMISO' => $perm['ID_PERMISO'],
                'ES_CONCEDIDO' => true,
                'USUARIO_ASIGNO' => 'SETUP',
            ]);
        }

        return $id;
    }

    protected function ensureRbac(): void
    {
        (new RbacSeeder())->run();
    }
}
