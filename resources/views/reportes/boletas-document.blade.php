<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletas - {{ $planilla->TITULO ?? '' }}</title>
    @include('reportes.partials.boleta-pdf-styles')
</head>
<body>
    @foreach($detalles as $detalle)
        @include('reportes.partials.boleta-cuerpo', ['detalle' => $detalle, 'planilla' => $planilla])
    @endforeach
</body>
</html>
