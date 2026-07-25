<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'contrasena' => 'required|string',
        ]);

        $user = Usuario::where('USUARIO', $request->usuario)->first();

        if (!$user || !Hash::check($request->contrasena, $user->CONTRASENA_HASH)) {
            throw ValidationException::withMessages([
                'usuario' => ['Credenciales incorrectas.'],
            ]);
        }

        if (!$user->ESACTIVO) {
            return response()->json(['error' => 'Usuario inactivo.'], 403);
        }

        if ($user->BLOQUEADO) {
            return response()->json(['error' => 'Usuario bloqueado.'], 403);
        }

        // Update last access timestamp
        $user->FECHAULTIMOACCESO = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Fetch direct permissions
        $directPermissions = DB::table('USUARIO_PERMISO')
            ->join('PERMISO', 'USUARIO_PERMISO.ID_PERMISO', '=', 'PERMISO.ID_PERMISO')
            ->where('USUARIO_PERMISO.ID_USUARIO', $user->ID_USUARIO)
            ->where('USUARIO_PERMISO.ES_CONCEDIDO', true)
            ->pluck('PERMISO.CODIGO_PERMISO');

        // Fetch role permissions
        $rolePermissions = DB::table('USUARIO_ROL')
            ->join('ROL_PERMISO', 'USUARIO_ROL.ID_ROL', '=', 'ROL_PERMISO.ID_ROL')
            ->join('PERMISO', 'ROL_PERMISO.ID_PERMISO', '=', 'PERMISO.ID_PERMISO')
            ->where('USUARIO_ROL.ID_USUARIO', $user->ID_USUARIO)
            ->pluck('PERMISO.CODIGO_PERMISO');

        $permissions = $directPermissions->concat($rolePermissions)->unique()->values();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->ID_USUARIO,
                'username' => $user->USUARIO,
                'email' => $user->EMAIL,
                'theme' => $user->TEMA ?? 'auto',
                'permissions' => $permissions,
                'menu' => $this->buildMenu($permissions)
            ]
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        // Fetch direct permissions
        $directPermissions = DB::table('USUARIO_PERMISO')
            ->join('PERMISO', 'USUARIO_PERMISO.ID_PERMISO', '=', 'PERMISO.ID_PERMISO')
            ->where('USUARIO_PERMISO.ID_USUARIO', $user->ID_USUARIO)
            ->where('USUARIO_PERMISO.ES_CONCEDIDO', true)
            ->pluck('PERMISO.CODIGO_PERMISO');

        // Fetch role permissions
        $rolePermissions = DB::table('USUARIO_ROL')
            ->join('ROL_PERMISO', 'USUARIO_ROL.ID_ROL', '=', 'ROL_PERMISO.ID_ROL')
            ->join('PERMISO', 'ROL_PERMISO.ID_PERMISO', '=', 'PERMISO.ID_PERMISO')
            ->where('USUARIO_ROL.ID_USUARIO', $user->ID_USUARIO)
            ->pluck('PERMISO.CODIGO_PERMISO');

        $permissions = $directPermissions->concat($rolePermissions)->unique()->values();

        return response()->json([
            'id' => $user->ID_USUARIO,
            'username' => $user->USUARIO,
            'email' => $user->EMAIL,
            'theme' => $user->TEMA ?? 'auto',
            'permissions' => $permissions,
            'menu' => $this->buildMenu($permissions)
        ]);
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:light,dark,auto,system',
        ]);

        $user = $request->user();
        $user->TEMA = $request->theme;
        $user->save();

        return response()->json(['theme' => $user->TEMA]);
    }

    private function buildMenu($permissions)
    {
        $menu = [];
        if ($permissions->contains('GEOGRAFIA_VIEW')) {
            $menu[] = [
                'group' => 'Geográfico',
                'icon' => '🌐',
                'options' => [
                    ['name' => 'Geografía', 'route' => '/geografia']
                ]
            ];
        }
        if ($permissions->contains('MH_VIEW')) {
            $menu[] = [
                'group' => 'Catálogos MH',
                'icon' => '📄',
                'options' => [
                    ['name' => 'Documentos de Identidad', 'route' => '/catalogos-mh']
                ]
            ];
        }
        if ($permissions->contains('CORP_VIEW')) {
            $menu[] = [
                'group' => 'Corporativo',
                'icon' => '🏢',
                'options' => [
                    ['name' => 'Estructura Corporativa', 'route' => '/corporativo'],
                    ['name' => 'Catálogos RRHH', 'route' => '/catalogo-rrhh']
                ]
            ];
        }
        if ($permissions->contains('SALARIAL_VIEW')) {
            $menu[] = [
                'group' => 'Planilla y Nómina',
                'icon' => '💵',
                'options' => [
                    ['name' => 'Cálculo de Planilla', 'route' => '/planilla'],
                    ['name' => 'Periodos Laborales', 'route' => '/periodos'],
                    ['name' => 'Liquidaciones', 'route' => '/liquidaciones'],
                    ['name' => 'Parámetros Aguinaldo', 'route' => '/parametros-aguinaldo'],
                ]
            ];
            $menu[] = [
                'group' => 'Empleados y Contratos',
                'icon' => '👥',
                'options' => [
                    ['name' => 'Empleados', 'route' => '/empleados'],
                    ['name' => 'Tipos de Contratación', 'route' => '/tipo-contratacion'],
                ]
            ];
        }
        if ($permissions->contains('ASISTENCIA_VIEW') || $permissions->contains('SALARIAL_VIEW')) {
            $menu[] = [
                'group' => 'Asistencia y Horas Extras',
                'icon' => '⏰',
                'options' => [
                    ['name' => 'Horarios', 'route' => '/horarios'],
                    ['name' => 'Asistencia y Marcaciones', 'route' => '/asistencia'],
                ]
            ];
        }
        if ($permissions->contains('DEDUCCIONES_VIEW')) {
            $menu[] = [
                'group' => 'Ingresos, Descuentos y Préstamos',
                'icon' => '🧮',
                'options' => [
                    ['name' => 'Conceptos por Empleado (comisiones, préstamos, descuentos)', 'route' => '/conceptos-empleado'],
                    ['name' => 'Catálogo Tipos de Ingreso/Descuento', 'route' => '/deducciones'],
                    ['name' => 'Incapacidades ISSS', 'route' => '/incapacidades'],
                ]
            ];
        }
        if ($permissions->contains('SEGURIDAD_VIEW')) {
            $menu[] = [
                'group' => 'Seguridad',
                'icon' => '🛡️',
                'options' => [
                    ['name' => 'Control de Acceso', 'route' => '/seguridad']
                ]
            ];
        }
        return $menu;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
