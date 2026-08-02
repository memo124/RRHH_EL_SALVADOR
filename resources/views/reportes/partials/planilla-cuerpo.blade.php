@php
    use App\Helpers\ReportFormatHelper;

    $colsFijas = 5;
    $totalCols = $colsFijas
        + count($conceptosIngreso)
        + 1
        + count($conceptosDescuento)
        + 2
        + count($conceptosPatronal);

    $grupos = $grupos ?? [];
    if (empty($grupos) && !empty($detalles)) {
        $grupos = resolve(\App\Services\PlanillaReportService::class)
            ->groupDetallesByAreaDepartamento($detalles);
    }
    $rowNum = 0;
@endphp

<div class="planilla-header">
    @include('reportes.partials.empresa-header', ['centered' => true])
    <h2 class="planilla-titulo">Planilla de Pago — {{ $planilla->TITULO }}</h2>
    <div class="planilla-meta">
        <span><strong>Tipo:</strong> {{ $planilla->TIPOPLANILLA }}</span>
        <span><strong>Periodo:</strong> {{ $planilla->CALPERIODO }}</span>
        <span><strong>Fecha pago:</strong> {{ \Carbon\Carbon::parse($planilla->FECHAPAGO)->format('d/m/Y') }}</span>
        <span><strong>Frecuencia:</strong> {{ $planilla->NOMBREFRECUENCIA ?? '—' }}</span>
        <span><strong>Forma pago:</strong> {{ $planilla->FORMAPAGO }}</span>
        @if(!empty($planilla->EMPRESA_NIT))
            <span><strong>NIT:</strong> {{ $planilla->EMPRESA_NIT }}</span>
        @endif
    </div>
</div>

<table class="planilla-table" border="0" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="planilla-row-columns">
            <th>#</th>
            <th>{!! ReportFormatHelper::multilineHeader('Empleado', 12) !!}</th>
            <th>{!! ReportFormatHelper::multilineHeader('Contrato', 10) !!}</th>
            <th>{!! ReportFormatHelper::multilineHeader('Cargo', 10) !!}</th>
            <th class="head-num">{!! ReportFormatHelper::multilineHeader('Días', 10) !!}</th>
            @foreach($conceptosIngreso as $ingreso)
                <th class="head-num">{!! ReportFormatHelper::multilineHeader($ingreso['label'], 18) !!}</th>
            @endforeach
            <th class="head-num">{!! ReportFormatHelper::multilineHeader('Total Devengado', 16) !!}</th>
            @foreach($conceptosDescuento as $concepto)
                <th class="head-num">{!! ReportFormatHelper::multilineHeader($concepto['CONCEPTO'], 18) !!}</th>
            @endforeach
            <th class="head-num">{!! ReportFormatHelper::multilineHeader('Total Desc.', 14) !!}</th>
            <th class="head-num">{!! ReportFormatHelper::multilineHeader('Líquido', 12) !!}</th>
            @foreach($conceptosPatronal as $pat)
                <th class="head-num">{!! ReportFormatHelper::multilineHeader($pat['label'], 16) !!}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($grupos as $grupoArea)
            <tr class="planilla-group-area">
                <td colspan="{{ $totalCols }}">ÁREA: {{ $grupoArea['area'] }}</td>
            </tr>
            @foreach($grupoArea['departamentos'] as $grupoDepto)
                <tr class="planilla-group-depto">
                    <td colspan="{{ $totalCols }}">Departamento: {{ $grupoDepto['departamento'] }}</td>
                </tr>
                @foreach($grupoDepto['detalles'] as $det)
                    @php $rowNum++; @endphp
                    @include('reportes.partials.planilla-fila-empleado', [
                        'det' => $det,
                        'rowNum' => $rowNum,
                    ])
                @endforeach
                @include('reportes.partials.planilla-fila-totales', [
                    'label' => 'Subtotal ' . $grupoDepto['departamento'] . ' (' . $grupoDepto['detalles']->count() . ' empleados)',
                    'detallesGrupo' => $grupoDepto['detalles'],
                    'rowClass' => 'planilla-subtotal planilla-subtotal-depto',
                ])
            @endforeach
            @include('reportes.partials.planilla-fila-totales', [
                'label' => 'Subtotal área ' . $grupoArea['area'] . ' (' . $grupoArea['detalles']->count() . ' empleados)',
                'detallesGrupo' => $grupoArea['detalles'],
                'rowClass' => 'planilla-subtotal planilla-subtotal-area',
            ])
        @endforeach
    </tbody>
    <tfoot>
        @include('reportes.partials.planilla-fila-totales', [
            'label' => 'TOTALES (' . $totales['COUNT'] . ' empleados)',
            'detallesGrupo' => $detalles,
            'rowClass' => 'planilla-subtotal planilla-subtotal-general',
        ])
    </tfoot>
</table>

@if(isset($firmantes) && count($firmantes))
<div class="firmantes-section section">
    <h2>Firmantes</h2>
    <table class="firmantes-table">
        <tr>
            @foreach($firmantes as $firmante)
                <td>
                    <div class="firma-line"></div>
                    <strong>{{ $firmante->NOMBRE }}</strong>
                    @if(!empty($firmante->CARGO))
                        <div class="muted">{{ $firmante->CARGO }}</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
</div>
@endif
