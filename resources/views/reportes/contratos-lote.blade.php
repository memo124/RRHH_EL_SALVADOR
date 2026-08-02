@extends('reportes.layout')

@section('title', 'Contratos — Lote (' . count($items) . ')')

@push('styles')
<style>
    .contrato-lote-page {
        page-break-after: always;
        border: 1px solid #e2e8f0;
        padding: 16px;
        margin-bottom: 24px;
    }
    .contrato-lote-page:last-child {
        page-break-after: auto;
    }
    @media print {
        .contrato-lote-page {
            border: none;
            margin: 0;
            padding: 0;
        }
    }
</style>
@endpush

@push('toolbar')
    <a href="{{ url('/reportes/contratos/lote/pdf') }}?ids={{ request('ids') }}&token={{ request('token') }}"
       style="margin-left:8px;background:#059669;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;">
        Descargar ZIP
    </a>
@endpush

@section('content')
    <p class="muted" style="margin-bottom:16px;">
        {{ count($items) }} contrato(s) en este lote. Use <strong>Imprimir</strong> para enviar todos a la impresora, o descargue el ZIP con un PDF por empleado.
    </p>

    @foreach($items as $item)
        @php
            $contrato = $item['contrato'];
            $empresa = $item['empresa'];
            $empresaLogo = $item['empresaLogo'];
            $nombreEmpleado = $item['nombreEmpleado'];
        @endphp
        <div class="contrato-lote-page boleta-page">
            @include('reportes.partials.empresa-header', ['empresa' => $empresa, 'empresaLogo' => $empresaLogo])

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
                    <span><strong>Salario:</strong> ${{ number_format((float) $contrato->SALARIO, 2) }}</span>
                @endif
                <span><strong>Estado:</strong> {{ $contrato->ESTADO }}</span>
            </div>

            <div class="section contrato-contenido" style="line-height:1.6;margin-top:20px;">
                {!! $contrato->CONTENIDO_GENERADO !!}
            </div>
        </div>
    @endforeach
@endsection
