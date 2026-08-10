<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReclutamientoOnboardingService
{
    /**
     * Crea un EMPLEADO a partir de un candidato en etapa contratado.
     *
     * @param  array{DUI:string,GENERO:string,FECHANACIMIENTO:string,FECHAINGRESO?:string,SALARIOMENSUAL:float|int,ID_TIPOCONTRATACION:int,ID_DISTRITO:int,CODIGOEMPLEADO?:string,ID_EMPRESA?:int,ID_DEPARTAMENTO?:int,ID_CARGO?:int,NIT?:string,ISSS?:string,NUP?:string,ID_AFP?:int,ID_BANCO?:int}  $datos
     * @return array{ID_EMPLEADO:int,CODIGOEMPLEADO:string,message:string}
     */
    public function contratar(int $idCandidato, array $datos): array
    {
        $candidato = DB::table('CANDIDATO')
            ->where('ID_CANDIDATO', $idCandidato)
            ->where('ESACTIVO', true)
            ->first();

        if (!$candidato) {
            throw ValidationException::withMessages(['ID_CANDIDATO' => 'Candidato no encontrado.']);
        }

        if (!empty($candidato->ID_EMPLEADO)) {
            throw ValidationException::withMessages(['ID_CANDIDATO' => 'Este candidato ya fue contratado (empleado #' . $candidato->ID_EMPLEADO . ').']);
        }

        $vacante = DB::table('VACANTE')->where('ID_VACANTE', $candidato->ID_VACANTE)->first();
        if (!$vacante) {
            throw ValidationException::withMessages(['ID_VACANTE' => 'Vacante no encontrada.']);
        }

        $dui = trim($datos['DUI']);
        if (DB::table('EMPLEADO')->where('DUI', $dui)->exists()) {
            throw ValidationException::withMessages(['DUI' => 'Ya existe un empleado con este DUI.']);
        }

        $email = $datos['CORREOELECTRONICO'] ?? $candidato->EMAIL;
        if ($email && DB::table('EMPLEADO')->where('CORREOELECTRONICO', $email)->where('ESACTIVO', true)->exists()) {
            throw ValidationException::withMessages(['CORREOELECTRONICO' => 'Ya existe un empleado activo con este correo.']);
        }

        $idEmpresa = (int) ($datos['ID_EMPRESA'] ?? $vacante->ID_EMPRESA);
        $idDepto = (int) ($datos['ID_DEPARTAMENTO'] ?? $vacante->ID_DEPARTAMENTO);
        $idCargo = (int) ($datos['ID_CARGO'] ?? $vacante->ID_CARGO);

        if (!$idEmpresa || !$idDepto || !$idCargo) {
            throw ValidationException::withMessages([
                'ID_EMPRESA' => 'Debe indicar empresa, departamento y cargo (desde la vacante o el formulario).',
            ]);
        }

        $nombres = trim($candidato->NOMBRES);
        $apellidos = trim((string) ($candidato->APELLIDOS ?? ''));
        $parts = preg_split('/\s+/', $apellidos, 2) ?: [];
        $apellido1 = $parts[0] ?? 'N/A';
        $apellido2 = $parts[1] ?? null;

        $codigo = $datos['CODIGOEMPLEADO'] ?? $this->generarCodigo($idEmpresa);
        if (DB::table('EMPLEADO')->where('CODIGOEMPLEADO', $codigo)->exists()) {
            throw ValidationException::withMessages(['CODIGOEMPLEADO' => 'El código de empleado ya existe.']);
        }

        $salarioMensual = (float) $datos['SALARIOMENSUAL'];
        $idEmpleado = (DB::table('EMPLEADO')->max('ID_EMPLEADO') ?? 0) + 1;

        DB::transaction(function () use (
            $idEmpleado, $idEmpresa, $idDepto, $idCargo, $datos, $candidato,
            $nombres, $apellido1, $apellido2, $dui, $email, $codigo, $salarioMensual, $idCandidato
        ) {
            DB::table('EMPLEADO')->insert([
                'ID_EMPLEADO' => $idEmpleado,
                'ID_EMPRESA' => $idEmpresa,
                'ID_DEPARTAMENTO' => $idDepto,
                'ID_CARGO' => $idCargo,
                'ID_TIPOCONTRATACION' => $datos['ID_TIPOCONTRATACION'],
                'ID_DISTRITO' => $datos['ID_DISTRITO'],
                'ID_AFP' => $datos['ID_AFP'] ?? null,
                'ID_BANCO' => $datos['ID_BANCO'] ?? null,
                'CODIGOEMPLEADO' => $codigo,
                'NOMBRES' => $nombres,
                'APELLIDO_1' => $apellido1,
                'APELLIDO_2' => $apellido2,
                'DUI' => $dui,
                'NIT' => $datos['NIT'] ?? null,
                'ISSS' => $datos['ISSS'] ?? null,
                'NUP' => $datos['NUP'] ?? null,
                'GENERO' => $datos['GENERO'],
                'FECHANACIMIENTO' => $datos['FECHANACIMIENTO'],
                'FECHAINGRESO' => $datos['FECHAINGRESO'] ?? now()->toDateString(),
                'SALARIOMENSUAL' => $salarioMensual,
                'SALARIODIARIO' => $salarioMensual / 30.0,
                'HORAS_EXTRAS_FIJAS_DIURAS' => 0,
                'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 0,
                'CORREOELECTRONICO' => $email,
                'TELEFONOCELULAR' => $datos['TELEFONOCELULAR'] ?? $candidato->TELEFONO,
                'DIRECCION' => $datos['DIRECCION'] ?? null,
                'NUMEROCUENTA' => $datos['NUMEROCUENTA'] ?? null,
                'ESACTIVO' => true,
                'JUBILADO' => false,
                'ID_PERFILPAGO' => 1,
            ]);

            if ($candidato->ID_ADJUNTO_CV) {
                DB::table('ADJUNTO')->where('ID_ADJUNTO', $candidato->ID_ADJUNTO_CV)->update([
                    'ID_EMPLEADO' => $idEmpleado,
                    'ORIGEN' => 'empleado',
                    'ID_ORIGEN' => $idEmpleado,
                ]);
            }

            DB::table('CANDIDATO')->where('ID_CANDIDATO', $idCandidato)->update([
                'ESTADO' => 'contratado',
                'ID_EMPLEADO' => $idEmpleado,
            ]);
        });

        return [
            'ID_EMPLEADO' => $idEmpleado,
            'CODIGOEMPLEADO' => $codigo,
            'message' => 'Empleado creado desde reclutamiento.',
        ];
    }

    public function preview(int $idCandidato): array
    {
        $candidato = DB::table('CANDIDATO as C')
            ->leftJoin('VACANTE as V', 'C.ID_VACANTE', '=', 'V.ID_VACANTE')
            ->leftJoin('EMPRESA as E', 'V.ID_EMPRESA', '=', 'E.ID_EMPRESA')
            ->leftJoin('DEPARTAMENTO as D', 'V.ID_DEPARTAMENTO', '=', 'D.ID_DEPARTAMENTO')
            ->leftJoin('CARGO as G', 'V.ID_CARGO', '=', 'G.ID_CARGO')
            ->where('C.ID_CANDIDATO', $idCandidato)
            ->where('C.ESACTIVO', true)
            ->select(
                'C.*',
                'V.TITULO as VACANTE_TITULO',
                'V.ID_EMPRESA',
                'V.ID_DEPARTAMENTO',
                'V.ID_CARGO',
                'E.NOMBREEMPRESA',
                'D.NOMBREDEPARTAMENTO',
                'G.NOMBRECARGO'
            )
            ->first();

        if (!$candidato) {
            throw ValidationException::withMessages(['ID_CANDIDATO' => 'Candidato no encontrado.']);
        }

        $tipoDefault = DB::table('TIPO_CONTRATACION')->where('ESACTIVO', true)->orderBy('ID_TIPOCONTRATACION')->value('ID_TIPOCONTRATACION');
        $distritoDefault = DB::table('DISTRITO')->where('ESACTIVO', true)->orderBy('ID_DISTRITO')->value('ID_DISTRITO');

        return [
            'candidato' => $candidato,
            'defaults' => [
                'ID_EMPRESA' => $candidato->ID_EMPRESA,
                'ID_DEPARTAMENTO' => $candidato->ID_DEPARTAMENTO,
                'ID_CARGO' => $candidato->ID_CARGO,
                'CORREOELECTRONICO' => $candidato->EMAIL,
                'TELEFONOCELULAR' => $candidato->TELEFONO,
                'ID_TIPOCONTRATACION' => $tipoDefault,
                'ID_DISTRITO' => $distritoDefault,
                'FECHAINGRESO' => now()->toDateString(),
                'CODIGOEMPLEADO' => $candidato->ID_EMPRESA ? $this->generarCodigo((int) $candidato->ID_EMPRESA) : null,
            ],
        ];
    }

    private function generarCodigo(int $idEmpresa): string
    {
        $seq = (DB::table('EMPLEADO')->where('ID_EMPRESA', $idEmpresa)->count()) + 1;
        return sprintf('E%02d-%04d', $idEmpresa, $seq);
    }
}
