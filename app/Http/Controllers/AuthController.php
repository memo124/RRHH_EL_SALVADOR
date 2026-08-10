<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión y obtener token Bearer Sanctum.
     *
     * @unauthenticated
     */
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
                'id_empleado' => $user->ID_EMPLEADO,
                'is_employee_portal' => $this->isEmployeePortalUser($user, $permissions),
                'permissions' => $permissions,
                'menu' => $this->buildMenu($permissions, $user->ID_EMPLEADO)
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
            'id_empleado' => $user->ID_EMPLEADO,
            'is_employee_portal' => $this->isEmployeePortalUser($user, $permissions),
            'permissions' => $permissions,
            'menu' => $this->buildMenu($permissions, $user->ID_EMPLEADO)
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

    /**
     * Determina si el usuario debe operar bajo el Portal Empleado (autoservicio):
     * requiere ID_EMPLEADO vinculado y permisos PORTAL_* sin acceso administrativo (SALARIAL_VIEW).
     */
    private function isEmployeePortalUser(Usuario $user, $permissions): bool
    {
        if (!$user->ID_EMPLEADO) {
            return false;
        }

        $hasPortalAccess = $permissions->contains(fn ($codigo) => str_starts_with($codigo, 'PORTAL_'));
        $hasAdminAccess = $permissions->contains('SALARIAL_VIEW');

        return $hasPortalAccess && !$hasAdminAccess;
    }

    private function buildMenu($permissions, $idEmpleado = null)
    {
        $menu = [];

        if ($permissions->contains('PORTAL_VIEW') && $idEmpleado) {
            $menu[] = [
                'group' => 'Mi portal',
                'icon' => 'user',
                'options' => [
                    ['name' => 'Inicio', 'route' => '/portal'],
                    ['name' => 'Mis boletas de pago', 'route' => '/portal/boletas'],
                    ['name' => 'Permisos y vacaciones', 'route' => '/portal/permisos'],
                    ['name' => 'Encuestas', 'route' => '/portal/encuestas'],
                    ['name' => 'Evaluación de desempeño', 'route' => '/portal/evaluaciones'],
                    ['name' => 'Mi perfil', 'route' => '/portal/perfil'],
                ]
            ];
        }

        if ($permissions->contains('GEOGRAFIA_VIEW')) {
            $menu[] = [
                'group' => 'Geográfico',
                'icon' => 'globe',
                'options' => [
                    ['name' => 'Geografía', 'route' => '/geografia']
                ]
            ];
        }
        if ($permissions->contains('MH_VIEW')) {
            $menu[] = [
                'group' => 'Catálogos MH',
                'icon' => 'file-text',
                'options' => [
                    ['name' => 'Documentos de Identidad', 'route' => '/catalogos-mh']
                ]
            ];
        }
        if ($permissions->contains('CORP_VIEW')) {
            $menu[] = [
                'group' => 'Corporativo',
                'icon' => 'building',
                'options' => [
                    ['name' => 'Estructura Corporativa', 'route' => '/corporativo'],
                    ['name' => 'Catálogos RRHH', 'route' => '/catalogo-rrhh']
                ]
            ];
        }
        if ($permissions->contains('SALARIAL_VIEW')) {
            $menu[] = [
                'group' => 'Planilla y Nómina',
                'icon' => 'banknote',
                'options' => [
                    ['name' => 'Cálculo de Planilla', 'route' => '/planilla'],
                    ['name' => 'Periodos Laborales', 'route' => '/periodos'],
                    ['name' => 'Liquidaciones', 'route' => '/liquidaciones'],
                    ['name' => 'Parámetros Aguinaldo', 'route' => '/parametros-aguinaldo'],
                ]
            ];
            $menu[] = [
                'group' => 'Empleados',
                'icon' => 'users',
                'options' => [
                    ['name' => 'Empleados', 'route' => '/empleados'],
                    ['name' => 'Tipos de Contratación', 'route' => '/tipo-contratacion'],
                ]
            ];
        }
        if ($permissions->contains('SALARIAL_VIEW')) {
            $menu[] = [
                'group' => 'Cumplimiento SV',
                'icon' => 'landmark',
                'options' => [
                    ['name' => 'Planilla ISSS', 'route' => '/cumplimiento/isss'],
                    ['name' => 'Planilla AFP', 'route' => '/cumplimiento/afp'],
                    ['name' => 'INSAFORP', 'route' => '/cumplimiento/insaforp'],
                    ['name' => 'F-14 / Renta retenida MH', 'route' => '/cumplimiento/renta'],
                    ['name' => 'Altas y bajas ISSS', 'route' => '/cumplimiento/isss-movimientos'],
                    ['name' => 'Aguinaldo (corrida)', 'route' => '/aguinaldo'],
                    ['name' => 'Retención 10% servicios profesionales', 'route' => '/cumplimiento/retencion10'],
                ]
            ];
        }
        if ($permissions->contains('CONTRATO_VIEW')) {
            $menu[] = [
                'group' => 'Contratos Laborales',
                'icon' => 'file-text',
                'options' => [
                    ['name' => 'Contratos', 'route' => '/contratos'],
                    ['name' => 'Plantillas de Contrato', 'route' => '/contratos/plantillas'],
                ]
            ];
        }
        if ($permissions->contains('ASISTENCIA_VIEW') || $permissions->contains('SALARIAL_VIEW')) {
            $menu[] = [
                'group' => 'Asistencia y Horas Extras',
                'icon' => 'clock',
                'options' => [
                    ['name' => 'Horarios', 'route' => '/horarios'],
                    ['name' => 'Asistencia y Marcaciones', 'route' => '/asistencia'],
                ]
            ];
        }
        if ($permissions->contains('DEDUCCIONES_VIEW')) {
            $menu[] = [
                'group' => 'Ingresos, Descuentos y Préstamos',
                'icon' => 'calculator',
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
                'icon' => 'shield',
                'options' => [
                    ['name' => 'Control de Acceso', 'route' => '/seguridad']
                ]
            ];
        }
        if ($permissions->contains('GESTION_HUMANA_VIEW')) {
            $menu[] = [
                'group' => 'Gestión Humana',
                'icon' => 'heart-handshake',
                'options' => [
                    ['name' => 'Calendario', 'route' => '/calendario'],
                    ['name' => 'Encuestas', 'route' => '/encuestas'],
                    ['name' => 'Formularios / Actualización de datos', 'route' => '/formularios-empleado'],
                    ['name' => 'Documentos del empleado', 'route' => '/documentos-empleado'],
                    ['name' => 'Vacaciones y permisos', 'route' => '/vacaciones-permisos'],
                    ['name' => 'Capacitaciones', 'route' => '/capacitaciones'],
                    ['name' => 'Reclutamiento', 'route' => '/reclutamiento'],
                    ['name' => 'Evaluación de desempeño', 'route' => '/evaluaciones'],
                    ['name' => 'Actividades económicas MH', 'route' => '/actividades-economicas'],
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
