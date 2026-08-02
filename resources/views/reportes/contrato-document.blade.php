<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato - {{ $contrato->NUMERO_CONTRATO ?? '' }}</title>
    @include('reportes.partials.contrato-pdf-styles')
</head>
<body>
    @include('reportes.partials.contrato-cuerpo')
</body>
</html>
