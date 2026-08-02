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

<div class="section contrato-contenido">
    {!! $contrato->CONTENIDO_GENERADO !!}
</div>
