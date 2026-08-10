@extends('reportes.layout')

@section('title', 'F-14 Renta Retenida ' . $anio . ' - ' . ($empresa->NOMBREEMPRESA ?? ''))

@section('content')
@include('reportes.partials.empresa-header', ['empresa' => $empresa])

<h2>Acumulado anual de renta retenida — Año {{ $anio }}</h2>
<p class="muted">Insumo de apoyo para la Declaración e Informe Anual de Retenciones (Formulario F-14) del Ministerio de Hacienda. No sustituye la declaración oficial.</p>

<table class="report-table">
    <thead>
        <tr>
            <th>NIT</th>
            <th>DUI</th>
            <th>Nombre completo</th>
            <th class="head-num">Total devengado</th>
            <th class="head-num">Total renta retenida</th>
            <th class="head-num">Periodos con retención</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $fila)
        <tr>
            <td>{{ $fila['NIT'] }}</td>
            <td>{{ $fila['DUI'] }}</td>
            <td>{{ $fila['NOMBRE'] }}</td>
            <td class="num amount">${{ number_format($fila['TOTAL_DEVENGADO'], 2) }}</td>
            <td class="num amount">${{ number_format($fila['TOTAL_RENTA'], 2) }}</td>
            <td class="num">{{ $fila['PERIODOS'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTALES ({{ $totales['count'] }} empleados)</td>
            <td class="num amount">${{ number_format($totales['devengado'], 2) }}</td>
            <td class="num amount">${{ number_format($totales['renta'], 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endsection
