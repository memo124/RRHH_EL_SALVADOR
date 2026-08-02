<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        color: #111;
        background: #fff;
        padding: 12px;
    }
    h1 { font-size: 14px; margin-bottom: 3px; font-weight: 700; }
    h2 { font-size: 11px; margin-bottom: 6px; color: #334155; font-weight: 700; }
    .empresa-header-table { width: 100%; border: none; border-collapse: collapse; margin-bottom: 10px; }
    .empresa-header-table td { border: none; padding: 0; vertical-align: middle; }
    .empresa-info-cell { text-align: center; }
    .empresa-info-cell h1 { text-align: center; }
    .empresa-info-cell .empresa-abrev,
    .empresa-info-cell .empresa-contact { text-align: center; }
    .empresa-logo { max-height: 48px; max-width: 80px; object-fit: contain; }
    .empresa-abrev { font-size: 9px; color: #64748b; margin-top: 2px; }
    .empresa-contact { font-size: 8px; margin-top: 3px; color: #64748b; line-height: 1.35; }
    .boleta-empresa-wrap {
        border-bottom: 1.5px solid #4338ca;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .meta { margin-bottom: 10px; line-height: 1.45; font-size: 9px; }
    .meta span { display: inline-block; margin-right: 12px; margin-bottom: 2px; }
    .section { margin-top: 10px; }
    .boleta-page { page-break-after: always; padding-bottom: 8px; }
    .boleta-page:last-child { page-break-after: auto; }
    .two-cols { display: table; width: 100%; border-collapse: separate; border-spacing: 10px 0; }
    .two-cols > div { display: table-cell; width: 50%; vertical-align: top; }
    .amount { font-family: 'Courier New', Courier, monospace; }
    .muted { color: #64748b; }
    .total-box {
        margin-top: 10px;
        padding: 8px;
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        font-size: 11px;
        font-weight: bold;
    }
    table.report-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9px; }
    table.report-table th,
    table.report-table td {
        border: 1px solid #cbd5e1;
        padding: 3px 5px;
        text-align: left;
        vertical-align: top;
    }
    table.report-table tbody td.num { text-align: right; white-space: nowrap; }
    table.report-table tbody td.label-wrap {
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.3;
    }
</style>
