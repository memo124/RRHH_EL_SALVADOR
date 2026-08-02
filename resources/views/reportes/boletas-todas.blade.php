@extends('reportes.layout')

@section('title', 'Boletas - ' . $planilla->TITULO)

@push('toolbar')
    <a href="{{ url('/reportes/planillas/' . $planilla->ID_PLANILLA . '/boletas/pdf') }}?token={{ request('token') }}" style="margin-left:8px;background:#059669;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;">Descargar PDF</a>
@endpush

@section('content')
<h2>Boletas de Pago — {{ $planilla->TITULO }}</h2>
<p class="meta muted">Periodo: {{ $planilla->CALPERIODO }} · {{ $totales['COUNT'] }} empleados</p>

@foreach($detalles as $detalle)
    @include('reportes.partials.boleta-cuerpo', ['detalle' => $detalle, 'planilla' => $planilla])
@endforeach
@endsection
