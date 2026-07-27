<div class="empresa-header">
    @if(!empty($empresaLogo))
        <img src="{{ $empresaLogo }}" alt="Logo" class="empresa-logo">
    @endif
    <div class="empresa-info">
        <h1>{{ $planilla->NOMBREEMPRESA }}</h1>
        @if(!empty($planilla->ABREVIATURA))
            <p class="empresa-abrev">{{ $planilla->ABREVIATURA }}</p>
        @endif
        @if(!empty($planilla->EMPRESA_DIRECCION) || !empty($planilla->EMPRESA_TELEFONO) || !empty($planilla->EMPRESA_NIT))
            <p class="empresa-contact muted">
                @if(!empty($planilla->EMPRESA_NIT)) NIT: {{ $planilla->EMPRESA_NIT }} @endif
                @if(!empty($planilla->EMPRESA_DIRECCION)) · {{ $planilla->EMPRESA_DIRECCION }} @endif
                @if(!empty($planilla->EMPRESA_TELEFONO)) · Tel: {{ $planilla->EMPRESA_TELEFONO }} @endif
            </p>
        @endif
    </div>
</div>
