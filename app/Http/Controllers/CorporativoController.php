<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CorporativoController extends Controller
{
    use PaginatesQueries;

    // ─── EMPRESAS ─────────────────────────────────────────────────────────────

    public function indexEmpresas(Request $request)
    {
        $query = DB::table('EMPRESA')->orderBy('ID_EMPRESA');

        return $this->paginateQuery($query, $request, ['NOMBREEMPRESA', 'NUMERONIT', 'ABREVIATURA']);
    }

    public function storeEmpresa(Request $request)
    {
        $request->validate([
            'NOMBREEMPRESA'   => 'required|string|max:150',
            'ABREVIATURA'     => 'nullable|string|max:20',
            'NUMEROREGISTRO'  => 'nullable|string|max:50',
            'NUMERONIT'       => 'nullable|string|max:20',
            'GIRO'            => 'nullable|string|max:500',
            'DIRECCION'       => 'nullable|string|max:500',
            'TELEFONO'        => 'nullable|string|max:25',
            'URL_LOGO'        => 'nullable|string|max:2048',
            'NOMBRE_DUENO'    => 'nullable|string|max:200',
            'DUI_DUENO'       => 'nullable|string|max:12',
        ]);

        $maxId = DB::table('EMPRESA')->max('ID_EMPRESA') ?? 0;
        $id    = $maxId + 1;

        DB::table('EMPRESA')->insert([
            'ID_EMPRESA'    => $id,
            'NOMBREEMPRESA' => $request->NOMBREEMPRESA,
            'ABREVIATURA'   => $request->ABREVIATURA,
            'NUMEROREGISTRO'=> $request->NUMEROREGISTRO,
            'NUMERONIT'     => $request->NUMERONIT,
            'GIRO'          => $request->GIRO,
            'DIRECCION'     => $request->DIRECCION,
            'TELEFONO'      => $request->TELEFONO,
            'URL_LOGO'      => $request->URL_LOGO,
            'NOMBRE_DUENO'  => $request->NOMBRE_DUENO,
            'DUI_DUENO'     => $request->DUI_DUENO,
            'EMPRESAACTIVA' => true,
        ]);

        return response()->json(['ID_EMPRESA' => $id, 'NOMBREEMPRESA' => $request->NOMBREEMPRESA], 201);
    }

    public function updateEmpresa(Request $request, $id)
    {
        $request->validate([
            'NOMBREEMPRESA'  => 'required|string|max:150',
            'ABREVIATURA'    => 'nullable|string|max:20',
            'NUMEROREGISTRO' => 'nullable|string|max:50',
            'NUMERONIT'      => 'nullable|string|max:20',
            'GIRO'           => 'nullable|string|max:500',
            'DIRECCION'      => 'nullable|string|max:500',
            'TELEFONO'       => 'nullable|string|max:25',
            'URL_LOGO'       => 'nullable|string|max:2048',
            'NOMBRE_DUENO'   => 'nullable|string|max:200',
            'DUI_DUENO'      => 'nullable|string|max:12',
            'EMPRESAACTIVA'  => 'boolean',
        ]);

        DB::table('EMPRESA')->where('ID_EMPRESA', $id)->update([
            'NOMBREEMPRESA'  => $request->NOMBREEMPRESA,
            'ABREVIATURA'    => $request->ABREVIATURA,
            'NUMEROREGISTRO' => $request->NUMEROREGISTRO,
            'NUMERONIT'      => $request->NUMERONIT,
            'GIRO'           => $request->GIRO,
            'DIRECCION'      => $request->DIRECCION,
            'TELEFONO'       => $request->TELEFONO,
            'URL_LOGO'       => $request->URL_LOGO,
            'NOMBRE_DUENO'   => $request->NOMBRE_DUENO,
            'DUI_DUENO'      => $request->DUI_DUENO,
            'EMPRESAACTIVA'  => $request->EMPRESAACTIVA ?? true,
        ]);

        return response()->json(['ID_EMPRESA' => $id, 'NOMBREEMPRESA' => $request->NOMBREEMPRESA]);
    }

    public function destroyEmpresa($id)
    {
        DB::table('EMPRESA')->where('ID_EMPRESA', $id)->update(['EMPRESAACTIVA' => false]);
        return response()->json(['message' => 'Empresa inactivada correctamente.']);
    }

    public function uploadEmpresaLogo(Request $request, $id)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,webp,svg|max:2048',
        ]);

        if (!DB::table('EMPRESA')->where('ID_EMPRESA', $id)->exists()) {
            return response()->json(['error' => 'Empresa no encontrada.'], 404);
        }

        $file = $request->file('logo');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = "empresa_{$id}.{$ext}";

        Storage::disk('public')->makeDirectory('logos');
        Storage::disk('public')->putFileAs('logos', $file, $filename);

        $url = Storage::url("logos/{$filename}");
        DB::table('EMPRESA')->where('ID_EMPRESA', $id)->update(['URL_LOGO' => $url]);

        return response()->json([
            'URL_LOGO' => $url,
            'message' => 'Logo actualizado correctamente.',
        ]);
    }

    // ─── AREAS ────────────────────────────────────────────────────────────────

    public function indexAreas(Request $request)
    {
        $query = DB::table('AREA')
            ->leftJoin('EMPRESA', 'AREA.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('AREA.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('AREA.ID_AREA');

        return $this->paginateQuery($query, $request, ['NOMBREAREA', 'NOMBREEMPRESA']);
    }

    public function storeArea(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'  => 'required|integer',
            'NOMBREAREA'  => 'required|string|max:200',
            'ACTIVA'      => 'boolean',
            'PRORRATEADA' => 'boolean',
        ]);

        $maxId = DB::table('AREA')->max('ID_AREA') ?? 0;
        $id    = $maxId + 1;

        DB::table('AREA')->insert([
            'ID_AREA'     => $id,
            'ID_EMPRESA'  => $request->ID_EMPRESA,
            'NOMBREAREA'  => $request->NOMBREAREA,
            'ACTIVA'      => $request->ACTIVA ?? true,
            'PRORRATEADA' => $request->PRORRATEADA ?? false,
        ]);

        return response()->json(['ID_AREA' => $id, 'NOMBREAREA' => $request->NOMBREAREA], 201);
    }

    public function updateArea(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'  => 'required|integer',
            'NOMBREAREA'  => 'required|string|max:200',
            'ACTIVA'      => 'boolean',
            'PRORRATEADA' => 'boolean',
        ]);

        DB::table('AREA')->where('ID_AREA', $id)->update([
            'ID_EMPRESA'  => $request->ID_EMPRESA,
            'NOMBREAREA'  => $request->NOMBREAREA,
            'ACTIVA'      => $request->ACTIVA ?? true,
            'PRORRATEADA' => $request->PRORRATEADA ?? false,
        ]);

        return response()->json(['ID_AREA' => $id, 'NOMBREAREA' => $request->NOMBREAREA]);
    }

    public function destroyArea($id)
    {
        DB::table('AREA')->where('ID_AREA', $id)->update(['ACTIVA' => false]);
        return response()->json(['message' => 'Área inactivada correctamente.']);
    }

    // ─── CENTRO DE COSTO ──────────────────────────────────────────────────────

    public function indexCentrosCosto(Request $request)
    {
        $query = DB::table('CENTRO_COSTO')
            ->leftJoin('EMPRESA', 'CENTRO_COSTO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('CENTRO_COSTO.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('CENTRO_COSTO.ID_CENTROCOSTO');

        return $this->paginateQuery($query, $request, ['NOMBRE_CENTROCOSTO', 'CODIGO_CENTROCOSTO', 'NOMBREEMPRESA']);
    }

    public function storeCentroCosto(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'           => 'required|integer',
            'CODIGO_CENTROCOSTO'   => 'required|string|max:50',
            'NOMBRE_CENTROCOSTO'   => 'required|string|max:200',
            'DESCRIPCION'          => 'nullable|string|max:500',
            'ESACTIVO'             => 'boolean',
        ]);

        $maxId = DB::table('CENTRO_COSTO')->max('ID_CENTROCOSTO') ?? 0;
        $id    = $maxId + 1;

        DB::table('CENTRO_COSTO')->insert([
            'ID_CENTROCOSTO'       => $id,
            'ID_EMPRESA'           => $request->ID_EMPRESA,
            'CODIGO_CENTROCOSTO'   => $request->CODIGO_CENTROCOSTO,
            'NOMBRE_CENTROCOSTO'   => $request->NOMBRE_CENTROCOSTO,
            'DESCRIPCION'          => $request->DESCRIPCION,
            'ESACTIVO'             => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_CENTROCOSTO' => $id, 'NOMBRE_CENTROCOSTO' => $request->NOMBRE_CENTROCOSTO], 201);
    }

    public function updateCentroCosto(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'         => 'required|integer',
            'CODIGO_CENTROCOSTO' => 'required|string|max:50',
            'NOMBRE_CENTROCOSTO' => 'required|string|max:200',
            'DESCRIPCION'        => 'nullable|string|max:500',
            'ESACTIVO'           => 'boolean',
        ]);

        DB::table('CENTRO_COSTO')->where('ID_CENTROCOSTO', $id)->update([
            'ID_EMPRESA'         => $request->ID_EMPRESA,
            'CODIGO_CENTROCOSTO' => $request->CODIGO_CENTROCOSTO,
            'NOMBRE_CENTROCOSTO' => $request->NOMBRE_CENTROCOSTO,
            'DESCRIPCION'        => $request->DESCRIPCION,
            'ESACTIVO'           => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_CENTROCOSTO' => $id, 'NOMBRE_CENTROCOSTO' => $request->NOMBRE_CENTROCOSTO]);
    }

    public function destroyCentroCosto($id)
    {
        DB::table('CENTRO_COSTO')->where('ID_CENTROCOSTO', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Centro de costo inactivado correctamente.']);
    }

    // ─── DEPARTAMENTOS ────────────────────────────────────────────────────────

    public function indexDepartamentos(Request $request)
    {
        $query = DB::table('DEPARTAMENTO')
            ->leftJoin('EMPRESA', 'DEPARTAMENTO.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('AREA', 'DEPARTAMENTO.ID_AREA', '=', 'AREA.ID_AREA')
            ->select('DEPARTAMENTO.*', 'EMPRESA.NOMBREEMPRESA', 'AREA.NOMBREAREA')
            ->orderBy('DEPARTAMENTO.ID_DEPARTAMENTO');

        return $this->paginateQuery($query, $request, ['NOMBREDEPARTAMENTO', 'NOMBREEMPRESA', 'NOMBREAREA']);
    }

    public function storeDepartamento(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'       => 'required|integer',
            'ID_AREA'          => 'required|integer',
            'ID_CENTROCOSTO'   => 'nullable|integer',
            'NOMBREDEPARTAMENTO' => 'required|string|max:150',
            'DESCRIPCION'      => 'nullable|string|max:500',
            'CUENTACONTABLE'   => 'nullable|string|max:50',
            'MANO_OBRA_DIRECTA'=> 'boolean',
        ]);

        $maxId = DB::table('DEPARTAMENTO')->max('ID_DEPARTAMENTO') ?? 0;
        $id    = $maxId + 1;

        DB::table('DEPARTAMENTO')->insert([
            'ID_DEPARTAMENTO'    => $id,
            'ID_EMPRESA'         => $request->ID_EMPRESA,
            'ID_AREA'            => $request->ID_AREA,
            'ID_CENTROCOSTO'     => $request->ID_CENTROCOSTO,
            'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO,
            'DESCRIPCION'        => $request->DESCRIPCION,
            'CUENTACONTABLE'     => $request->CUENTACONTABLE,
            'MANO_OBRA_DIRECTA'  => $request->MANO_OBRA_DIRECTA ?? false,
        ]);

        return response()->json(['ID_DEPARTAMENTO' => $id, 'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO], 201);
    }

    public function updateDepartamento(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'         => 'required|integer',
            'ID_AREA'            => 'required|integer',
            'ID_CENTROCOSTO'     => 'nullable|integer',
            'NOMBREDEPARTAMENTO' => 'required|string|max:150',
            'DESCRIPCION'        => 'nullable|string|max:500',
            'CUENTACONTABLE'     => 'nullable|string|max:50',
            'MANO_OBRA_DIRECTA'  => 'boolean',
        ]);

        DB::table('DEPARTAMENTO')->where('ID_DEPARTAMENTO', $id)->update([
            'ID_EMPRESA'         => $request->ID_EMPRESA,
            'ID_AREA'            => $request->ID_AREA,
            'ID_CENTROCOSTO'     => $request->ID_CENTROCOSTO,
            'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO,
            'DESCRIPCION'        => $request->DESCRIPCION,
            'CUENTACONTABLE'     => $request->CUENTACONTABLE,
            'MANO_OBRA_DIRECTA'  => $request->MANO_OBRA_DIRECTA ?? false,
        ]);

        return response()->json(['ID_DEPARTAMENTO' => $id, 'NOMBREDEPARTAMENTO' => $request->NOMBREDEPARTAMENTO]);
    }

    public function destroyDepartamento($id)
    {
        DB::table('DEPARTAMENTO')->where('ID_DEPARTAMENTO', $id)->delete();
        return response()->json(['message' => 'Departamento eliminado correctamente.']);
    }

    // ─── CARGOS ───────────────────────────────────────────────────────────────

    public function indexCargos(Request $request)
    {
        $query = DB::table('CARGO')
            ->leftJoin('DEPARTAMENTO', 'CARGO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->leftJoin('CENTRO_COSTO', 'CARGO.ID_CENTROCOSTO', '=', 'CENTRO_COSTO.ID_CENTROCOSTO')
            ->select('CARGO.*', 'DEPARTAMENTO.NOMBREDEPARTAMENTO', 'CENTRO_COSTO.NOMBRE_CENTROCOSTO')
            ->orderBy('CARGO.ID_CARGO');

        return $this->paginateQuery($query, $request, ['NOMBRECARGO', 'NOMBREDEPARTAMENTO', 'NOMBRE_CENTROCOSTO']);
    }

    public function storeCargo(Request $request)
    {
        $request->validate([
            'ID_DEPARTAMENTO' => 'required|integer',
            'ID_CENTROCOSTO'  => 'nullable|integer',
            'ID_CARGO_PADRE'  => 'nullable|integer',
            'NIVEL_JERARQUICO'=> 'integer',
            'NOMBRECARGO'     => 'required|string|max:150',
            'CARGOESTADO'     => 'boolean',
        ]);

        $maxId = DB::table('CARGO')->max('ID_CARGO') ?? 0;
        $id    = $maxId + 1;

        DB::table('CARGO')->insert([
            'ID_CARGO'         => $id,
            'ID_DEPARTAMENTO'  => $request->ID_DEPARTAMENTO,
            'ID_CENTROCOSTO'   => $request->ID_CENTROCOSTO,
            'ID_CARGO_PADRE'   => $request->ID_CARGO_PADRE,
            'NIVEL_JERARQUICO' => $request->NIVEL_JERARQUICO ?? 1,
            'NOMBRECARGO'      => $request->NOMBRECARGO,
            'CARGOESTADO'      => $request->CARGOESTADO ?? true,
        ]);

        return response()->json(['ID_CARGO' => $id, 'NOMBRECARGO' => $request->NOMBRECARGO], 201);
    }

    public function updateCargo(Request $request, $id)
    {
        $request->validate([
            'ID_DEPARTAMENTO'  => 'required|integer',
            'ID_CENTROCOSTO'   => 'nullable|integer',
            'ID_CARGO_PADRE'   => 'nullable|integer',
            'NIVEL_JERARQUICO' => 'integer',
            'NOMBRECARGO'      => 'required|string|max:150',
            'CARGOESTADO'      => 'boolean',
        ]);

        DB::table('CARGO')->where('ID_CARGO', $id)->update([
            'ID_DEPARTAMENTO'  => $request->ID_DEPARTAMENTO,
            'ID_CENTROCOSTO'   => $request->ID_CENTROCOSTO,
            'ID_CARGO_PADRE'   => $request->ID_CARGO_PADRE,
            'NIVEL_JERARQUICO' => $request->NIVEL_JERARQUICO ?? 1,
            'NOMBRECARGO'      => $request->NOMBRECARGO,
            'CARGOESTADO'      => $request->CARGOESTADO ?? true,
        ]);

        return response()->json(['ID_CARGO' => $id, 'NOMBRECARGO' => $request->NOMBRECARGO]);
    }

    public function destroyCargo($id)
    {
        DB::table('CARGO')->where('ID_CARGO', $id)->update(['CARGOESTADO' => false]);
        return response()->json(['message' => 'Cargo inactivado correctamente.']);
    }

    // ─── SUCURSALES ───────────────────────────────────────────────────────────

    public function indexSucursales(Request $request)
    {
        $query = DB::table('SUCURSAL')
            ->leftJoin('EMPRESA', 'SUCURSAL.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('SUCURSAL.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('SUCURSAL.ID_SUCURSAL');

        return $this->paginateQuery($query, $request, ['NOMBRESUCURSAL', 'NOMBREEMPRESA', 'DIRECCION']);
    }

    public function storeSucursal(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'     => 'required|integer',
            'NOMBRESUCURSAL' => 'required|string|max:100',
            'DIRECCION'      => 'nullable|string|max:250',
            'ESACTIVA'       => 'boolean',
        ]);

        $maxId = DB::table('SUCURSAL')->max('ID_SUCURSAL') ?? 0;
        $id    = $maxId + 1;

        DB::table('SUCURSAL')->insert([
            'ID_SUCURSAL'    => $id,
            'ID_EMPRESA'     => $request->ID_EMPRESA,
            'NOMBRESUCURSAL' => $request->NOMBRESUCURSAL,
            'DIRECCION'      => $request->DIRECCION,
            'ESACTIVA'       => $request->ESACTIVA ?? true,
        ]);

        return response()->json(['ID_SUCURSAL' => $id, 'NOMBRESUCURSAL' => $request->NOMBRESUCURSAL], 201);
    }

    public function updateSucursal(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'     => 'required|integer',
            'NOMBRESUCURSAL' => 'required|string|max:100',
            'DIRECCION'      => 'nullable|string|max:250',
            'ESACTIVA'       => 'boolean',
        ]);

        DB::table('SUCURSAL')->where('ID_SUCURSAL', $id)->update([
            'ID_EMPRESA'     => $request->ID_EMPRESA,
            'NOMBRESUCURSAL' => $request->NOMBRESUCURSAL,
            'DIRECCION'      => $request->DIRECCION,
            'ESACTIVA'       => $request->ESACTIVA ?? true,
        ]);

        return response()->json(['ID_SUCURSAL' => $id, 'NOMBRESUCURSAL' => $request->NOMBRESUCURSAL]);
    }

    public function destroySucursal($id)
    {
        DB::table('SUCURSAL')->where('ID_SUCURSAL', $id)->update(['ESACTIVA' => false]);
        return response()->json(['message' => 'Sucursal inactivada correctamente.']);
    }

    // ─── BODEGAS ──────────────────────────────────────────────────────────────

    public function indexBodegas(Request $request)
    {
        $query = DB::table('BODEGA')
            ->leftJoin('EMPRESA', 'BODEGA.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('BODEGA.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('BODEGA.ID_BODEGA');

        return $this->paginateQuery($query, $request, ['NOMBREBODEGA', 'NOMBREEMPRESA']);
    }

    public function storeBodega(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'  => 'required|integer',
            'NOMBREBODEGA'=> 'required|string|max:200',
        ]);

        $maxId = DB::table('BODEGA')->max('ID_BODEGA') ?? 0;
        $id    = $maxId + 1;

        DB::table('BODEGA')->insert([
            'ID_BODEGA'    => $id,
            'ID_EMPRESA'   => $request->ID_EMPRESA,
            'NOMBREBODEGA' => $request->NOMBREBODEGA,
        ]);

        return response()->json(['ID_BODEGA' => $id, 'NOMBREBODEGA' => $request->NOMBREBODEGA], 201);
    }

    public function updateBodega(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'   => 'required|integer',
            'NOMBREBODEGA' => 'required|string|max:200',
        ]);

        DB::table('BODEGA')->where('ID_BODEGA', $id)->update([
            'ID_EMPRESA'   => $request->ID_EMPRESA,
            'NOMBREBODEGA' => $request->NOMBREBODEGA,
        ]);

        return response()->json(['ID_BODEGA' => $id, 'NOMBREBODEGA' => $request->NOMBREBODEGA]);
    }

    public function destroyBodega($id)
    {
        DB::table('BODEGA')->where('ID_BODEGA', $id)->delete();
        return response()->json(['message' => 'Bodega eliminada correctamente.']);
    }

    // ─── RUTAS ────────────────────────────────────────────────────────────────

    public function indexRutas(Request $request)
    {
        $query = DB::table('RUTA')
            ->leftJoin('EMPRESA', 'RUTA.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->leftJoin('CENTRO_COSTO', 'RUTA.ID_CENTROCOSTO', '=', 'CENTRO_COSTO.ID_CENTROCOSTO')
            ->select('RUTA.*', 'EMPRESA.NOMBREEMPRESA', 'CENTRO_COSTO.NOMBRE_CENTROCOSTO')
            ->orderBy('RUTA.ID_RUTA');

        return $this->paginateQuery($query, $request, ['NOMBRERUTA', 'NOMBREEMPRESA', 'NOMBRE_CENTROCOSTO']);
    }

    public function storeRuta(Request $request)
    {
        $request->validate([
            'ID_EMPRESA'    => 'required|integer',
            'ID_CENTROCOSTO'=> 'nullable|integer',
            'NOMBRERUTA'    => 'required|string|max:100',
            'ESACTIVA'      => 'boolean',
        ]);

        $maxId = DB::table('RUTA')->max('ID_RUTA') ?? 0;
        $id    = $maxId + 1;

        DB::table('RUTA')->insert([
            'ID_RUTA'       => $id,
            'ID_EMPRESA'    => $request->ID_EMPRESA,
            'ID_CENTROCOSTO'=> $request->ID_CENTROCOSTO,
            'NOMBRERUTA'    => $request->NOMBRERUTA,
            'ESACTIVA'      => $request->ESACTIVA ?? true,
        ]);

        return response()->json(['ID_RUTA' => $id, 'NOMBRERUTA' => $request->NOMBRERUTA], 201);
    }

    public function updateRuta(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA'     => 'required|integer',
            'ID_CENTROCOSTO' => 'nullable|integer',
            'NOMBRERUTA'     => 'required|string|max:100',
            'ESACTIVA'       => 'boolean',
        ]);

        DB::table('RUTA')->where('ID_RUTA', $id)->update([
            'ID_EMPRESA'    => $request->ID_EMPRESA,
            'ID_CENTROCOSTO'=> $request->ID_CENTROCOSTO,
            'NOMBRERUTA'    => $request->NOMBRERUTA,
            'ESACTIVA'      => $request->ESACTIVA ?? true,
        ]);

        return response()->json(['ID_RUTA' => $id, 'NOMBRERUTA' => $request->NOMBRERUTA]);
    }

    public function destroyRuta($id)
    {
        DB::table('RUTA')->where('ID_RUTA', $id)->update(['ESACTIVA' => false]);
        return response()->json(['message' => 'Ruta inactivada correctamente.']);
    }

    // ─── FIRMANTES ────────────────────────────────────────────────────────────

    public function indexFirmantes(Request $request)
    {
        $query = DB::table('EMPRESA_FIRMANTE')
            ->join('EMPRESA', 'EMPRESA_FIRMANTE.ID_EMPRESA', '=', 'EMPRESA.ID_EMPRESA')
            ->select('EMPRESA_FIRMANTE.*', 'EMPRESA.NOMBREEMPRESA')
            ->orderBy('EMPRESA_FIRMANTE.ID_EMPRESA')
            ->orderBy('EMPRESA_FIRMANTE.ORDEN');

        if ($request->filled('ID_EMPRESA')) {
            $query->where('EMPRESA_FIRMANTE.ID_EMPRESA', $request->ID_EMPRESA);
        }

        return $this->paginateQuery($query, $request, ['NOMBRE', 'CARGO', 'NOMBREEMPRESA']);
    }

    public function storeFirmante(Request $request)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'NOMBRE'     => 'required|string|max:200',
            'CARGO'      => 'nullable|string|max:150',
            'DUI'        => 'nullable|string|max:12',
            'ORDEN'      => 'integer|min:1',
            'ESACTIVO'   => 'boolean',
        ]);

        $maxId = DB::table('EMPRESA_FIRMANTE')->max('ID_FIRMANTE') ?? 0;

        DB::table('EMPRESA_FIRMANTE')->insert([
            'ID_FIRMANTE' => $maxId + 1,
            'ID_EMPRESA'  => $request->ID_EMPRESA,
            'NOMBRE'      => $request->NOMBRE,
            'CARGO'       => $request->CARGO,
            'DUI'         => $request->DUI,
            'ORDEN'       => $request->ORDEN ?? 1,
            'ESACTIVO'    => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_FIRMANTE' => $maxId + 1], 201);
    }

    public function updateFirmante(Request $request, $id)
    {
        $request->validate([
            'ID_EMPRESA' => 'required|integer',
            'NOMBRE'     => 'required|string|max:200',
            'CARGO'      => 'nullable|string|max:150',
            'DUI'        => 'nullable|string|max:12',
            'ORDEN'      => 'integer|min:1',
            'ESACTIVO'   => 'boolean',
        ]);

        DB::table('EMPRESA_FIRMANTE')->where('ID_FIRMANTE', $id)->update([
            'ID_EMPRESA' => $request->ID_EMPRESA,
            'NOMBRE'     => $request->NOMBRE,
            'CARGO'      => $request->CARGO,
            'DUI'        => $request->DUI,
            'ORDEN'      => $request->ORDEN ?? 1,
            'ESACTIVO'   => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_FIRMANTE' => $id]);
    }

    public function destroyFirmante($id)
    {
        DB::table('EMPRESA_FIRMANTE')->where('ID_FIRMANTE', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Firmante inactivado correctamente.']);
    }
}
