<tr class="{{ $rowClass ?? 'planilla-subtotal' }}">
    <td colspan="{{ $colsFijas }}">{{ $label }}</td>
    @foreach($conceptosIngreso as $ingreso)
        @php
            $totalIngreso = $detallesGrupo->sum(fn ($d) => (float) ($d->{$ingreso['key']} ?? 0));
        @endphp
        <td class="num amount">${{ number_format($totalIngreso, 2) }}</td>
    @endforeach
    <td class="num amount">${{ number_format((float) $detallesGrupo->sum('TOTAL_DEVENGADO'), 2) }}</td>
    @foreach($conceptosDescuento as $concepto)
        @php
            $totalConcepto = 0;
            foreach ($detallesGrupo as $det) {
                foreach ($det->descuentos_detalle as $d) {
                    if ($d->CONCEPTO === $concepto['CONCEPTO'] && $d->CATEGORIA === $concepto['CATEGORIA']) {
                        $totalConcepto += (float) $d->MONTO;
                    }
                }
            }
        @endphp
        <td class="num amount">${{ number_format($totalConcepto, 2) }}</td>
    @endforeach
    <td class="num amount">${{ number_format((float) $detallesGrupo->sum('TOTAL_DEDUCCIONES'), 2) }}</td>
    <td class="num amount">${{ number_format((float) $detallesGrupo->sum('LIQUIDO_A_RECIBIR'), 2) }}</td>
    @foreach($conceptosPatronal as $pat)
        @php
            $totalPatCol = !empty($pat['computed'])
                ? $detallesGrupo->sum('AFP_PATRONAL') + $detallesGrupo->sum('ISSS_PATRONAL') + $detallesGrupo->sum('INSAFORP_PATRONAL')
                : $detallesGrupo->sum(fn ($d) => (float) ($d->{$pat['key']} ?? 0));
        @endphp
        <td class="num amount">${{ number_format($totalPatCol, 2) }}</td>
    @endforeach
</tr>
