<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\EmpresaFirmante;
use App\Models\PlantillaContrato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContratoTemplateService
{
    public function __construct(
        protected NumeroALetrasService $numeroALetras,
        protected ContratoBeneficiosService $beneficios,
    ) {
    }

    /**
     * Campos dinámicos disponibles para plantillas de contrato.
     */
    public function camposDisponibles(): array
    {
        return [
            ['key' => '{{empleado.nombre_completo}}', 'label' => 'Nombre completo del empleado'],
            ['key' => '{{empleado.nombres}}', 'label' => 'Nombres del empleado'],
            ['key' => '{{empleado.apellidos}}', 'label' => 'Apellidos del empleado'],
            ['key' => '{{empleado.dui}}', 'label' => 'DUI del empleado'],
            ['key' => '{{empleado.nit}}', 'label' => 'NIT del empleado'],
            ['key' => '{{empleado.cargo}}', 'label' => 'Cargo'],
            ['key' => '{{empleado.departamento}}', 'label' => 'Departamento'],
            ['key' => '{{empleado.salario}}', 'label' => 'Salario (número)'],
            ['key' => '{{empleado.salario_letras}}', 'label' => 'Salario en letras'],
            ['key' => '{{empleado.fecha_ingreso}}', 'label' => 'Fecha de ingreso'],
            ['key' => '{{empleado.codigo}}', 'label' => 'Código de empleado'],
            ['key' => '{{empleado.tipo_contratacion}}', 'label' => 'Tipo de contratación'],
            ['key' => '{{empleado.antiguedad_anios}}', 'label' => 'Años de antigüedad (enteros)'],
            ['key' => '{{empleado.antiguedad_texto}}', 'label' => 'Antigüedad en texto'],
            ['key' => '{{beneficios.aguinaldo_aplica}}', 'label' => '¿Aplica aguinaldo? (Sí/No)'],
            ['key' => '{{beneficios.dias_aguinaldo}}', 'label' => 'Días de aguinaldo'],
            ['key' => '{{beneficios.monto_aguinaldo}}', 'label' => 'Monto aguinaldo'],
            ['key' => '{{beneficios.aguinaldo_letras}}', 'label' => 'Aguinaldo en letras'],
            ['key' => '{{beneficios.quincena25_aplica}}', 'label' => '¿Aplica quincena 25? (Sí/No)'],
            ['key' => '{{beneficios.quincena25_monto}}', 'label' => 'Monto quincena 25'],
            ['key' => '{{beneficios.quincena25_letras}}', 'label' => 'Quincena 25 en letras'],
            ['key' => '{{beneficios.quincena25_detalle}}', 'label' => 'Detalle quincena 25'],
            ['key' => '{{empresa.nombre}}', 'label' => 'Nombre de la empresa'],
            ['key' => '{{empresa.nit}}', 'label' => 'NIT de la empresa'],
            ['key' => '{{empresa.direccion}}', 'label' => 'Dirección de la empresa'],
            ['key' => '{{empresa.dueno}}', 'label' => 'Nombre del dueño/representante'],
            ['key' => '{{empresa.dueno_dui}}', 'label' => 'DUI del dueño'],
            ['key' => '{{contrato.numero}}', 'label' => 'Número de contrato'],
            ['key' => '{{contrato.fecha_inicio}}', 'label' => 'Fecha inicio contrato'],
            ['key' => '{{contrato.fecha_fin}}', 'label' => 'Fecha fin contrato'],
            ['key' => '{{contrato.vigencia}}', 'label' => 'Texto de vigencia (con/sin fecha fin)'],
            ['key' => '{{fecha_actual}}', 'label' => 'Fecha actual'],
            ['key' => '{{clausulas}}', 'label' => 'Cláusulas adicionales'],
            ['key' => '{{firmantes}}', 'label' => 'Bloque de firmantes'],
        ];
    }

    public function render(
        PlantillaContrato $plantilla,
        Contrato $contrato,
        array $camposExtra = [],
        ?Carbon $fechaReferencia = null,
    ): string {
        $empleado = DB::table('EMPLEADO')
            ->leftJoin('CARGO', 'EMPLEADO.ID_CARGO', '=', 'CARGO.ID_CARGO')
            ->leftJoin('DEPARTAMENTO', 'EMPLEADO.ID_DEPARTAMENTO', '=', 'DEPARTAMENTO.ID_DEPARTAMENTO')
            ->leftJoin('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->where('EMPLEADO.ID_EMPLEADO', $contrato->ID_EMPLEADO)
            ->select(
                'EMPLEADO.*',
                'CARGO.NOMBRECARGO',
                'DEPARTAMENTO.NOMBREDEPARTAMENTO',
                'TIPO_CONTRATACION.TIPOCONTRATACION'
            )
            ->first();

        $empresa = Empresa::find($contrato->ID_EMPRESA);
        $firmantes = EmpresaFirmante::where('ID_EMPRESA', $contrato->ID_EMPRESA)
            ->where('ESACTIVO', true)
            ->orderBy('ORDEN')
            ->get();

        $salario = (float) ($contrato->SALARIO ?? $empleado->SALARIOMENSUAL ?? 0);
        $nombreCompleto = trim(($empleado->NOMBRES ?? '') . ' ' . ($empleado->APELLIDO_1 ?? '') . ' ' . ($empleado->APELLIDO_2 ?? ''));
        $apellidos = trim(($empleado->APELLIDO_1 ?? '') . ' ' . ($empleado->APELLIDO_2 ?? ''));

        $fechaRef = $fechaReferencia ?? ($contrato->FECHA_INICIO ? Carbon::parse($contrato->FECHA_INICIO) : Carbon::now());
        $empleadoModel = Empleado::with('tipoContratacion')->find($contrato->ID_EMPLEADO);
        $beneficiosData = $empleadoModel
            ? $this->beneficios->calcularParaEmpleado($empleadoModel, $fechaRef)
            : $this->beneficiosDemo();

        $esHtml = strtoupper($plantilla->FORMATO ?? 'HTML') === 'HTML';

        $replacements = array_merge([
            '{{empleado.nombre_completo}}' => $nombreCompleto,
            '{{empleado.nombres}}' => $empleado->NOMBRES ?? '',
            '{{empleado.apellidos}}' => $apellidos,
            '{{empleado.dui}}' => $empleado->DUI ?? '',
            '{{empleado.nit}}' => $empleado->NIT ?? '',
            '{{empleado.cargo}}' => $empleado->NOMBRECARGO ?? '',
            '{{empleado.departamento}}' => $empleado->NOMBREDEPARTAMENTO ?? '',
            '{{empleado.salario}}' => number_format($salario, 2),
            '{{empleado.salario_letras}}' => $this->numeroALetras->convertir($salario),
            '{{empleado.fecha_ingreso}}' => $empleado->FECHAINGRESO
                ? \Carbon\Carbon::parse($empleado->FECHAINGRESO)->format('d/m/Y')
                : '',
            '{{empleado.codigo}}' => $empleado->CODIGOEMPLEADO ?? '',
            '{{empleado.tipo_contratacion}}' => $empleado->TIPOCONTRATACION ?? '',
            '{{empresa.nombre}}' => $empresa->NOMBREEMPRESA ?? '',
            '{{empresa.nit}}' => $empresa->NUMERONIT ?? '',
            '{{empresa.direccion}}' => $empresa->DIRECCION ?? '',
            '{{empresa.dueno}}' => $empresa->NOMBRE_DUENO ?? '',
            '{{empresa.dueno_dui}}' => $empresa->DUI_DUENO ?? '',
            '{{contrato.numero}}' => $contrato->NUMERO_CONTRATO ?? '',
            '{{contrato.fecha_inicio}}' => $contrato->FECHA_INICIO
                ? $contrato->FECHA_INICIO->format('d/m/Y')
                : '—',
            '{{contrato.fecha_fin}}' => $this->textoFechaFin($contrato),
            '{{contrato.vigencia}}' => $this->textoVigencia($contrato),
            '{{fecha_actual}}' => now()->format('d/m/Y'),
            '{{clausulas}}' => $plantilla->CLAUSULAS ?? '',
            '{{firmantes}}' => $this->renderFirmantes($firmantes, $nombreCompleto, $esHtml),
        ], $this->beneficios->placeholdersBeneficios($beneficiosData), $this->normalizarCamposExtra($camposExtra));

        $contenido = $plantilla->CONTENIDO;
        foreach ($replacements as $placeholder => $valor) {
            $contenido = str_replace($placeholder, (string) $valor, $contenido);
        }

        return $contenido;
    }

    public function preview(PlantillaContrato $plantilla): string
    {
        $esHtml = strtoupper($plantilla->FORMATO ?? 'HTML') === 'HTML';

        $demo = array_merge([
            '{{empleado.nombre_completo}}' => 'JUAN CARLOS PÉREZ GARCÍA',
            '{{empleado.nombres}}' => 'JUAN CARLOS',
            '{{empleado.apellidos}}' => 'PÉREZ GARCÍA',
            '{{empleado.dui}}' => '01234567-8',
            '{{empleado.nit}}' => '0614-010199-001-5',
            '{{empleado.cargo}}' => 'ANALISTA DE SISTEMAS',
            '{{empleado.departamento}}' => 'TECNOLOGÍA',
            '{{empleado.salario}}' => '850.00',
            '{{empleado.salario_letras}}' => $this->numeroALetras->convertir(850),
            '{{empleado.fecha_ingreso}}' => '01/01/2025',
            '{{empleado.codigo}}' => 'EMP-001',
            '{{empleado.tipo_contratacion}}' => 'Permanente / Ley de Salarios',
            '{{empresa.nombre}}' => 'EMPRESA DEMO S.A. DE C.V.',
            '{{empresa.nit}}' => '0614-010199-001-5',
            '{{empresa.direccion}}' => 'San Salvador, El Salvador',
            '{{empresa.dueno}}' => 'MARÍA LÓPEZ',
            '{{empresa.dueno_dui}}' => '01234567-8',
            '{{contrato.numero}}' => 'CONT-2025-001',
            '{{contrato.fecha_inicio}}' => '01/02/2025',
            '{{contrato.fecha_fin}}' => '31/12/2025',
            '{{contrato.vigencia}}' => 'desde el 01/02/2025 hasta el 31/12/2025',
            '{{fecha_actual}}' => now()->format('d/m/Y'),
            '{{clausulas}}' => $plantilla->CLAUSULAS ?? '',
            '{{firmantes}}' => $this->renderFirmantes(collect([
                (object) ['NOMBRE' => 'MARÍA LÓPEZ', 'CARGO' => 'Representante Legal'],
            ]), 'JUAN CARLOS PÉREZ GARCÍA', $esHtml),
        ], $this->beneficios->placeholdersBeneficios([
            'ANTIGUEDAD_ANIOS' => 2,
            'ANTIGUEDAD_TEXTO' => '2 años y 3 meses',
            'AGUINALDO_APLICA' => true,
            'DIAS_AGUINALDO' => 19,
            'MONTO_AGUINALDO' => 538.33,
            'AGUINALDO_LETRAS' => $this->numeroALetras->convertir(538.33),
            'QUINCENA25_APLICA' => true,
            'QUINCENA25_MONTO' => 425.00,
            'QUINCENA25_LETRAS' => $this->numeroALetras->convertir(425),
            'QUINCENA25_DETALLE' => 'Aplica quincena 25 (50% del salario mensual).',
        ]));

        $contenido = $plantilla->CONTENIDO;
        foreach ($demo as $placeholder => $valor) {
            $contenido = str_replace($placeholder, (string) $valor, $contenido);
        }

        return $contenido;
    }

    private function textoFechaFin(Contrato $contrato): string
    {
        if ($contrato->SIN_FECHA_DEFINIDA) {
            return 'Indefinido';
        }
        if ($contrato->FECHA_FIN) {
            return $contrato->FECHA_FIN->format('d/m/Y');
        }

        return '—';
    }

    private function textoVigencia(Contrato $contrato): string
    {
        $inicio = $contrato->FECHA_INICIO
            ? $contrato->FECHA_INICIO->format('d/m/Y')
            : 'fecha por definir';

        if ($contrato->SIN_FECHA_DEFINIDA) {
            return "a partir del {$inicio}, por tiempo indefinido";
        }
        if ($contrato->FECHA_FIN) {
            $fin = $contrato->FECHA_FIN->format('d/m/Y');

            return "desde el {$inicio} hasta el {$fin}";
        }

        return "a partir del {$inicio}, sin fecha de finalización definida";
    }

    private function renderFirmantes(Collection $firmantes, string $nombreEmpleado, bool $html = true): string
    {
        if ($html) {
            $blocks = [];
            foreach ($firmantes as $f) {
                $cargo = $f->CARGO
                    ? '<div style="font-size:12px;color:#666;">' . e($f->CARGO) . '</div>'
                    : '';
                $blocks[] = '<div style="display:inline-block;min-width:200px;text-align:center;margin:12px 16px;">'
                    . '<div style="border-top:1px solid #333;margin-top:48px;padding-top:8px;"><strong>' . e($f->NOMBRE) . '</strong></div>'
                    . $cargo
                    . '<div style="font-size:11px;color:#666;margin-top:4px;">Por la empresa</div>'
                    . '</div>';
            }
            $blocks[] = '<div style="display:inline-block;min-width:200px;text-align:center;margin:12px 16px;">'
                . '<div style="border-top:1px solid #333;margin-top:48px;padding-top:8px;"><strong>' . e($nombreEmpleado) . '</strong></div>'
                . '<div style="font-size:11px;color:#666;margin-top:4px;">Por el trabajador</div>'
                . '</div>';

            return '<div style="display:flex;flex-wrap:wrap;justify-content:space-around;margin-top:32px;">'
                . implode('', $blocks)
                . '</div>';
        }

        $lineas = [];
        foreach ($firmantes as $f) {
            $cargo = $f->CARGO ? " — {$f->CARGO}" : '';
            $lineas[] = "_________________________\n{$f->NOMBRE}{$cargo}\nPor la empresa";
        }
        $lineas[] = "_________________________\n{$nombreEmpleado}\nPor el trabajador";

        return implode("\n\n", $lineas);
    }

    private function beneficiosDemo(): array
    {
        return [
            'ANTIGUEDAD_ANIOS' => 0,
            'ANTIGUEDAD_TEXTO' => '—',
            'AGUINALDO_APLICA' => false,
            'DIAS_AGUINALDO' => 0,
            'MONTO_AGUINALDO' => 0,
            'AGUINALDO_LETRAS' => '',
            'QUINCENA25_APLICA' => false,
            'QUINCENA25_MONTO' => 0,
            'QUINCENA25_LETRAS' => '',
            'QUINCENA25_DETALLE' => 'No aplica para este tipo de contratación.',
        ];
    }

    private function normalizarCamposExtra(array $camposExtra): array
    {
        $normalized = [];
        foreach ($camposExtra as $key => $value) {
            $placeholder = str_starts_with($key, '{{') ? $key : "{{{$key}}}";
            $normalized[$placeholder] = $value;
        }

        return $normalized;
    }
}
