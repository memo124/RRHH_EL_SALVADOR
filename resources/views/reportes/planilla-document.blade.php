<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla - {{ $planilla->TITULO ?? '' }}</title>
    @include('reportes.partials.planilla-pdf-styles')
</head>
<body>
    @include('reportes.partials.planilla-cuerpo')
</body>
</html>
