@php
    $empresaInfo = $planilla ?? null;
    if (!$empresaInfo && !empty($empresa)) {
        $empresaInfo = (object) [
            'NOMBREEMPRESA' => $empresa->NOMBREEMPRESA ?? '',
            'ABREVIATURA' => $empresa->ABREVIATURA ?? null,
            'EMPRESA_DIRECCION' => $empresa->DIRECCION ?? null,
            'EMPRESA_TELEFONO' => $empresa->TELEFONO ?? null,
            'EMPRESA_NIT' => $empresa->NUMERONIT ?? null,
        ];
    }
    $centered = !empty($centered);
    $logoWidth = $centered ? 72 : 120;
@endphp

@if($empresaInfo)
    @if($centered)
        <table class="empresa-header-table" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="empresa-logo-cell" width="{{ $logoWidth }}" valign="middle" align="left">
                    @if(!empty($empresaLogo))
                        <img src="{{ $empresaLogo }}" alt="Logo" class="empresa-logo">
                    @endif
                </td>
                <td class="empresa-info-cell" align="center" valign="middle">
                    <h1>{{ $empresaInfo->NOMBREEMPRESA }}</h1>
                    @if(!empty($empresaInfo->ABREVIATURA))
                        <p class="empresa-abrev">{{ $empresaInfo->ABREVIATURA }}</p>
                    @endif
                    @if(!empty($empresaInfo->EMPRESA_DIRECCION) || !empty($empresaInfo->EMPRESA_TELEFONO) || !empty($empresaInfo->EMPRESA_NIT))
                        <p class="empresa-contact muted">
                            @if(!empty($empresaInfo->EMPRESA_NIT)) NIT: {{ $empresaInfo->EMPRESA_NIT }} @endif
                            @if(!empty($empresaInfo->EMPRESA_DIRECCION)) · {{ $empresaInfo->EMPRESA_DIRECCION }} @endif
                            @if(!empty($empresaInfo->EMPRESA_TELEFONO)) · Tel: {{ $empresaInfo->EMPRESA_TELEFONO }} @endif
                        </p>
                    @endif
                </td>
                <td class="empresa-spacer-cell" width="{{ $logoWidth }}">&nbsp;</td>
            </tr>
        </table>
    @else
        <div class="empresa-header">
            @if(!empty($empresaLogo))
                <img src="{{ $empresaLogo }}" alt="Logo" class="empresa-logo">
            @endif
            <div class="empresa-info">
                <h1>{{ $empresaInfo->NOMBREEMPRESA }}</h1>
                @if(!empty($empresaInfo->ABREVIATURA))
                    <p class="empresa-abrev">{{ $empresaInfo->ABREVIATURA }}</p>
                @endif
                @if(!empty($empresaInfo->EMPRESA_DIRECCION) || !empty($empresaInfo->EMPRESA_TELEFONO) || !empty($empresaInfo->EMPRESA_NIT))
                    <p class="empresa-contact muted">
                        @if(!empty($empresaInfo->EMPRESA_NIT)) NIT: {{ $empresaInfo->EMPRESA_NIT }} @endif
                        @if(!empty($empresaInfo->EMPRESA_DIRECCION)) · {{ $empresaInfo->EMPRESA_DIRECCION }} @endif
                        @if(!empty($empresaInfo->EMPRESA_TELEFONO)) · Tel: {{ $empresaInfo->EMPRESA_TELEFONO }} @endif
                    </p>
                @endif
            </div>
        </div>
    @endif
@endif
