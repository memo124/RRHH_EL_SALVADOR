<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeguridadController extends Controller
{
    // ─── ROLES ────────────────────────────────────────────────────────────────

    public function indexRoles()
    {
        $roles = DB::table('ROL')->orderBy('ID_ROL')->get();
        foreach ($roles as $r) {
            $r->permisos = DB::table('ROL_PERMISO')
                ->where('ID_ROL', $r->ID_ROL)
                ->pluck('ID_PERMISO');
        }
        return response()->json($roles);
    }

    public function storeRol(Request $request)
    {
        $request->validate([
            'NOMBREROL'  => 'required|string|max:50|unique:ROL,NOMBREROL',
            'DESCRIPCION' => 'nullable|string|max:250',
            'ESACTIVO'   => 'boolean',
            'PERMISOS'   => 'array',
        ]);

        $maxId = DB::table('ROL')->max('ID_ROL') ?? 0;
        $id    = $maxId + 1;

        DB::table('ROL')->insert([
            'ID_ROL'      => $id,
            'NOMBREROL'   => $request->NOMBREROL,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ESACTIVO'    => $request->ESACTIVO ?? true,
        ]);

        if ($request->has('PERMISOS')) {
            foreach ($request->PERMISOS as $permId) {
                DB::table('ROL_PERMISO')->insert([
                    'ID_ROL'     => $id,
                    'ID_PERMISO' => $permId,
                ]);
            }
        }

        return response()->json(['ID_ROL' => $id, 'NOMBREROL' => $request->NOMBREROL], 201);
    }

    public function updateRol(Request $request, $id)
    {
        $request->validate([
            'NOMBREROL'   => 'required|string|max:50|unique:ROL,NOMBREROL,' . $id . ',ID_ROL',
            'DESCRIPCION' => 'nullable|string|max:250',
            'ESACTIVO'    => 'required|boolean',
            'PERMISOS'    => 'array',
        ]);

        DB::table('ROL')->where('ID_ROL', $id)->update([
            'NOMBREROL'   => $request->NOMBREROL,
            'DESCRIPCION' => $request->DESCRIPCION,
            'ESACTIVO'    => $request->ESACTIVO,
        ]);

        if ($request->has('PERMISOS')) {
            DB::table('ROL_PERMISO')->where('ID_ROL', $id)->delete();
            foreach ($request->PERMISOS as $permId) {
                DB::table('ROL_PERMISO')->insert([
                    'ID_ROL'     => $id,
                    'ID_PERMISO' => $permId,
                ]);
            }
        }

        return response()->json(['message' => 'Rol actualizado correctamente.']);
    }

    public function destroyRol($id)
    {
        DB::table('ROL')->where('ID_ROL', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Rol inactivado correctamente.']);
    }

    // ─── ROL_PERMISO (assign permissions to a role) ───────────────────────────

    public function getRolPermisos($id)
    {
        $permisos = DB::table('ROL_PERMISO')
            ->where('ID_ROL', $id)
            ->pluck('ID_PERMISO');
        return response()->json($permisos);
    }

    public function updateRolPermisos(Request $request, $id)
    {
        $request->validate([
            'permisos' => 'required|array',
        ]);

        DB::table('ROL_PERMISO')->where('ID_ROL', $id)->delete();

        foreach ($request->permisos as $permId) {
            DB::table('ROL_PERMISO')->insert([
                'ID_ROL'     => $id,
                'ID_PERMISO' => $permId,
            ]);
        }

        return response()->json(['message' => 'Permisos del rol actualizados correctamente.']);
    }

    // ─── USUARIOS ─────────────────────────────────────────────────────────────

    public function indexUsuarios()
    {
        $usuarios = DB::table('USUARIO')
            ->select('ID_USUARIO', 'USUARIO', 'EMAIL', 'ESACTIVO', 'BLOQUEADO', 'ID_EMPLEADO', 'TEMA')
            ->orderBy('ID_USUARIO')
            ->get();

        foreach ($usuarios as $u) {
            $u->permissions = DB::table('USUARIO_PERMISO')
                ->where('ID_USUARIO', $u->ID_USUARIO)
                ->where('ES_CONCEDIDO', true)
                ->pluck('ID_PERMISO');

            $u->roles = DB::table('USUARIO_ROL')
                ->where('ID_USUARIO', $u->ID_USUARIO)
                ->pluck('ID_ROL');
        }

        return response()->json($usuarios);
    }

    public function storeUsuario(Request $request)
    {
        $request->validate([
            'USUARIO'     => 'required|string|max:50|unique:USUARIO,USUARIO',
            'EMAIL'       => 'required|email|max:100|unique:USUARIO,EMAIL',
            'CONTRASENA'  => 'required|string|min:6',
            'ID_EMPLEADO' => 'nullable|integer',
            'ROLES'       => 'required|array',
        ]);

        $maxId = DB::table('USUARIO')->max('ID_USUARIO') ?? 0;
        $id    = $maxId + 1;

        DB::table('USUARIO')->insert([
            'ID_USUARIO'      => $id,
            'ID_EMPLEADO'     => $request->ID_EMPLEADO,
            'USUARIO'         => $request->USUARIO,
            'CONTRASENA_HASH' => Hash::make($request->CONTRASENA),
            'EMAIL'           => $request->EMAIL,
            'ESACTIVO'        => true,
            'BLOQUEADO'       => false,
        ]);

        foreach ($request->ROLES as $rolId) {
            DB::table('USUARIO_ROL')->insert([
                'ID_USUARIO' => $id,
                'ID_ROL'     => $rolId
            ]);
        }

        return response()->json(['ID_USUARIO' => $id, 'message' => 'Usuario creado correctamente.'], 201);
    }

    public function updateUsuario(Request $request, $id)
    {
        $request->validate([
            'USUARIO'     => 'required|string|max:50|unique:USUARIO,USUARIO,' . $id . ',ID_USUARIO',
            'EMAIL'       => 'required|email|max:100|unique:USUARIO,EMAIL,' . $id . ',ID_USUARIO',
            'CONTRASENA'  => 'nullable|string|min:6',
            'ID_EMPLEADO' => 'nullable|integer',
            'ESACTIVO'    => 'required|boolean',
            'BLOQUEADO'   => 'required|boolean',
            'ROLES'       => 'required|array',
        ]);

        $data = [
            'ID_EMPLEADO' => $request->ID_EMPLEADO,
            'USUARIO'     => $request->USUARIO,
            'EMAIL'       => $request->EMAIL,
            'ESACTIVO'    => $request->ESACTIVO,
            'BLOQUEADO'   => $request->BLOQUEADO,
        ];

        if ($request->CONTRASENA) {
            $data['CONTRASENA_HASH'] = Hash::make($request->CONTRASENA);
        }

        DB::table('USUARIO')->where('ID_USUARIO', $id)->update($data);

        DB::table('USUARIO_ROL')->where('ID_USUARIO', $id)->delete();
        foreach ($request->ROLES as $rolId) {
            DB::table('USUARIO_ROL')->insert([
                'ID_USUARIO' => $id,
                'ID_ROL'     => $rolId
            ]);
        }

        return response()->json(['message' => 'Usuario actualizado correctamente.']);
    }

    public function destroyUsuario($id)
    {
        DB::table('USUARIO')->where('ID_USUARIO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Usuario inactivado correctamente.']);
    }

    // ─── PERMISOS ─────────────────────────────────────────────────────────────

    public function indexPermisos()
    {
        return response()->json(DB::table('PERMISO')->orderBy('ID_PERMISO')->get());
    }

    public function updateUsuarioPermisos(Request $request, $id)
    {
        $request->validate([
            'permisos' => 'required|array',
        ]);

        DB::table('USUARIO_PERMISO')->where('ID_USUARIO', $id)->delete();

        foreach ($request->permisos as $permId) {
            DB::table('USUARIO_PERMISO')->insert([
                'ID_USUARIO'    => $id,
                'ID_PERMISO'    => $permId,
                'ES_CONCEDIDO'  => true,
                'USUARIO_ASIGNO' => 'ADMIN'
            ]);
        }

        return response()->json(['message' => 'Matriz de permisos de usuario actualizada correctamente.']);
    }
}
