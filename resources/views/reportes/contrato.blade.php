@extends('reportes.layout')

@section('title', 'Contrato - ' . ($contrato->NUMERO_CONTRATO ?? ''))

@push('toolbar')
    <a href="{{ url('/reportes/contratos/' . $contrato->ID_CONTRATO . '/pdf') }}?token={{ request('token') }}" style="margin-left:8px;background:#059669;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;">Descargar PDF</a>
@endpush

@section('content')
@include('reportes.partials.empresa-header')

<h2>Contrato Laboral — {{ $contrato->NUMERO_CONTRATO }}</h2>

<div class="meta">
    <span><strong>Empleado:</strong> {{ $nombreEmpleado }}</span>
    @if($contrato->FECHA_INICIO)
        <span><strong>Inicio:</strong> {{ $contrato->FECHA_INICIO->format('d/m/Y') }}</span>
    @endif
    @if($contrato->SIN_FECHA_DEFINIDA)
        <span><strong>Vigencia:</strong> Indefinida</span>
    @elseif($contrato->FECHA_FIN)
        <span><strong>Fin:</strong> {{ $contrato->FECHA_FIN->format('d/m/Y') }}</span>
    @else
        <span><strong>Vigencia:</strong> Sin fecha definida</span>
    @endif
    @if($contrato->SALARIO)
        <span><strong>Salario:</strong> ${{ number_format((float)$contrato->SALARIO, 2) }}</span>
    @endif
    <span><strong>Estado:</strong> {{ $contrato->ESTADO }}</span>
</div>

<div class="section contrato-contenido" style="line-height:1.6;margin-top:20px;">
{!! $contrato->CONTENIDO_GENERADO !!}
</div>
@endsection
