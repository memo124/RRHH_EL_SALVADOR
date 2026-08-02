<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta - {{ $detalle->NOM_EMPLEADO ?? '' }}</title>
    @include('reportes.partials.boleta-pdf-styles')
</head>
<body>
    @include('reportes.partials.boleta-cuerpo', ['detalle' => $detalle, 'planilla' => $planilla])
</body>
</html>
