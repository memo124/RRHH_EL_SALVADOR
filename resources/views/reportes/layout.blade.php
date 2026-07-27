<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Reporte RRHH')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #fff; padding: 16px; }
        .toolbar { margin-bottom: 12px; }
        .toolbar button { background: #4338ca; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .toolbar button:hover { background: #3730a3; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-bottom: 8px; color: #334155; }
        .empresa-header { display: table; width: 100%; margin-bottom: 14px; border-bottom: 2px solid #4338ca; padding-bottom: 10px; }
        .empresa-logo { display: table-cell; width: 120px; vertical-align: middle; max-height: 56px; max-width: 120px; object-fit: contain; padding-right: 12px; }
        .empresa-info { display: table-cell; vertical-align: middle; }
        .empresa-abrev { font-size: 11px; color: #64748b; margin-top: 2px; }
        .empresa-contact { font-size: 10px; margin-top: 4px; }
        .meta { margin-bottom: 12px; line-height: 1.5; }
        .meta span { display: inline-block; margin-right: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; background: #f8fafc; }
        .section { margin-top: 16px; }
        .boleta-page { page-break-after: always; border: 1px solid #e2e8f0; padding: 16px; margin-bottom: 20px; }
        .boleta-page:last-child { page-break-after: auto; }
        .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .amount { font-family: 'Courier New', monospace; }
        .muted { color: #64748b; }
        .total-box { margin-top: 10px; padding: 8px; background: #ecfdf5; border: 1px solid #6ee7b7; font-size: 13px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none !important; }
            .boleta-page { border: none; margin: 0; padding: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
        @stack('toolbar')
    </div>
    @yield('content')
    @stack('scripts')
</body>
</html>
