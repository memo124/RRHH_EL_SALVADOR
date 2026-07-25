<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoRRHHController extends Controller
{
    // ─── AFP ──────────────────────────────────────────────────────────────────

    public function indexAfp()
    {
        return response()->json(DB::table('AFP')->orderBy('ID_AFP')->get());
    }

    public function storeAfp(Request $request)
    {
        $request->validate([
            'NOMBREAFP'            => 'required|string|max:150',
            'CODIGOPREVISIONAL'    => 'nullable|string|max:25',
            'PORCENTAJEPATRONAL'   => 'required|numeric',
            'PORCENTAJEEMPLEADOR'  => 'required|numeric',
            'DEVENGADOMAXIMO'      => 'nullable|numeric',
            'DEVENGADOMINIMO'      => 'nullable|numeric',
            'ESACTIVO'             => 'boolean',
        ]);

        $maxId = DB::table('AFP')->max('ID_AFP') ?? 0;
        $id    = $maxId + 1;

        DB::table('AFP')->insert([
            'ID_AFP'               => $id,
            'NOMBREAFP'            => $request->NOMBREAFP,
            'CODIGOPREVISIONAL'    => $request->CODIGOPREVISIONAL,
            'PORCENTAJEPATRONAL'   => $request->PORCENTAJEPATRONAL,
            'PORCENTAJEEMPLEADOR'  => $request->PORCENTAJEEMPLEADOR,
            'DEVENGADOMAXIMO'      => $request->DEVENGADOMAXIMO,
            'DEVENGADOMINIMO'      => $request->DEVENGADOMINIMO,
            'ESACTIVO'             => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_AFP' => $id, 'NOMBREAFP' => $request->NOMBREAFP], 201);
    }

    public function updateAfp(Request $request, $id)
    {
        $request->validate([
            'NOMBREAFP'           => 'required|string|max:150',
            'CODIGOPREVISIONAL'   => 'nullable|string|max:25',
            'PORCENTAJEPATRONAL'  => 'required|numeric',
            'PORCENTAJEEMPLEADOR' => 'required|numeric',
            'DEVENGADOMAXIMO'     => 'nullable|numeric',
            'DEVENGADOMINIMO'     => 'nullable|numeric',
            'ESACTIVO'            => 'boolean',
        ]);

        DB::table('AFP')->where('ID_AFP', $id)->update([
            'NOMBREAFP'           => $request->NOMBREAFP,
            'CODIGOPREVISIONAL'   => $request->CODIGOPREVISIONAL,
            'PORCENTAJEPATRONAL'  => $request->PORCENTAJEPATRONAL,
            'PORCENTAJEEMPLEADOR' => $request->PORCENTAJEEMPLEADOR,
            'DEVENGADOMAXIMO'     => $request->DEVENGADOMAXIMO,
            'DEVENGADOMINIMO'     => $request->DEVENGADOMINIMO,
            'ESACTIVO'            => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_AFP' => $id, 'NOMBREAFP' => $request->NOMBREAFP]);
    }

    public function destroyAfp($id)
    {
        DB::table('AFP')->where('ID_AFP', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'AFP inactivada correctamente.']);
    }

    // ─── BANCO ────────────────────────────────────────────────────────────────

    public function indexBancos()
    {
        return response()->json(DB::table('BANCO')->orderBy('ID_BANCO')->get());
    }

    public function storeBanco(Request $request)
    {
        $request->validate([
            'ID_PAIS'     => 'required|integer',
            'NOMBREBANCO' => 'required|string|max:100',
            'ALIAS'       => 'nullable|string|max:25',
            'BANCOACTIVO' => 'boolean',
        ]);

        $maxId = DB::table('BANCO')->max('ID_BANCO') ?? 0;
        $id    = $maxId + 1;

        DB::table('BANCO')->insert([
            'ID_BANCO'    => $id,
            'ID_PAIS'     => $request->ID_PAIS,
            'NOMBREBANCO' => $request->NOMBREBANCO,
            'ALIAS'       => $request->ALIAS,
            'BANCOACTIVO' => $request->BANCOACTIVO ?? true,
        ]);

        return response()->json(['ID_BANCO' => $id, 'NOMBREBANCO' => $request->NOMBREBANCO], 201);
    }

    public function updateBanco(Request $request, $id)
    {
        $request->validate([
            'ID_PAIS'     => 'required|integer',
            'NOMBREBANCO' => 'required|string|max:100',
            'ALIAS'       => 'nullable|string|max:25',
            'BANCOACTIVO' => 'boolean',
        ]);

        DB::table('BANCO')->where('ID_BANCO', $id)->update([
            'ID_PAIS'     => $request->ID_PAIS,
            'NOMBREBANCO' => $request->NOMBREBANCO,
            'ALIAS'       => $request->ALIAS,
            'BANCOACTIVO' => $request->BANCOACTIVO ?? true,
        ]);

        return response()->json(['ID_BANCO' => $id, 'NOMBREBANCO' => $request->NOMBREBANCO]);
    }

    public function destroyBanco($id)
    {
        DB::table('BANCO')->where('ID_BANCO', $id)->update(['BANCOACTIVO' => false]);
        return response()->json(['message' => 'Banco inactivado correctamente.']);
    }

    // ─── ESTADO CIVIL ─────────────────────────────────────────────────────────

    public function indexEstadoCivil()
    {
        return response()->json(DB::table('ESTADO_CIVIL')->orderBy('ID_ESTADOCIVIL')->get());
    }

    public function storeEstadoCivil(Request $request)
    {
        $request->validate([
            'NOMBREESTADOCIVIL' => 'required|string|max:50',
        ]);

        $maxId = DB::table('ESTADO_CIVIL')->max('ID_ESTADOCIVIL') ?? 0;
        $id    = $maxId + 1;

        DB::table('ESTADO_CIVIL')->insert([
            'ID_ESTADOCIVIL'    => $id,
            'NOMBREESTADOCIVIL' => $request->NOMBREESTADOCIVIL,
        ]);

        return response()->json(['ID_ESTADOCIVIL' => $id, 'NOMBREESTADOCIVIL' => $request->NOMBREESTADOCIVIL], 201);
    }

    public function updateEstadoCivil(Request $request, $id)
    {
        $request->validate([
            'NOMBREESTADOCIVIL' => 'required|string|max:50',
        ]);

        DB::table('ESTADO_CIVIL')->where('ID_ESTADOCIVIL', $id)->update([
            'NOMBREESTADOCIVIL' => $request->NOMBREESTADOCIVIL,
        ]);

        return response()->json(['ID_ESTADOCIVIL' => $id, 'NOMBREESTADOCIVIL' => $request->NOMBREESTADOCIVIL]);
    }

    public function destroyEstadoCivil($id)
    {
        DB::table('ESTADO_CIVIL')->where('ID_ESTADOCIVIL', $id)->delete();
        return response()->json(['message' => 'Estado civil eliminado correctamente.']);
    }

    // ─── EDUCACIÓN ACADÉMICA ──────────────────────────────────────────────────

    public function indexEducacion()
    {
        return response()->json(DB::table('EDUCACION_ACADEMICA')->orderBy('ID_EDUCACIONACADEMICA')->get());
    }

    public function storeEducacion(Request $request)
    {
        $request->validate([
            'DESCRIPCION' => 'required|string|max:150',
            'ACTIVO'      => 'boolean',
        ]);

        $maxId = DB::table('EDUCACION_ACADEMICA')->max('ID_EDUCACIONACADEMICA') ?? 0;
        $id    = $maxId + 1;

        DB::table('EDUCACION_ACADEMICA')->insert([
            'ID_EDUCACIONACADEMICA' => $id,
            'DESCRIPCION'           => $request->DESCRIPCION,
            'ACTIVO'                => $request->ACTIVO ?? true,
        ]);

        return response()->json(['ID_EDUCACIONACADEMICA' => $id, 'DESCRIPCION' => $request->DESCRIPCION], 201);
    }

    public function updateEducacion(Request $request, $id)
    {
        $request->validate([
            'DESCRIPCION' => 'required|string|max:150',
            'ACTIVO'      => 'boolean',
        ]);

        DB::table('EDUCACION_ACADEMICA')->where('ID_EDUCACIONACADEMICA', $id)->update([
            'DESCRIPCION' => $request->DESCRIPCION,
            'ACTIVO'      => $request->ACTIVO ?? true,
        ]);

        return response()->json(['ID_EDUCACIONACADEMICA' => $id, 'DESCRIPCION' => $request->DESCRIPCION]);
    }

    public function destroyEducacion($id)
    {
        DB::table('EDUCACION_ACADEMICA')->where('ID_EDUCACIONACADEMICA', $id)->update(['ACTIVO' => false]);
        return response()->json(['message' => 'Nivel educativo inactivado correctamente.']);
    }

    // ─── PROFESIONES Y OFICIOS ────────────────────────────────────────────────

    public function indexProfesiones()
    {
        return response()->json(DB::table('PROFESIONES_OFICIOS')->orderBy('ID_PROFESIONES_OFICIOS')->get());
    }

    public function storeProfesion(Request $request)
    {
        $request->validate([
            'PROFESION_OFICIO' => 'required|string|max:250',
        ]);

        $maxId = DB::table('PROFESIONES_OFICIOS')->max('ID_PROFESIONES_OFICIOS') ?? 0;
        $id    = $maxId + 1;

        DB::table('PROFESIONES_OFICIOS')->insert([
            'ID_PROFESIONES_OFICIOS' => $id,
            'PROFESION_OFICIO'       => $request->PROFESION_OFICIO,
        ]);

        return response()->json(['ID_PROFESIONES_OFICIOS' => $id, 'PROFESION_OFICIO' => $request->PROFESION_OFICIO], 201);
    }

    public function updateProfesion(Request $request, $id)
    {
        $request->validate([
            'PROFESION_OFICIO' => 'required|string|max:250',
        ]);

        DB::table('PROFESIONES_OFICIOS')->where('ID_PROFESIONES_OFICIOS', $id)->update([
            'PROFESION_OFICIO' => $request->PROFESION_OFICIO,
        ]);

        return response()->json(['ID_PROFESIONES_OFICIOS' => $id, 'PROFESION_OFICIO' => $request->PROFESION_OFICIO]);
    }

    public function destroyProfesion($id)
    {
        DB::table('PROFESIONES_OFICIOS')->where('ID_PROFESIONES_OFICIOS', $id)->delete();
        return response()->json(['message' => 'Profesión/Oficio eliminado correctamente.']);
    }

    // ─── PERFIL DE PAGO ───────────────────────────────────────────────────────

    public function indexPerfilPago()
    {
        return response()->json(DB::table('PERFIL_PAGO')->orderBy('ID_PERFILPAGO')->get());
    }

    public function storePerfilPago(Request $request)
    {
        $request->validate([
            'PEFILPAGO'            => 'required|string|max:100',
            'GRATIFICACIONES'      => 'boolean',
            'EXTRA_GRATIFICACIONES'=> 'boolean',
        ]);

        $maxId = DB::table('PERFIL_PAGO')->max('ID_PERFILPAGO') ?? 0;
        $id    = $maxId + 1;

        DB::table('PERFIL_PAGO')->insert([
            'ID_PERFILPAGO'        => $id,
            'PEFILPAGO'            => $request->PEFILPAGO,
            'GRATIFICACIONES'      => $request->GRATIFICACIONES ?? true,
            'EXTRA_GRATIFICACIONES'=> $request->EXTRA_GRATIFICACIONES ?? true,
        ]);

        return response()->json(['ID_PERFILPAGO' => $id, 'PEFILPAGO' => $request->PEFILPAGO], 201);
    }

    public function updatePerfilPago(Request $request, $id)
    {
        $request->validate([
            'PEFILPAGO'            => 'required|string|max:100',
            'GRATIFICACIONES'      => 'boolean',
            'EXTRA_GRATIFICACIONES'=> 'boolean',
        ]);

        DB::table('PERFIL_PAGO')->where('ID_PERFILPAGO', $id)->update([
            'PEFILPAGO'            => $request->PEFILPAGO,
            'GRATIFICACIONES'      => $request->GRATIFICACIONES ?? true,
            'EXTRA_GRATIFICACIONES'=> $request->EXTRA_GRATIFICACIONES ?? true,
        ]);

        return response()->json(['ID_PERFILPAGO' => $id, 'PEFILPAGO' => $request->PEFILPAGO]);
    }

    public function destroyPerfilPago($id)
    {
        DB::table('PERFIL_PAGO')->where('ID_PERFILPAGO', $id)->delete();
        return response()->json(['message' => 'Perfil de pago eliminado correctamente.']);
    }

    // ─── FRECUENCIA DE PAGO ───────────────────────────────────────────────────

    public function indexFrecuenciaPago()
    {
        return response()->json(DB::table('FRECUENCIA_PAGO')->orderBy('ID_FRECUENCIAPAGO')->get());
    }

    public function storeFrecuenciaPago(Request $request)
    {
        $request->validate([
            'NOMBREFRECUENCIA' => 'required|string|max:50',
            'NUMERODIAS'       => 'required|integer',
        ]);

        $maxId = DB::table('FRECUENCIA_PAGO')->max('ID_FRECUENCIAPAGO') ?? 0;
        $id    = $maxId + 1;

        DB::table('FRECUENCIA_PAGO')->insert([
            'ID_FRECUENCIAPAGO' => $id,
            'NOMBREFRECUENCIA'  => $request->NOMBREFRECUENCIA,
            'NUMERODIAS'        => $request->NUMERODIAS,
        ]);

        return response()->json(['ID_FRECUENCIAPAGO' => $id, 'NOMBREFRECUENCIA' => $request->NOMBREFRECUENCIA], 201);
    }

    public function updateFrecuenciaPago(Request $request, $id)
    {
        $request->validate([
            'NOMBREFRECUENCIA' => 'required|string|max:50',
            'NUMERODIAS'       => 'required|integer',
        ]);

        DB::table('FRECUENCIA_PAGO')->where('ID_FRECUENCIAPAGO', $id)->update([
            'NOMBREFRECUENCIA' => $request->NOMBREFRECUENCIA,
            'NUMERODIAS'       => $request->NUMERODIAS,
        ]);

        return response()->json(['ID_FRECUENCIAPAGO' => $id, 'NOMBREFRECUENCIA' => $request->NOMBREFRECUENCIA]);
    }

    public function destroyFrecuenciaPago($id)
    {
        DB::table('FRECUENCIA_PAGO')->where('ID_FRECUENCIAPAGO', $id)->delete();
        return response()->json(['message' => 'Frecuencia de pago eliminada correctamente.']);
    }

    // ─── TIPO PLANILLA ────────────────────────────────────────────────────────

    public function indexTipoPlanilla()
    {
        return response()->json(DB::table('TIPO_PLANILLA')->orderBy('ID_TIPOPLANILLA')->get());
    }

    public function storeTipoPlanilla(Request $request)
    {
        $request->validate([
            'TIPOPLANILLA'  => 'required|string|max:100',
            'DESCRIPCION'   => 'nullable|string|max:250',
            'APLICA_ISSS'   => 'boolean',
            'APLICA_AFP'    => 'boolean',
            'APLICA_RENTA'  => 'boolean',
            'APLICA_INSAFORP'=> 'boolean',
            'ES_EVENTUAL'   => 'boolean',
            'ESACTIVO'      => 'boolean',
        ]);

        $maxId = DB::table('TIPO_PLANILLA')->max('ID_TIPOPLANILLA') ?? 0;
        $id    = $maxId + 1;

        DB::table('TIPO_PLANILLA')->insert([
            'ID_TIPOPLANILLA'  => $id,
            'TIPOPLANILLA'     => $request->TIPOPLANILLA,
            'DESCRIPCION'      => $request->DESCRIPCION,
            'APLICA_ISSS'      => $request->APLICA_ISSS ?? true,
            'APLICA_AFP'       => $request->APLICA_AFP ?? true,
            'APLICA_RENTA'     => $request->APLICA_RENTA ?? true,
            'APLICA_INSAFORP'  => $request->APLICA_INSAFORP ?? true,
            'ES_EVENTUAL'      => $request->ES_EVENTUAL ?? false,
            'ESACTIVO'         => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_TIPOPLANILLA' => $id, 'TIPOPLANILLA' => $request->TIPOPLANILLA], 201);
    }

    public function updateTipoPlanilla(Request $request, $id)
    {
        $request->validate([
            'TIPOPLANILLA'   => 'required|string|max:100',
            'DESCRIPCION'    => 'nullable|string|max:250',
            'APLICA_ISSS'    => 'boolean',
            'APLICA_AFP'     => 'boolean',
            'APLICA_RENTA'   => 'boolean',
            'APLICA_INSAFORP'=> 'boolean',
            'ES_EVENTUAL'    => 'boolean',
            'ESACTIVO'       => 'boolean',
        ]);

        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', $id)->update([
            'TIPOPLANILLA'   => $request->TIPOPLANILLA,
            'DESCRIPCION'    => $request->DESCRIPCION,
            'APLICA_ISSS'    => $request->APLICA_ISSS ?? true,
            'APLICA_AFP'     => $request->APLICA_AFP ?? true,
            'APLICA_RENTA'   => $request->APLICA_RENTA ?? true,
            'APLICA_INSAFORP'=> $request->APLICA_INSAFORP ?? true,
            'ES_EVENTUAL'    => $request->ES_EVENTUAL ?? false,
            'ESACTIVO'       => $request->ESACTIVO ?? true,
        ]);

        return response()->json(['ID_TIPOPLANILLA' => $id, 'TIPOPLANILLA' => $request->TIPOPLANILLA]);
    }

    public function destroyTipoPlanilla($id)
    {
        DB::table('TIPO_PLANILLA')->where('ID_TIPOPLANILLA', $id)->update(['ESACTIVO' => false]);
        return response()->json(['message' => 'Tipo de planilla inactivado correctamente.']);
    }

    // ─── TIPO PRÉSTAMO ────────────────────────────────────────────────────────

    public function indexTipoPrestamo()
    {
        return response()->json(DB::table('TIPO_PRESTAMO')->orderBy('ID_TIPOPRESTAMO')->get());
    }

    public function storeTipoPrestamo(Request $request)
    {
        $request->validate([
            'NOMBREPRESTAMO' => 'required|string|max:150',
            'OBSERVACIONES'  => 'nullable|string|max:250',
        ]);

        $maxId = DB::table('TIPO_PRESTAMO')->max('ID_TIPOPRESTAMO') ?? 0;
        $id    = $maxId + 1;

        DB::table('TIPO_PRESTAMO')->insert([
            'ID_TIPOPRESTAMO' => $id,
            'NOMBREPRESTAMO'  => $request->NOMBREPRESTAMO,
            'OBSERVACIONES'   => $request->OBSERVACIONES,
        ]);

        return response()->json(['ID_TIPOPRESTAMO' => $id, 'NOMBREPRESTAMO' => $request->NOMBREPRESTAMO], 201);
    }

    public function updateTipoPrestamo(Request $request, $id)
    {
        $request->validate([
            'NOMBREPRESTAMO' => 'required|string|max:150',
            'OBSERVACIONES'  => 'nullable|string|max:250',
        ]);

        DB::table('TIPO_PRESTAMO')->where('ID_TIPOPRESTAMO', $id)->update([
            'NOMBREPRESTAMO' => $request->NOMBREPRESTAMO,
            'OBSERVACIONES'  => $request->OBSERVACIONES,
        ]);

        return response()->json(['ID_TIPOPRESTAMO' => $id, 'NOMBREPRESTAMO' => $request->NOMBREPRESTAMO]);
    }

    public function destroyTipoPrestamo($id)
    {
        DB::table('TIPO_PRESTAMO')->where('ID_TIPOPRESTAMO', $id)->delete();
        return response()->json(['message' => 'Tipo de préstamo eliminado correctamente.']);
    }

    // ─── HORAS EXTRAS ─────────────────────────────────────────────────────────

    public function indexHorasExtras()
    {
        return response()->json(DB::table('HORAS_EXTRAS')->orderBy('ID_HORASEXTRAS')->get());
    }

    public function storeHorasExtras(Request $request)
    {
        $request->validate([
            'TIPOHORAEXTRA'    => 'required|string|max:100',
            'PORCENTAJEEXTRA'  => 'required|numeric',
            'FACTOR'           => 'required|numeric',
            'MODALIDAD'        => 'required|string|in:FIJA,ADICIONAL',
            'JORNADA'          => 'required|string|in:DIURNA,NOCTURNA',
            'ES_DOMINICAL'     => 'boolean',
            'CODIGO'           => 'nullable|string|max:40',
        ]);

        $maxId = DB::table('HORAS_EXTRAS')->max('ID_HORASEXTRAS') ?? 0;
        $id    = $maxId + 1;

        DB::table('HORAS_EXTRAS')->insert([
            'ID_HORASEXTRAS'  => $id,
            'TIPOHORAEXTRA'   => $request->TIPOHORAEXTRA,
            'PORCENTAJEEXTRA' => $request->PORCENTAJEEXTRA,
            'FACTOR'          => $request->FACTOR,
            'MODALIDAD'       => $request->MODALIDAD,
            'JORNADA'         => $request->JORNADA,
            'ES_DOMINICAL'    => $request->ES_DOMINICAL ?? false,
            'CODIGO'          => $request->CODIGO,
        ]);

        return response()->json(['ID_HORASEXTRAS' => $id, 'TIPOHORAEXTRA' => $request->TIPOHORAEXTRA], 201);
    }

    public function updateHorasExtras(Request $request, $id)
    {
        $request->validate([
            'TIPOHORAEXTRA'   => 'required|string|max:100',
            'PORCENTAJEEXTRA' => 'required|numeric',
            'FACTOR'          => 'required|numeric',
            'MODALIDAD'       => 'required|string|in:FIJA,ADICIONAL',
            'JORNADA'         => 'required|string|in:DIURNA,NOCTURNA',
            'ES_DOMINICAL'    => 'boolean',
            'CODIGO'          => 'nullable|string|max:40',
        ]);

        DB::table('HORAS_EXTRAS')->where('ID_HORASEXTRAS', $id)->update([
            'TIPOHORAEXTRA'   => $request->TIPOHORAEXTRA,
            'PORCENTAJEEXTRA' => $request->PORCENTAJEEXTRA,
            'FACTOR'          => $request->FACTOR,
            'MODALIDAD'       => $request->MODALIDAD,
            'JORNADA'         => $request->JORNADA,
            'ES_DOMINICAL'    => $request->ES_DOMINICAL ?? false,
            'CODIGO'          => $request->CODIGO,
        ]);

        return response()->json(['ID_HORASEXTRAS' => $id, 'TIPOHORAEXTRA' => $request->TIPOHORAEXTRA]);
    }

    public function destroyHorasExtras($id)
    {
        DB::table('HORAS_EXTRAS')->where('ID_HORASEXTRAS', $id)->delete();
        return response()->json(['message' => 'Tipo de hora extra eliminado correctamente.']);
    }
}
