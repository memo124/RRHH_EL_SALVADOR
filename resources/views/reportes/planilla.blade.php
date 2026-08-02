@extends('reportes.layout')

@section('title', 'Planilla - ' . $planilla->TITULO)

@push('styles')
@include('reportes.partials.planilla-pdf-styles')
@endpush

@section('content')
@include('reportes.partials.planilla-cuerpo')
@endsection
