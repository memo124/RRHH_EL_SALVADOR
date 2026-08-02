<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contratos — Lote ({{ count($items) }})</title>
    @include('reportes.partials.contrato-pdf-styles')
</head>
<body>
    @foreach($items as $item)
        @php
            $contrato = $item['contrato'];
            $empresa = $item['empresa'];
            $empresaLogo = $item['empresaLogo'];
            $nombreEmpleado = $item['nombreEmpleado'];
        @endphp
        <div class="contrato-page">
            @include('reportes.partials.contrato-cuerpo')
        </div>
    @endforeach
</body>
</html>
