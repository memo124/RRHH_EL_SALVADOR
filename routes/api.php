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

// Configuración inicial (primera instalación — sin autenticación)
Route::get('/setup/status', [App\Http\Controllers\SetupController::class, 'status']);
Route::post('/setup', [App\Http\Controllers\SetupController::class, 'store']);

// Formulario público (sin Sanctum — autenticación por token en URL)
Route::get('/formularios/responder/{token}', [App\Http\Controllers\FormularioPublicoController::class, 'show']);
Route::post('/formularios/responder/{token}', [App\Http\Controllers\FormularioPublicoController::class, 'submit']);
Route::post('/formularios/responder/{token}/adjunto', [App\Http\Controllers\FormularioPublicoController::class, 'uploadAdjunto']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',   [AuthController::class, 'user']);
    Route::put('/user/theme', [AuthController::class, 'updateTheme']);
    Route::post('/logout',[AuthController::class, 'logout']);

    Route::get('/catalogs/{type}/select', [App\Http\Controllers\CatalogSelectController::class, 'select']);

    // ── Notificaciones ────────────────────────────────────────────────────────
    Route::get ('/notificaciones',                [App\Http\Controllers\NotificacionController::class, 'index']);
    Route::get ('/notificaciones/no-leidas',      [App\Http\Controllers\NotificacionController::class, 'unreadCount']);
    Route::post('/notificaciones/leer-todas',     [App\Http\Controllers\NotificacionController::class, 'markAllRead']);
    Route::post('/notificaciones/{id}/leer',      [App\Http\Controllers\NotificacionController::class, 'markRead']);

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

    // ── Geografía — Departamentos ──────────────────────────────────────────────
    Route::middleware('permission:GEOGRAFIA_VIEW')  ->get   ('/departamentos-pais',     [GeografiaController::class, 'indexDepartamentos']);
    Route::middleware('permission:GEOGRAFIA_CREATE')->post  ('/departamentos-pais',     [GeografiaController::class, 'storeDepartamento']);
    Route::middleware('permission:GEOGRAFIA_UPDATE')->put   ('/departamentos-pais/{id}',[GeografiaController::class, 'updateDepartamento']);
    Route::middleware('permission:GEOGRAFIA_DELETE')->delete('/departamentos-pais/{id}',[GeografiaController::class, 'destroyDepartamento']);

    // ── Geografía — Municipios ─────────────────────────────────────────────────
    Route::middleware('permission:GEOGRAFIA_VIEW')  ->get   ('/municipios',     [GeografiaController::class, 'indexMunicipios']);
    Route::middleware('permission:GEOGRAFIA_CREATE')->post  ('/municipios',     [GeografiaController::class, 'storeMunicipio']);
    Route::middleware('permission:GEOGRAFIA_UPDATE')->put   ('/municipios/{id}',[GeografiaController::class, 'updateMunicipio']);
    Route::middleware('permission:GEOGRAFIA_DELETE')->delete('/municipios/{id}',[GeografiaController::class, 'destroyMunicipio']);

    // ── Geografía — Distritos ──────────────────────────────────────────────────
    Route::middleware('permission:GEOGRAFIA_VIEW')  ->get   ('/distritos',     [GeografiaController::class, 'indexDistritos']);
    Route::middleware('permission:GEOGRAFIA_CREATE')->post  ('/distritos',     [GeografiaController::class, 'storeDistrito']);
    Route::middleware('permission:GEOGRAFIA_UPDATE')->put   ('/distritos/{id}',[GeografiaController::class, 'updateDistrito']);
    Route::middleware('permission:GEOGRAFIA_DELETE')->delete('/distritos/{id}',[GeografiaController::class, 'destroyDistrito']);

    // ── Catálogos MH ──────────────────────────────────────────────────────────
    Route::middleware('permission:MH_VIEW')  ->get   ('/tipo-documento',     [CatalogosMhController::class, 'index']);
    Route::middleware('permission:MH_CREATE')->post  ('/tipo-documento',     [CatalogosMhController::class, 'store']);
    Route::middleware('permission:MH_UPDATE')->put   ('/tipo-documento/{id}',[CatalogosMhController::class, 'update']);
    Route::middleware('permission:MH_DELETE')->delete('/tipo-documento/{id}',[CatalogosMhController::class, 'destroy']);

    // ── Catálogos MH — Establecimientos ────────────────────────────────────────
    Route::middleware('permission:MH_VIEW')  ->get   ('/establecimientos',     [App\Http\Controllers\EstablecimientoController::class, 'index']);
    Route::middleware('permission:MH_CREATE')->post  ('/establecimientos',     [App\Http\Controllers\EstablecimientoController::class, 'store']);
    Route::middleware('permission:MH_UPDATE')->put   ('/establecimientos/{id}',[App\Http\Controllers\EstablecimientoController::class, 'update']);
    Route::middleware('permission:MH_DELETE')->delete('/establecimientos/{id}',[App\Http\Controllers\EstablecimientoController::class, 'destroy']);

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

    // ── Corporativo – Firmantes ───────────────────────────────────────────────
    Route::middleware('permission:CORP_VIEW')  ->get   ('/firmantes',     [CorporativoController::class, 'indexFirmantes']);
    Route::middleware('permission:CORP_CREATE')->post  ('/firmantes',     [CorporativoController::class, 'storeFirmante']);
    Route::middleware('permission:CORP_UPDATE')->put   ('/firmantes/{id}',[CorporativoController::class, 'updateFirmante']);
    Route::middleware('permission:CORP_DELETE')->delete('/firmantes/{id}',[CorporativoController::class, 'destroyFirmante']);

    // ── Contratos laborales ───────────────────────────────────────────────────
    Route::middleware('permission:CONTRATO_VIEW')  ->get   ('/plantillas-contrato/campos',        [App\Http\Controllers\PlantillaContratoController::class, 'campos']);
    Route::middleware('permission:CONTRATO_VIEW')  ->get   ('/plantillas-contrato',               [App\Http\Controllers\PlantillaContratoController::class, 'index']);
    Route::middleware('permission:CONTRATO_VIEW')  ->get   ('/plantillas-contrato/{id}/preview',  [App\Http\Controllers\PlantillaContratoController::class, 'preview']);
    Route::middleware('permission:CONTRATO_CREATE')->post  ('/plantillas-contrato',               [App\Http\Controllers\PlantillaContratoController::class, 'store']);
    Route::middleware('permission:CONTRATO_UPDATE')->put   ('/plantillas-contrato/{id}',          [App\Http\Controllers\PlantillaContratoController::class, 'update']);
    Route::middleware('permission:CONTRATO_DELETE')->delete('/plantillas-contrato/{id}',          [App\Http\Controllers\PlantillaContratoController::class, 'destroy']);

    Route::middleware('permission:CONTRATO_VIEW')  ->post  ('/contratos/lote/preview',       [App\Http\Controllers\ContratoController::class, 'previewLote']);
    Route::middleware('permission:CONTRATO_CREATE')->post  ('/contratos/lote/generar',         [App\Http\Controllers\ContratoController::class, 'generarLote']);
    Route::middleware('permission:CONTRATO_VIEW')  ->get   ('/contratos',                    [App\Http\Controllers\ContratoController::class, 'index']);
    Route::middleware('permission:CONTRATO_VIEW')  ->post  ('/contratos/numero-a-letras',    [App\Http\Controllers\ContratoController::class, 'numeroALetras']);
    Route::middleware('permission:CONTRATO_CREATE')->post  ('/contratos',                    [App\Http\Controllers\ContratoController::class, 'store']);
    Route::middleware('permission:CONTRATO_UPDATE')->put   ('/contratos/{id}',               [App\Http\Controllers\ContratoController::class, 'update']);
    Route::middleware('permission:CONTRATO_UPDATE')->post  ('/contratos/{id}/regenerar',     [App\Http\Controllers\ContratoController::class, 'regenerar']);
    Route::middleware('permission:CONTRATO_DELETE')->delete('/contratos/{id}',              [App\Http\Controllers\ContratoController::class, 'destroy']);

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

    // ── Seguridad – Auditoría ─────────────────────────────────────────────────
    Route::middleware('permission:SEGURIDAD_VIEW')->get('/auditoria',         [App\Http\Controllers\AuditoriaController::class, 'index']);
    Route::middleware('permission:SEGURIDAD_VIEW')->get('/auditoria/tablas', [App\Http\Controllers\AuditoriaController::class, 'tablas']);

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

    // ── Empleados — Expediente ampliado (Educación, Certificaciones, Dependientes) ──
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados/{id}/educacion',            [App\Http\Controllers\EmpleadoExpedienteController::class, 'indexEducacion']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/empleados/{id}/educacion',            [App\Http\Controllers\EmpleadoExpedienteController::class, 'storeEducacion']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/empleados/{id}/educacion/{eduId}',    [App\Http\Controllers\EmpleadoExpedienteController::class, 'updateEducacion']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/empleados/{id}/educacion/{eduId}',    [App\Http\Controllers\EmpleadoExpedienteController::class, 'destroyEducacion']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados/{id}/certificaciones',          [App\Http\Controllers\EmpleadoExpedienteController::class, 'indexCertificaciones']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/empleados/{id}/certificaciones',          [App\Http\Controllers\EmpleadoExpedienteController::class, 'storeCertificacion']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/empleados/{id}/certificaciones/{certId}', [App\Http\Controllers\EmpleadoExpedienteController::class, 'updateCertificacion']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/empleados/{id}/certificaciones/{certId}', [App\Http\Controllers\EmpleadoExpedienteController::class, 'destroyCertificacion']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/empleados/{id}/dependientes',          [App\Http\Controllers\EmpleadoExpedienteController::class, 'indexDependientes']);
    Route::middleware('permission:SALARIAL_CREATE')->post  ('/empleados/{id}/dependientes',          [App\Http\Controllers\EmpleadoExpedienteController::class, 'storeDependiente']);
    Route::middleware('permission:SALARIAL_UPDATE')->put   ('/empleados/{id}/dependientes/{depId}',  [App\Http\Controllers\EmpleadoExpedienteController::class, 'updateDependiente']);
    Route::middleware('permission:SALARIAL_DELETE')->delete('/empleados/{id}/dependientes/{depId}',  [App\Http\Controllers\EmpleadoExpedienteController::class, 'destroyDependiente']);

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
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/asiento',          [App\Http\Controllers\PlanillaController::class, 'asiento']);
    Route::middleware('permission:SALARIAL_VIEW')->get   ('/planillas/{id}/asiento/export',   [App\Http\Controllers\PlanillaController::class, 'asientoExport']);
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
    Route::middleware('permission:ASISTENCIA_CREATE')->post  ('/marcaciones/importar',         [App\Http\Controllers\AsistenciaController::class, 'importar']);
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

    Route::middleware('permission:ERROR_JOURNAL_VIEW')->get('/error-journal', [App\Http\Controllers\ErrorJournalController::class, 'index']);
    Route::middleware('permission:ERROR_JOURNAL_VIEW')->get('/error-journal/{filename}', [App\Http\Controllers\ErrorJournalController::class, 'show']);

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/dashboard/stats', [App\Http\Controllers\DashboardController::class, 'stats']);

    // ── Gestión Humana — Adjuntos ─────────────────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/adjuntos/tipos',              [App\Http\Controllers\AdjuntoController::class, 'tipos']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/adjuntos',                    [App\Http\Controllers\AdjuntoController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/adjuntos',                    [App\Http\Controllers\AdjuntoController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/adjuntos/{id}/download',      [App\Http\Controllers\AdjuntoController::class, 'download']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/adjuntos/{id}',               [App\Http\Controllers\AdjuntoController::class, 'destroy']);

    // ── Gestión Humana — Encuestas ────────────────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/encuestas/mis-encuestas',     [App\Http\Controllers\EncuestaController::class, 'misEncuestas']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/encuestas',                   [App\Http\Controllers\EncuestaController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/encuestas/{id}',              [App\Http\Controllers\EncuestaController::class, 'show']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/encuestas/{id}/resultados',   [App\Http\Controllers\EncuestaController::class, 'resultados']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/encuestas',                   [App\Http\Controllers\EncuestaController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/encuestas/{id}',              [App\Http\Controllers\EncuestaController::class, 'update']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/encuestas/{id}/publicar',     [App\Http\Controllers\EncuestaController::class, 'publicar']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/encuestas/{id}/cerrar',       [App\Http\Controllers\EncuestaController::class, 'cerrar']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/encuestas/{id}/responder',    [App\Http\Controllers\EncuestaController::class, 'responder']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/encuestas/{id}',              [App\Http\Controllers\EncuestaController::class, 'destroy']);

    // ── Gestión Humana — Calendario ───────────────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/calendario/tipos',            [App\Http\Controllers\CalendarioController::class, 'tipos']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/calendario/eventos',          [App\Http\Controllers\CalendarioController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/calendario/eventos',          [App\Http\Controllers\CalendarioController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/calendario/eventos/{id}',     [App\Http\Controllers\CalendarioController::class, 'update']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/calendario/eventos/{id}',     [App\Http\Controllers\CalendarioController::class, 'destroy']);

    // ── Gestión Humana — Formularios empleado ─────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/plantillas/select',             [App\Http\Controllers\FormularioEmpleadoController::class, 'selectPlantillas']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/plantillas',                    [App\Http\Controllers\FormularioEmpleadoController::class, 'indexPlantillas']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/plantillas/{id}',               [App\Http\Controllers\FormularioEmpleadoController::class, 'showPlantilla']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/formularios/plantillas',                    [App\Http\Controllers\FormularioEmpleadoController::class, 'storePlantilla']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/formularios/plantillas/seed-default',       [App\Http\Controllers\FormularioEmpleadoController::class, 'seedPlantillaDefault']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/formularios/plantillas/{id}',               [App\Http\Controllers\FormularioEmpleadoController::class, 'updatePlantilla']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/formularios/plantillas/{id}',              [App\Http\Controllers\FormularioEmpleadoController::class, 'destroyPlantilla']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/campanas',                      [App\Http\Controllers\FormularioEmpleadoController::class, 'indexCampanas']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/formularios/campanas',                      [App\Http\Controllers\FormularioEmpleadoController::class, 'storeCampana']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/formularios/campanas/{id}/activar',         [App\Http\Controllers\FormularioEmpleadoController::class, 'activarCampana']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/formularios/campanas/{id}/invitaciones',    [App\Http\Controllers\FormularioEmpleadoController::class, 'generarInvitaciones']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/campanas/{id}/invitaciones',    [App\Http\Controllers\FormularioEmpleadoController::class, 'invitacionesCampana']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/respuestas/pendientes',         [App\Http\Controllers\FormularioEmpleadoController::class, 'respuestasPendientes']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/formularios/respuestas/{id}',               [App\Http\Controllers\FormularioEmpleadoController::class, 'showRespuesta']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/formularios/respuestas/{id}/aprobar',       [App\Http\Controllers\FormularioEmpleadoController::class, 'aprobarRespuesta']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/formularios/respuestas/{id}/rechazar',      [App\Http\Controllers\FormularioEmpleadoController::class, 'rechazarRespuesta']);

    // ── Gestión Humana — Vacaciones y permisos ────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/permisos/catalogs',                         [App\Http\Controllers\SolicitudPermisoController::class, 'catalogs']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/permisos',                                  [App\Http\Controllers\SolicitudPermisoController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/permisos/pendientes',                       [App\Http\Controllers\SolicitudPermisoController::class, 'pendientes']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/permisos/saldo/{idEmpleado}',               [App\Http\Controllers\SolicitudPermisoController::class, 'saldo']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/permisos',                                  [App\Http\Controllers\SolicitudPermisoController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/permisos/saldos/inicializar',               [App\Http\Controllers\SolicitudPermisoController::class, 'inicializarSaldos']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/permisos/{id}/aprobar',                     [App\Http\Controllers\SolicitudPermisoController::class, 'aprobar']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/permisos/{id}/integrar-planilla',           [App\Http\Controllers\SolicitudPermisoController::class, 'integrarPlanilla']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/permisos/{id}/rechazar',                    [App\Http\Controllers\SolicitudPermisoController::class, 'rechazar']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/permisos/{id}/cancelar',                    [App\Http\Controllers\SolicitudPermisoController::class, 'cancelar']);

    // ── Gestión Humana — Capacitaciones ───────────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/capacitaciones',                            [App\Http\Controllers\CapacitacionController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/capacitaciones',                            [App\Http\Controllers\CapacitacionController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/capacitaciones/inscripciones/{id}/asistencias', [App\Http\Controllers\CapacitacionController::class, 'asistencias']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/capacitaciones/inscripciones/{id}/asistencia', [App\Http\Controllers\CapacitacionController::class, 'asistencia']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/capacitaciones/inscripciones/{id}/completar', [App\Http\Controllers\CapacitacionController::class, 'completar']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/capacitaciones/{id}',                      [App\Http\Controllers\CapacitacionController::class, 'show']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/capacitaciones/{id}',                       [App\Http\Controllers\CapacitacionController::class, 'update']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/capacitaciones/{id}/publicar',              [App\Http\Controllers\CapacitacionController::class, 'publicar']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/capacitaciones/{id}/cerrar',                [App\Http\Controllers\CapacitacionController::class, 'cerrar']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/capacitaciones/{id}/inscribir',             [App\Http\Controllers\CapacitacionController::class, 'inscribir']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/capacitaciones/{id}',                       [App\Http\Controllers\CapacitacionController::class, 'destroy']);

    // ── Gestión Humana — Reclutamiento ──────────────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/reclutamiento/catalogs',                    [App\Http\Controllers\ReclutamientoController::class, 'catalogs']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/reclutamiento/vacantes',                    [App\Http\Controllers\ReclutamientoController::class, 'indexVacantes']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/reclutamiento/vacantes/{id}',               [App\Http\Controllers\ReclutamientoController::class, 'showVacante']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/reclutamiento/candidatos/{id}/entrevistas', [App\Http\Controllers\ReclutamientoController::class, 'entrevistasCandidato']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/reclutamiento/vacantes',                    [App\Http\Controllers\ReclutamientoController::class, 'storeVacante']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/reclutamiento/vacantes/{id}',               [App\Http\Controllers\ReclutamientoController::class, 'updateVacante']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/reclutamiento/vacantes/{id}/cerrar',        [App\Http\Controllers\ReclutamientoController::class, 'cerrarVacante']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/reclutamiento/candidatos',                  [App\Http\Controllers\ReclutamientoController::class, 'storeCandidato']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/reclutamiento/candidatos/{id}/etapa',       [App\Http\Controllers\ReclutamientoController::class, 'avanzarEtapa']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/reclutamiento/candidatos/{id}/cv',          [App\Http\Controllers\ReclutamientoController::class, 'attachCv']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/reclutamiento/candidatos/{id}/contratar',  [App\Http\Controllers\ReclutamientoController::class, 'previewContratar']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/reclutamiento/candidatos/{id}/contratar', [App\Http\Controllers\ReclutamientoController::class, 'contratar']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/reclutamiento/entrevistas',                 [App\Http\Controllers\ReclutamientoController::class, 'storeEntrevista']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/reclutamiento/vacantes/{id}',               [App\Http\Controllers\ReclutamientoController::class, 'destroyVacante']);

    // ── Gestión Humana — Evaluación de desempeño ────────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/evaluaciones/periodos',                   [App\Http\Controllers\EvaluacionDesempenoController::class, 'indexPeriodos']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/evaluaciones/periodos/{id}',              [App\Http\Controllers\EvaluacionDesempenoController::class, 'showPeriodo']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/evaluaciones/periodos/{id}/resultados',   [App\Http\Controllers\EvaluacionDesempenoController::class, 'resultados']);
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/evaluaciones/{id}',                       [App\Http\Controllers\EvaluacionDesempenoController::class, 'showEvaluacion']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/evaluaciones/periodos',                   [App\Http\Controllers\EvaluacionDesempenoController::class, 'storePeriodo']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/evaluaciones/periodos/{id}/activar',      [App\Http\Controllers\EvaluacionDesempenoController::class, 'activarPeriodo']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/evaluaciones/periodos/{id}/cerrar',       [App\Http\Controllers\EvaluacionDesempenoController::class, 'cerrarPeriodo']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/evaluaciones/periodos/{id}/asignar',      [App\Http\Controllers\EvaluacionDesempenoController::class, 'asignarEvaluaciones']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/evaluaciones/{id}/metas',                 [App\Http\Controllers\EvaluacionDesempenoController::class, 'saveMetas']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->post  ('/evaluaciones/{id}/completar',             [App\Http\Controllers\EvaluacionDesempenoController::class, 'completar']);

    // ── Gestión Humana — Actividades económicas MH ─────────────────────────────
    Route::middleware('permission:GESTION_HUMANA_VIEW')  ->get   ('/actividades-economicas',                  [App\Http\Controllers\ActividadEconomicaController::class, 'index']);
    Route::middleware('permission:GESTION_HUMANA_CREATE')->post  ('/actividades-economicas',                  [App\Http\Controllers\ActividadEconomicaController::class, 'store']);
    Route::middleware('permission:GESTION_HUMANA_UPDATE')->put   ('/actividades-economicas/{id}',             [App\Http\Controllers\ActividadEconomicaController::class, 'update']);
    Route::middleware('permission:GESTION_HUMANA_DELETE')->delete('/actividades-economicas/{id}',             [App\Http\Controllers\ActividadEconomicaController::class, 'destroy']);

    // ── Cumplimiento SV — Planilla ISSS ───────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/isss/planillas', [App\Http\Controllers\IsssPlanillaController::class, 'planillas']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/isss/preview',   [App\Http\Controllers\IsssPlanillaController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/isss/export',    [App\Http\Controllers\IsssPlanillaController::class, 'export']);

    // ── Cumplimiento SV — Planilla AFP ────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/afp/planillas', [App\Http\Controllers\AfpPlanillaController::class, 'planillas']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/afp/catalogo',  [App\Http\Controllers\AfpPlanillaController::class, 'catalogo']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/afp/preview',   [App\Http\Controllers\AfpPlanillaController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/afp/export',    [App\Http\Controllers\AfpPlanillaController::class, 'export']);

    // ── Cumplimiento SV — INSAFORP ────────────────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/insaforp/planillas', [App\Http\Controllers\InsaforpController::class, 'planillas']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/insaforp/preview',   [App\Http\Controllers\InsaforpController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/insaforp/export',    [App\Http\Controllers\InsaforpController::class, 'export']);

    // ── Cumplimiento SV — F-14 / Renta retenida MH ────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/renta/preview', [App\Http\Controllers\RentaRetencionController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/renta/export',  [App\Http\Controllers\RentaRetencionController::class, 'export']);

    // ── Cumplimiento SV — Altas y bajas ISSS ──────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/cumplimiento/isss-movimientos',                 [App\Http\Controllers\IsssMovimientoController::class, 'index']);
    Route::middleware('permission:SALARIAL_UPDATE')->post  ('/cumplimiento/isss-movimientos/marcar-enviado',  [App\Http\Controllers\IsssMovimientoController::class, 'marcarEnviado']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get   ('/cumplimiento/isss-movimientos/export',          [App\Http\Controllers\IsssMovimientoController::class, 'export']);

    // ── Cumplimiento SV — Aguinaldo (corrida) ─────────────────────────────────
    Route::middleware('permission:SALARIAL_VIEW')  ->get ('/cumplimiento/aguinaldo/preview',        [App\Http\Controllers\AguinaldoCorridaController::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')  ->get ('/cumplimiento/aguinaldo/export',         [App\Http\Controllers\AguinaldoCorridaController::class, 'export']);
    Route::middleware('permission:SALARIAL_CREATE')->post('/cumplimiento/aguinaldo/crear-planilla', [App\Http\Controllers\AguinaldoCorridaController::class, 'crearPlanilla']);

    // ── Cumplimiento SV — Retención 10% servicios profesionales ──────────────
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/retencion10/planillas',  [App\Http\Controllers\Retencion10Controller::class, 'planillas']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/retencion10/estimacion', [App\Http\Controllers\Retencion10Controller::class, 'estimacion']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/retencion10/preview',    [App\Http\Controllers\Retencion10Controller::class, 'preview']);
    Route::middleware('permission:SALARIAL_VIEW')->get('/cumplimiento/retencion10/export',     [App\Http\Controllers\Retencion10Controller::class, 'export']);

    // ── Portal Empleado (autoservicio) ───────────────────────────────────────
    Route::middleware('portal.employee')->group(function () {
        Route::middleware('permission:PORTAL_VIEW')       ->get ('/portal/me',                    [App\Http\Controllers\PortalController::class, 'me']);
        Route::middleware('permission:PORTAL_BOLETAS')     ->get ('/portal/boletas',                [App\Http\Controllers\PortalController::class, 'boletas']);
        Route::middleware('permission:PORTAL_PERMISOS')    ->get ('/portal/permisos/catalogs',      [App\Http\Controllers\PortalController::class, 'permisosCatalogs']);
        Route::middleware('permission:PORTAL_PERMISOS')    ->get ('/portal/permisos',               [App\Http\Controllers\PortalController::class, 'permisos']);
        Route::middleware('permission:PORTAL_PERMISOS')    ->post('/portal/permisos',               [App\Http\Controllers\PortalController::class, 'storePermiso']);
        Route::middleware('permission:PORTAL_PERMISOS')    ->post('/portal/permisos/{id}/cancelar', [App\Http\Controllers\PortalController::class, 'cancelarPermiso']);
        Route::middleware('permission:PORTAL_ENCUESTAS')   ->get ('/portal/encuestas',              [App\Http\Controllers\PortalController::class, 'encuestas']);
        Route::middleware('permission:PORTAL_ENCUESTAS')   ->post('/portal/encuestas/{id}/responder', [App\Http\Controllers\PortalController::class, 'responderEncuesta']);
        Route::middleware('permission:PORTAL_EVALUACIONES')->get ('/portal/evaluaciones',           [App\Http\Controllers\PortalController::class, 'evaluaciones']);
        Route::middleware('permission:PORTAL_EVALUACIONES')->get ('/portal/evaluaciones/{id}',      [App\Http\Controllers\PortalController::class, 'evaluacionShow']);
    });
});
