<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TipoContratacionController;
use App\Http\Controllers\GeografiaController;
use App\Http\Controllers\CatalogosMhController;
use App\Http\Controllers\CorporativoController;
use App\Http\Controllers\DeduccionesController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\CatalogoRRHHController;

// Public Auth routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',   [AuthController::class, 'user']);
    Route::put('/user/theme', [AuthController::class, 'updateTheme']);
    Route::post('/logout',[AuthController::class, 'logout']);

    Route::get('/catalogs/{type}/select', [App\Http\Controllers\CatalogSelectController::class, 'select']);

    // ── Tipo Contratación ──────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/tipo-contratacion',     [TipoContratacionController::class, 'index']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/tipo-contratacion',     [TipoContratacionController::class, 'store']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/tipo-contratacion/{id}',[TipoContratacionController::class, 'update']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/tipo-contratacion/{id}',[TipoContratacionController::class, 'destroy']);

    // ── Geografía ─────────────────────────────────────────────────────────────
    Route::middleware('permission:GEOGRAFIA_VIEW')  ->get   ('/paises',     [GeografiaController::class, 'index']);
    Route::middleware('permission:GEOGRAFIA_CREATE')->post  ('/paises',     [GeografiaController::class, 'store']);
    Route::middleware('permission:GEOGRAFIA_UPDATE')->put   ('/paises/{id}',[GeografiaController::class, 'update']);
    Route::middleware('permission:GEOGRAFIA_DELETE')->delete('/paises/{id}',[GeografiaController::class, 'destroy']);

    // ── Catálogos MH ──────────────────────────────────────────────────────────
    Route::middleware('permission:MH_VIEW')  ->get   ('/tipo-documento',     [CatalogosMhController::class, 'index']);
    Route::middleware('permission:MH_CREATE')->post  ('/tipo-documento',     [CatalogosMhController::class, 'store']);
    Route::middleware('permission:MH_UPDATE')->put   ('/tipo-documento/{id}',[CatalogosMhController::class, 'update']);
    Route::middleware('permission:MH_DELETE')->delete('/tipo-documento/{id}',[CatalogosMhController::class, 'destroy']);

    // ── Corporativo – Empresas ─────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/empresas',     [CorporativoController::class, 'indexEmpresas']);
    Route::middleware('permission:CORP_CREATE')->post  ('/empresas',     [CorporativoController::class, 'storeEmpresa']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/empresas/{id}',[CorporativoController::class, 'updateEmpresa']);
    Route::middleware('permission:CORP_UPDATE')->post  ('/empresas/{id}/logo', [CorporativoController::class, 'uploadEmpresaLogo']);
    Route::middleware('permission:CORP_DELETE')->delete('/empresas/{id}',[CorporativoController::class, 'destroyEmpresa']);

    // ── Corporativo – Áreas ───────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/areas',     [CorporativoController::class, 'indexAreas']);
    Route::middleware('permission:CORP_CREATE')->post  ('/areas',     [CorporativoController::class, 'storeArea']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/areas/{id}',[CorporativoController::class, 'updateArea']);
    Route::middleware('permission:CORP_DELETE')->delete('/areas/{id}',[CorporativoController::class, 'destroyArea']);

    // ── Corporativo – Centros de Costo ────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/centros-costo',     [CorporativoController::class, 'indexCentrosCosto']);
    Route::middleware('permission:CORP_CREATE')->post  ('/centros-costo',     [CorporativoController::class, 'storeCentroCosto']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/centros-costo/{id}',[CorporativoController::class, 'updateCentroCosto']);
    Route::middleware('permission:CORP_DELETE')->delete('/centros-costo/{id}',[CorporativoController::class, 'destroyCentroCosto']);

    // ── Corporativo – Departamentos ───────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/departamentos',     [CorporativoController::class, 'indexDepartamentos']);
    Route::middleware('permission:CORP_CREATE')->post  ('/departamentos',     [CorporativoController::class, 'storeDepartamento']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/departamentos/{id}',[CorporativoController::class, 'updateDepartamento']);
    Route::middleware('permission:CORP_DELETE')->delete('/departamentos/{id}',[CorporativoController::class, 'destroyDepartamento']);

    // ── Corporativo – Cargos ──────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/cargos',     [CorporativoController::class, 'indexCargos']);
    Route::middleware('permission:CORP_CREATE')->post  ('/cargos',     [CorporativoController::class, 'storeCargo']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/cargos/{id}',[CorporativoController::class, 'updateCargo']);
    Route::middleware('permission:CORP_DELETE')->delete('/cargos/{id}',[CorporativoController::class, 'destroyCargo']);

    // ── Corporativo – Sucursales ──────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/sucursales',     [CorporativoController::class, 'indexSucursales']);
    Route::middleware('permission:CORP_CREATE')->post  ('/sucursales',     [CorporativoController::class, 'storeSucursal']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/sucursales/{id}',[CorporativoController::class, 'updateSucursal']);
    Route::middleware('permission:CORP_DELETE')->delete('/sucursales/{id}',[CorporativoController::class, 'destroySucursal']);

    // ── Corporativo – Bodegas ─────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/bodegas',     [CorporativoController::class, 'indexBodegas']);
    Route::middleware('permission:CORP_CREATE')->post  ('/bodegas',     [CorporativoController::class, 'storeBodega']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/bodegas/{id}',[CorporativoController::class, 'updateBodega']);
    Route::middleware('permission:CORP_DELETE')->delete('/bodegas/{id}',[CorporativoController::class, 'destroyBodega']);

    // ── Corporativo – Rutas ───────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/rutas',     [CorporativoController::class, 'indexRutas']);
    Route::middleware('permission:CORP_CREATE')->post  ('/rutas',     [CorporativoController::class, 'storeRuta']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/rutas/{id}',[CorporativoController::class, 'updateRuta']);
    Route::middleware('permission:CORP_DELETE')->delete('/rutas/{id}',[CorporativoController::class, 'destroyRuta']);

    // ── Deducciones – Tipo Descuento ──────────────────────────────────────────
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/tipo-descuento',     [DeduccionesController::class, 'indexDescuentos']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/tipo-descuento',     [DeduccionesController::class, 'storeDescuento']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/tipo-descuento/{id}',[DeduccionesController::class, 'updateDescuento']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/tipo-descuento/{id}',[DeduccionesController::class, 'destroyDescuento']);

    // ── Deducciones – Tipo Ingreso ────────────────────────────────────────────
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/tipo-ingreso',     [DeduccionesController::class, 'indexIngresos']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/tipo-ingreso',     [DeduccionesController::class, 'storeIngreso']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/tipo-ingreso/{id}',[DeduccionesController::class, 'updateIngreso']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/tipo-ingreso/{id}',[DeduccionesController::class, 'destroyIngreso']);

    // ── Conceptos por Empleado (Préstamos, Descuentos, Ingresos) ──────────────
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/conceptos-empleado/catalogs', [App\Http\Controllers\ConceptosEmpleadoController::class, 'catalogs']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/prestamos',                   [App\Http\Controllers\PrestamosController::class, 'index']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/prestamos/{id}',              [App\Http\Controllers\PrestamosController::class, 'show']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/prestamos',                   [App\Http\Controllers\PrestamosController::class, 'store']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/prestamos/{id}',              [App\Http\Controllers\PrestamosController::class, 'update']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->delete('/prestamos/{id}/abonos/{abonoId}', [App\Http\Controllers\PrestamosController::class, 'destroyAbono']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/prestamos/{id}',              [App\Http\Controllers\PrestamosController::class, 'destroy']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/descuentos-empleado',         [App\Http\Controllers\DescuentoEmpleadoController::class, 'index']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/descuentos-empleado/{id}/historial', [App\Http\Controllers\DescuentoEmpleadoController::class, 'historial']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/descuentos-empleado',         [App\Http\Controllers\DescuentoEmpleadoController::class, 'store']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/descuentos-empleado/{id}',    [App\Http\Controllers\DescuentoEmpleadoController::class, 'update']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/descuentos-empleado/{id}',    [App\Http\Controllers\DescuentoEmpleadoController::class, 'destroy']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/otros-ingresos',              [App\Http\Controllers\OtroIngresoController::class, 'index']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/otros-ingresos/{id}/historial', [App\Http\Controllers\OtroIngresoController::class, 'historial']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/otros-ingresos',              [App\Http\Controllers\OtroIngresoController::class, 'store']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/otros-ingresos/{id}',         [App\Http\Controllers\OtroIngresoController::class, 'update']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/otros-ingresos/{id}',          [App\Http\Controllers\OtroIngresoController::class, 'destroy']);

    // ── Seguridad – Roles ─────────────────────────────────────────────────────
    Route::middleware('permission:SEGURIDAD_VIEW')  ->get   ('/roles',           [SeguridadController::class, 'indexRoles']);
    Route::middleware('permission:SEGURIDAD_CREATE')->post  ('/roles',           [SeguridadController::class, 'storeRol']);
    Route::middleware('permission:SEGURIDAD_UPDATE')->put   ('/roles/{id}',      [SeguridadController::class, 'updateRol']);
    Route::middleware('permission:SEGURIDAD_DELETE')->delete('/roles/{id}',      [SeguridadController::class, 'destroyRol']);
    Route::middleware('permission:SEGURIDAD_VIEW')  ->get   ('/roles/{id}/permisos',  [SeguridadController::class, 'getRolPermisos']);
    Route::middleware('permission:SEGURIDAD_UPDATE')->put   ('/roles/{id}/permisos',  [SeguridadController::class, 'updateRolPermisos']);

    // ── Seguridad – Usuarios ──────────────────────────────────────────────────
    Route::middleware('permission:SEGURIDAD_VIEW')  ->get   ('/usuarios',              [SeguridadController::class, 'indexUsuarios']);
    Route::middleware('permission:SEGURIDAD_VIEW')  ->get   ('/permisos-list',         [SeguridadController::class, 'indexPermisos']);
    Route::middleware('permission:SEGURIDAD_CREATE')->post  ('/usuarios',              [SeguridadController::class, 'storeUsuario']);
    Route::middleware('permission:SEGURIDAD_UPDATE')->put   ('/usuarios/{id}',         [SeguridadController::class, 'updateUsuario']);
    Route::middleware('permission:SEGURIDAD_DELETE')->delete('/usuarios/{id}',         [SeguridadController::class, 'destroyUsuario']);
    Route::middleware('permission:SEGURIDAD_UPDATE')->put   ('/usuarios/{id}/permisos',[SeguridadController::class, 'updateUsuarioPermisos']);

    // ── Catálogos RRHH – AFP ──────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/afp',     [CatalogoRRHHController::class, 'indexAfp']);
    Route::middleware('permission:CORP_CREATE')->post  ('/afp',     [CatalogoRRHHController::class, 'storeAfp']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/afp/{id}',[CatalogoRRHHController::class, 'updateAfp']);
    Route::middleware('permission:CORP_DELETE')->delete('/afp/{id}',[CatalogoRRHHController::class, 'destroyAfp']);

    // ── Catálogos RRHH – Banco ────────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/bancos',     [CatalogoRRHHController::class, 'indexBancos']);
    Route::middleware('permission:CORP_CREATE')->post  ('/bancos',     [CatalogoRRHHController::class, 'storeBanco']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/bancos/{id}',[CatalogoRRHHController::class, 'updateBanco']);
    Route::middleware('permission:CORP_DELETE')->delete('/bancos/{id}',[CatalogoRRHHController::class, 'destroyBanco']);

    // ── Catálogos RRHH – Estado Civil ─────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/estado-civil',     [CatalogoRRHHController::class, 'indexEstadoCivil']);
    Route::middleware('permission:CORP_CREATE')->post  ('/estado-civil',     [CatalogoRRHHController::class, 'storeEstadoCivil']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/estado-civil/{id}',[CatalogoRRHHController::class, 'updateEstadoCivil']);
    Route::middleware('permission:CORP_DELETE')->delete('/estado-civil/{id}',[CatalogoRRHHController::class, 'destroyEstadoCivil']);

    // ── Catálogos RRHH – Educación Académica ──────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/educacion',     [CatalogoRRHHController::class, 'indexEducacion']);
    Route::middleware('permission:CORP_CREATE')->post  ('/educacion',     [CatalogoRRHHController::class, 'storeEducacion']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/educacion/{id}',[CatalogoRRHHController::class, 'updateEducacion']);
    Route::middleware('permission:CORP_DELETE')->delete('/educacion/{id}',[CatalogoRRHHController::class, 'destroyEducacion']);

    // ── Catálogos RRHH – Profesiones/Oficios ──────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/profesiones',     [CatalogoRRHHController::class, 'indexProfesiones']);
    Route::middleware('permission:CORP_CREATE')->post  ('/profesiones',     [CatalogoRRHHController::class, 'storeProfesion']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/profesiones/{id}',[CatalogoRRHHController::class, 'updateProfesion']);
    Route::middleware('permission:CORP_DELETE')->delete('/profesiones/{id}',[CatalogoRRHHController::class, 'destroyProfesion']);

    // ── Catálogos RRHH – Perfil de Pago ──────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/perfil-pago',     [CatalogoRRHHController::class, 'indexPerfilPago']);
    Route::middleware('permission:CORP_CREATE')->post  ('/perfil-pago',     [CatalogoRRHHController::class, 'storePerfilPago']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/perfil-pago/{id}',[CatalogoRRHHController::class, 'updatePerfilPago']);
    Route::middleware('permission:CORP_DELETE')->delete('/perfil-pago/{id}',[CatalogoRRHHController::class, 'destroyPerfilPago']);

    // ── Catálogos RRHH – Frecuencia de Pago ───────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/frecuencia-pago',     [CatalogoRRHHController::class, 'indexFrecuenciaPago']);
    Route::middleware('permission:CORP_CREATE')->post  ('/frecuencia-pago',     [CatalogoRRHHController::class, 'storeFrecuenciaPago']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/frecuencia-pago/{id}',[CatalogoRRHHController::class, 'updateFrecuenciaPago']);
    Route::middleware('permission:CORP_DELETE')->delete('/frecuencia-pago/{id}',[CatalogoRRHHController::class, 'destroyFrecuenciaPago']);

    // ── Catálogos RRHH – Tipo Planilla ────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/tipo-planilla',     [CatalogoRRHHController::class, 'indexTipoPlanilla']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/tipo-planilla',     [CatalogoRRHHController::class, 'storeTipoPlanilla']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/tipo-planilla/{id}',[CatalogoRRHHController::class, 'updateTipoPlanilla']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/tipo-planilla/{id}',[CatalogoRRHHController::class, 'destroyTipoPlanilla']);

    // ── Catálogos RRHH – Tipo Préstamo ────────────────────────────────────────
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/tipo-prestamo',     [CatalogoRRHHController::class, 'indexTipoPrestamo']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/tipo-prestamo',     [CatalogoRRHHController::class, 'storeTipoPrestamo']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/tipo-prestamo/{id}',[CatalogoRRHHController::class, 'updateTipoPrestamo']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->delete('/tipo-prestamo/{id}',[CatalogoRRHHController::class, 'destroyTipoPrestamo']);

    // ── Catálogos RRHH – Horas Extras ─────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/horas-extras',     [CatalogoRRHHController::class, 'indexHorasExtras']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/horas-extras',     [CatalogoRRHHController::class, 'storeHorasExtras']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/horas-extras/{id}',[CatalogoRRHHController::class, 'updateHorasExtras']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/horas-extras/{id}',[CatalogoRRHHController::class, 'destroyHorasExtras']);

    // ── Empleados ─────────────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados',          [App\Http\Controllers\EmpleadoController::class, 'index']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados/select',   [App\Http\Controllers\EmpleadoController::class, 'select']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados/catalogs', [App\Http\Controllers\EmpleadoController::class, 'catalogs']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/empleados',          [App\Http\Controllers\EmpleadoController::class, 'store']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/empleados/{id}',     [App\Http\Controllers\EmpleadoController::class, 'update']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/empleados/{id}',     [App\Http\Controllers\EmpleadoController::class, 'destroy']);

    // ── Planillas ─────────────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/catalogs',              [App\Http\Controllers\PlanillaController::class, 'catalogs']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas',                       [App\Http\Controllers\PlanillaController::class, 'index']);
    Route::middleware('permission:SALARIAL_CREATE')->post ('/planillas',                      [App\Http\Controllers\PlanillaController::class, 'store']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}',                  [App\Http\Controllers\PlanillaController::class, 'show']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/detalles',         [App\Http\Controllers\PlanillaController::class, 'detalles']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/empleados-select', [App\Http\Controllers\PlanillaController::class, 'empleadosSelect']);
    Route::middleware('permission:SALARIAL_UPDATE')->post ('/planillas/{id}/calcular',        [App\Http\Controllers\PlanillaController::class, 'calculate']);
    Route::middleware('permission:SALARIAL_UPDATE')->post ('/planillas/{id}/cerrar',           [App\Http\Controllers\PlanillaController::class, 'cerrar']);
    Route::middleware('permission:SALARIAL_UPDATE')->post ('/planillas/{id}/anular',          [App\Http\Controllers\PlanillaController::class, 'anular']);
    Route::middleware('permission:SALARIAL_UPDATE')->post ('/planillas/{id}/contabilizar',    [App\Http\Controllers\PlanillaController::class, 'contabilizar']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/horas-extras',    [App\Http\Controllers\DetalleHorasExtrasController::class, 'index']);
    Route::middleware('permission:SALARIAL_CREATE')->post ('/planillas/{id}/horas-extras',    [App\Http\Controllers\DetalleHorasExtrasController::class, 'store']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/planillas/{planillaId}/horas-extras/{id}', [App\Http\Controllers\DetalleHorasExtrasController::class, 'destroy']);
    Route::middleware('permission:SALARIAL_UPDATE')->post ('/planillas/{id}/horas-extras/sync',[App\Http\Controllers\DetalleHorasExtrasController::class, 'syncFromAttendance']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/archivo-banco/catalogo', [App\Http\Controllers\PlanillaBankExportController::class, 'catalog']);
    Route::middleware('permission:SALARIAL_VIEW')->post  ('/planillas/{id}/archivo-banco/preview',  [App\Http\Controllers\PlanillaBankExportController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->post  ('/planillas/{id}/archivo-banco/generar',  [App\Http\Controllers\PlanillaBankExportController::class, 'generate']);

    // ── Periodos Laborales ────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/periodos-laborales',           [App\Http\Controllers\PeriodoLaboralController::class, 'index']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/periodos-laborales',           [App\Http\Controllers\PeriodoLaboralController::class, 'store']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/periodos-laborales/{id}',      [App\Http\Controllers\PeriodoLaboralController::class, 'update']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/periodos-laborales/generar',   [App\Http\Controllers\PeriodoLaboralController::class, 'generar']);

    // ── Horarios ──────────────────────────────────────────────────────────────
    Route::middleware('permission:ASISTENCIA_VIEW')  ->get   ('/horarios',                     [App\Http\Controllers\HorarioController::class, 'index']);
    Route::middleware('permission:ASISTENCIA_CREATE')->post  ('/horarios',                     [App\Http\Controllers\HorarioController::class, 'store']);
    Route::middleware('permission:ASISTENCIA_UPDATE')->put   ('/horarios/{id}',                [App\Http\Controllers\HorarioController::class, 'update']);
    Route::middleware('permission:ASISTENCIA_DELETE')->delete('/horarios/{id}',                [App\Http\Controllers\HorarioController::class, 'destroy']);

    // ── Asistencia y Marcaciones ──────────────────────────────────────────────
    Route::middleware('permission:ASISTENCIA_VIEW')  ->get   ('/asistencia/catalogs',          [App\Http\Controllers\AsistenciaController::class, 'catalogs']);
    Route::middleware('permission:ASISTENCIA_VIEW')  ->get   ('/asistencia',                   [App\Http\Controllers\AsistenciaController::class, 'index']);
    Route::middleware('permission:ASISTENCIA_VIEW')  ->get   ('/marcaciones/pendientes',       [App\Http\Controllers\AsistenciaController::class, 'marcacionesPendientes']);
    Route::middleware('permission:ASISTENCIA_CREATE')->post  ('/marcaciones',                  [App\Http\Controllers\AsistenciaController::class, 'storeMarcacion']);
    Route::middleware('permission:ASISTENCIA_UPDATE')->post  ('/asistencia/procesar',          [App\Http\Controllers\AsistenciaController::class, 'procesar']);

    // ── Incapacidades ─────────────────────────────────────────────────────────
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/incapacidades/catalogs',   [App\Http\Controllers\IncapacidadController::class, 'catalogs']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/incapacidades',              [App\Http\Controllers\IncapacidadController::class, 'index']);
    Route::middleware('permission:DEDUCCIONES_CREATE')->post  ('/incapacidades',              [App\Http\Controllers\IncapacidadController::class, 'store']);
    Route::middleware('permission:DEDUCCIONES_DELETE')->post  ('/incapacidades/{id}/cancelar',[App\Http\Controllers\IncapacidadController::class, 'cancelar']);
    Route::middleware('permission:DEDUCCIONES_VIEW')  ->get   ('/subsidios-isss',             [App\Http\Controllers\IncapacidadController::class, 'subsidios']);
    Route::middleware('permission:DEDUCCIONES_UPDATE')->put   ('/subsidios-isss/{id}',        [App\Http\Controllers\IncapacidadController::class, 'actualizarSubsidio']);

    // ── Liquidaciones ─────────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/liquidaciones',                [App\Http\Controllers\LiquidacionController::class, 'index']);
    Route::middleware('permission:SALARIAL_VIEW')  ->post  ('/liquidaciones/preview',        [App\Http\Controllers\LiquidacionController::class, 'calcularPreview']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/liquidaciones',                [App\Http\Controllers\LiquidacionController::class, 'store']);

    // ── Parámetros Aguinaldo ───────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/parametros-aguinaldo',         [App\Http\Controllers\ParametrosAguinaldosController::class, 'index']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/parametros-aguinaldo',         [App\Http\Controllers\ParametrosAguinaldosController::class, 'store']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/parametros-aguinaldo/{id}',    [App\Http\Controllers\ParametrosAguinaldosController::class, 'update']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/parametros-aguinaldo/{id}',    [App\Http\Controllers\ParametrosAguinaldosController::class, 'destroy']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/parametros-aguinaldo/seed/{empresaId}', [App\Http\Controllers\ParametrosAguinaldosController::class, 'seedDefault']);

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/dashboard/stats', [App\Http\Controllers\DashboardController::class, 'stats']);
});
