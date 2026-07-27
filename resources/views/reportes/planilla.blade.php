@extends('reportes.layout')

@section('title', 'Planilla - ' . $planilla->TITULO)

@push('toolbar')
    <a href="{{ url('/reportes/planillas/' . $planilla->ID_PLANILLA . '/pdf') }}?token={{ request('token') }}" style="margin-left:8px;background:#059669;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;">Descargar PDF</a>
@endpush

@section('content')
@include('reportes.partials.empresa-header')
<h2>Planilla de Pago — {{ $planilla->TITULO }}</h2>

<div class="meta">
    <span><strong>Tipo:</strong> {{ $planilla->TIPOPLANILLA }}</span>
    <span><strong>Periodo:</strong> {{ $planilla->CALPERIODO }}</span>
    <span><strong>Fecha pago:</strong> {{ \Carbon\Carbon::parse($planilla->FECHAPAGO)->format('d/m/Y') }}</span>
    <span><strong>Frecuencia:</strong> {{ $planilla->NOMBREFRECUENCIA ?? '—' }}</span>
    <span><strong>Forma pago:</strong> {{ $planilla->FORMAPAGO }}</span>
    @if(!empty($planilla->EMPRESA_NIT))
        <span><strong>NIT:</strong> {{ $planilla->EMPRESA_NIT }}</span>
    @endif
</div>

@php
    $colsIngreso = count($conceptosIngreso);
    $colsDescuento = count($conceptosDescuento);
    $colsPatronal = count($conceptosPatronal);
    $colsFijas = 5;
    $colTotalDevengado = 1;
    $colTotalesFinales = 2;
@endphp

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Empleado</th>
            <th>Contrato</th>
            <th>Cargo</th>
            <th class="num">Días</th>
            @foreach($conceptosIngreso as $ingreso)
                <th class="num">{{ $ingreso['label'] }}</th>
            @endforeach
            <th class="num">Total Devengado</th>
            @foreach($conceptosDescuento as $concepto)
                <th class="num">{{ $concepto['CONCEPTO'] }}</th>
            @endforeach
            <th class="num">Total Desc.</th>
            <th class="num">Líquido</th>
            @foreach($conceptosPatronal as $pat)
                <th class="num">{{ $pat['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($detalles as $i => $det)
            @php
                $mapDesc = collect($det->descuentos_detalle)->keyBy(fn($d) => $d->CONCEPTO . '|' . $d->CATEGORIA);
                $totalPat = (float)$det->AFP_PATRONAL + (float)$det->ISSS_PATRONAL + (float)$det->INSAFORP_PATRONAL;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $det->NOM_EMPLEADO }}</td>
                <td>{{ $det->TIPO_CONTRATACION_NOM }}</td>
                <td>{{ $det->CARGO }}</td>
                <td class="num">{{ number_format((float)$det->DIASLABORADOS, 1) }}</td>
                @foreach($conceptosIngreso as $ingreso)
                    <td class="num amount">${{ number_format((float)($det->{$ingreso['key']} ?? 0), 2) }}</td>
                @endforeach
                <td class="num amount">${{ number_format((float)$det->TOTAL_DEVENGADO, 2) }}</td>
                @foreach($conceptosDescuento as $concepto)
                    @php $key = $concepto['CONCEPTO'] . '|' . $concepto['CATEGORIA']; @endphp
                    <td class="num amount">
                        @if(isset($mapDesc[$key]))
                            ${{ number_format((float)$mapDesc[$key]->MONTO, 2) }}
                        @endif
                    </td>
                @endforeach
                <td class="num amount">${{ number_format((float)$det->TOTAL_DEDUCCIONES, 2) }}</td>
                <td class="num amount">${{ number_format((float)$det->LIQUIDO_A_RECIBIR, 2) }}</td>
                @foreach($conceptosPatronal as $pat)
                    <td class="num amount">
                        @if(!empty($pat['computed']))
                            ${{ number_format($totalPat, 2) }}
                        @else
                            ${{ number_format((float)($det->{$pat['key']} ?? 0), 2) }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $colsFijas }}" class="num">TOTALES ({{ $totales['COUNT'] }} empleados)</td>
            @foreach($conceptosIngreso as $ingreso)
                @php
                    $totalIngreso = $detalles->sum(fn ($d) => (float) ($d->{$ingreso['key']} ?? 0));
                @endphp
                <td class="num amount">${{ number_format($totalIngreso, 2) }}</td>
            @endforeach
            <td class="num amount">${{ number_format($totales['TOTAL_DEVENGADO'], 2) }}</td>
            @foreach($conceptosDescuento as $concepto)
                @php
                    $totalConcepto = 0;
                    foreach ($detalles as $det) {
                        foreach ($det->descuentos_detalle as $d) {
                            if ($d->CONCEPTO === $concepto['CONCEPTO'] && $d->CATEGORIA === $concepto['CATEGORIA']) {
                                $totalConcepto += (float) $d->MONTO;
                            }
                        }
                    }
                @endphp
                <td class="num amount">${{ number_format($totalConcepto, 2) }}</td>
            @endforeach
            <td class="num amount">${{ number_format($totales['TOTAL_DEDUCCIONES'], 2) }}</td>
            <td class="num amount">${{ number_format($totales['LIQUIDO_A_RECIBIR'], 2) }}</td>
            @foreach($conceptosPatronal as $pat)
                @php
                    $totalPatCol = !empty($pat['computed'])
                        ? $totales['AFP_PATRONAL'] + $totales['ISSS_PATRONAL'] + $totales['INSAFORP_PATRONAL']
                        : $detalles->sum(fn ($d) => (float) ($d->{$pat['key']} ?? 0));
                @endphp
                <td class="num amount">${{ number_format($totalPatCol, 2) }}</td>
            @endforeach
        </tr>
    </tfoot>
</table>
@endsection
