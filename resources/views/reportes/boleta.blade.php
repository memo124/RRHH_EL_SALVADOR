@extends('reportes.layout')

@section('title', 'Boleta - ' . ($detalle->NOM_EMPLEADO ?? ''))

@push('toolbar')
    <a href="{{ url('/reportes/planillas/' . $planilla->ID_PLANILLA . '/boletas/' . $detalle->ID_DETALLEPLANILLA . '/pdf') }}?token={{ request('token') }}" style="margin-left:8px;background:#059669;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;">Descargar PDF</a>
@endpush

@section('content')
@include('reportes.partials.boleta-cuerpo', ['detalle' => $detalle, 'planilla' => $planilla])
@endsection
