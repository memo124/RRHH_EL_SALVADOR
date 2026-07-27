<div class="boleta-page">
    @include('reportes.partials.empresa-header')
    <h2>Boleta de Pago</h2>

    <div class="meta">
        <span><strong>Empleado:</strong> {{ $detalle->NOM_EMPLEADO }}</span>
        @if($detalle->empleado_info)
            <span><strong>Código:</strong> {{ $detalle->empleado_info->CODIGOEMPLEADO }}</span>
            <span><strong>DUI:</strong> {{ $detalle->empleado_info->DUI }}</span>
        @endif
        <span><strong>Cargo:</strong> {{ $detalle->CARGO }}</span>
        <span><strong>Departamento:</strong> {{ $detalle->DEPARTAMENTO }}</span>
        <span><strong>Periodo:</strong> {{ $planilla->CALPERIODO }}</span>
        <span><strong>Fecha pago:</strong> {{ \Carbon\Carbon::parse($planilla->FECHAPAGO)->format('d/m/Y') }}</span>
        <span><strong>Planilla:</strong> {{ $planilla->TITULO }}</span>
    </div>

    <div class="two-cols section">
        <div>
            <h2>Ingresos</h2>
            <table>
                <tbody>
                    <tr><td>Salario ({{ number_format((float)$detalle->DIASLABORADOS, 1) }} días)</td><td class="num amount">${{ number_format((float)$detalle->SALARIO_DIAS, 2) }}</td></tr>
                    @if((float)$detalle->HORAEXTRAS > 0)
                        <tr><td>Horas extras</td><td class="num amount">${{ number_format((float)$detalle->HORAEXTRAS, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->PRODUCTIVIDAD > 0)
                        <tr><td>Productividad</td><td class="num amount">${{ number_format((float)$detalle->PRODUCTIVIDAD, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->COMISION > 0)
                        <tr><td>Comisión</td><td class="num amount">${{ number_format((float)$detalle->COMISION, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->OTROS_INGRESOS > 0)
                        <tr><td>Otros ingresos</td><td class="num amount">${{ number_format((float)$detalle->OTROS_INGRESOS, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->DEVENGADO_EXENTO > 0)
                        <tr><td>Devengado exento</td><td class="num amount">${{ number_format((float)$detalle->DEVENGADO_EXENTO, 2) }}</td></tr>
                    @endif
                    <tr>
                        <td><strong>Total devengado</strong></td>
                        <td class="num amount"><strong>${{ number_format((float)$detalle->TOTAL_DEVENGADO, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <h2>Descuentos</h2>
            <table>
                <tbody>
                    @forelse($detalle->descuentos_detalle as $desc)
                        <tr>
                            <td>
                                {{ $desc->CONCEPTO }}
                                <span class="muted">({{ $desc->CATEGORIA }})</span>
                            </td>
                            <td class="num amount">${{ number_format((float)$desc->MONTO, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="muted">Sin descuentos aplicados</td></tr>
                    @endforelse
                    <tr>
                        <td><strong>Total descuentos</strong></td>
                        <td class="num amount"><strong>${{ number_format((float)$detalle->TOTAL_DEDUCCIONES, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="total-box">
        Líquido a recibir: <span class="amount">${{ number_format((float)$detalle->LIQUIDO_A_RECIBIR, 2) }}</span>
    </div>

    @php
        $totalPatronal = (float)$detalle->AFP_PATRONAL + (float)$detalle->ISSS_PATRONAL + (float)$detalle->INSAFORP_PATRONAL;
    @endphp
    @if($totalPatronal > 0)
        <div class="section">
            <h2>Costo patronal (empresa)</h2>
            <table>
                <tbody>
                    @if((float)$detalle->AFP_PATRONAL > 0)
                        <tr><td>AFP patronal</td><td class="num amount">${{ number_format((float)$detalle->AFP_PATRONAL, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->ISSS_PATRONAL > 0)
                        <tr><td>ISSS patronal</td><td class="num amount">${{ number_format((float)$detalle->ISSS_PATRONAL, 2) }}</td></tr>
                    @endif
                    @if((float)$detalle->INSAFORP_PATRONAL > 0)
                        <tr><td>INSAFORP</td><td class="num amount">${{ number_format((float)$detalle->INSAFORP_PATRONAL, 2) }}</td></tr>
                    @endif
                    <tr>
                        <td><strong>Total costo patronal</strong></td>
                        <td class="num amount"><strong>${{ number_format($totalPatronal, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <p class="muted section">Documento generado el {{ now()->format('d/m/Y H:i') }}. Este comprobante refleja los descuentos configurados en el sistema al momento del cálculo.</p>
</div>
