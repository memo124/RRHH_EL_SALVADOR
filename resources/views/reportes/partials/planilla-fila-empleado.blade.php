@php
    $mapDesc = collect($det->descuentos_detalle)->keyBy(fn ($d) => $d->CONCEPTO . '|' . $d->CATEGORIA);
    $totalPat = (float) $det->AFP_PATRONAL + (float) $det->ISSS_PATRONAL + (float) $det->INSAFORP_PATRONAL;
@endphp
<tr>
    <td>{{ $rowNum }}</td>
    <td class="label-wrap">{{ $det->NOM_EMPLEADO }}</td>
    <td class="label-wrap">{{ $det->TIPO_CONTRATACION_NOM }}</td>
    <td class="label-wrap">{{ $det->CARGO }}</td>
    <td class="num">{{ number_format((float) $det->DIASLABORADOS, 1) }}</td>
    @foreach($conceptosIngreso as $ingreso)
        <td class="num amount">${{ number_format((float) ($det->{$ingreso['key']} ?? 0), 2) }}</td>
    @endforeach
    <td class="num amount">${{ number_format((float) $det->TOTAL_DEVENGADO, 2) }}</td>
    @foreach($conceptosDescuento as $concepto)
        @php $key = $concepto['CONCEPTO'] . '|' . $concepto['CATEGORIA']; @endphp
        <td class="num amount">
            @if(isset($mapDesc[$key]))
                ${{ number_format((float) $mapDesc[$key]->MONTO, 2) }}
            @endif
        </td>
    @endforeach
    <td class="num amount">${{ number_format((float) $det->TOTAL_DEDUCCIONES, 2) }}</td>
    <td class="num amount">${{ number_format((float) $det->LIQUIDO_A_RECIBIR, 2) }}</td>
    @foreach($conceptosPatronal as $pat)
        <td class="num amount">
            @if(!empty($pat['computed']))
                ${{ number_format($totalPat, 2) }}
            @else
                ${{ number_format((float) ($det->{$pat['key']} ?? 0), 2) }}
            @endif
        </td>
    @endforeach
</tr>
