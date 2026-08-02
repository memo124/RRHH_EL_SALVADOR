<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 7px;
        color: #222;
        background: #fff;
        padding: 8px 10px;
        line-height: 1.3;
    }
    h1 { font-size: 11px; margin-bottom: 2px; font-weight: 700; }

    .planilla-header {
        width: 100%;
        margin-bottom: 10px;
        text-align: center;
        border: 0;
        background: #fff;
    }
    .planilla-header .empresa-header-table { margin: 0 auto; }
    .planilla-titulo {
        font-size: 9px;
        font-weight: 700;
        color: #334155;
        margin: 6px 0 4px;
        text-align: center;
    }
    .planilla-meta {
        font-size: 6.5px;
        line-height: 1.4;
        color: #444;
        text-align: center;
        padding-bottom: 6px;
        border-bottom: 1.5px solid #4338ca;
    }
    .planilla-meta span {
        display: inline-block;
        margin: 0 8px 1px;
    }

    .empresa-header {
        display: table;
        width: 100%;
        margin-bottom: 8px;
        border-bottom: 1.5px solid #4338ca;
        padding-bottom: 6px;
    }
    .empresa-header-table { width: 100%; border: none; border-collapse: collapse; }
    .empresa-header-table td { border: none; padding: 0; vertical-align: middle; }
    .empresa-info-cell { text-align: center; }
    .empresa-info-cell h1 { text-align: center; }
    .empresa-info-cell .empresa-abrev,
    .empresa-info-cell .empresa-contact { text-align: center; }
    .empresa-logo {
        max-height: 40px;
        max-width: 72px;
        object-fit: contain;
    }
    .empresa-info { display: table-cell; vertical-align: middle; }
    .empresa-abrev { font-size: 7px; color: #64748b; margin-top: 1px; }
    .empresa-contact { font-size: 6.5px; margin-top: 2px; color: #64748b; line-height: 1.35; }
    .muted { color: #64748b; }

    table.planilla-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-top: 4px;
        font-size: 6px;
    }
    table.planilla-table th,
    table.planilla-table td {
        padding: 2px 3px;
        vertical-align: middle;
        text-align: left;
        border: 0;
    }
    table.planilla-table thead tr.planilla-row-columns th,
    table.planilla-table tbody tr:not(.planilla-group-area):not(.planilla-group-depto) td,
    table.planilla-table tfoot td {
        border: 1px solid #cbd5e1;
    }
    table.planilla-table thead tr.planilla-row-columns th {
        background: #eef2ff;
        color: #1e293b;
        font-size: 5.5px;
        font-weight: 700;
        line-height: 1.35;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        text-transform: none;
        vertical-align: bottom;
        max-width: 0;
        hyphens: none;
    }
    table.planilla-table thead th.head-num { text-align: right; }

    table.planilla-table tbody tr.planilla-group-area td {
        background: #4338ca;
        color: #fff;
        font-size: 7px;
        font-weight: 700;
        padding: 4px 6px;
        border: 0;
        text-align: left;
    }
    table.planilla-table tbody tr.planilla-group-depto td {
        background: #e0e7ff;
        color: #312e81;
        font-size: 6.5px;
        font-weight: 700;
        padding: 3px 6px;
        border: 0;
        text-align: left;
    }
    table.planilla-table tr.planilla-subtotal td {
        font-weight: 700;
        font-size: 6px;
    }
    table.planilla-table tr.planilla-subtotal-depto td {
        background: #f8fafc;
        font-style: italic;
    }
    table.planilla-table tr.planilla-subtotal-area td {
        background: #eef2ff;
    }
    table.planilla-table tr.planilla-subtotal-general td {
        background: #f1f5f9;
        font-size: 6.5px;
    }

    table.planilla-table tbody td,
    table.planilla-table tfoot td {
        font-size: 6px;
        line-height: 1.25;
    }
    table.planilla-table tbody td.num,
    table.planilla-table tfoot td.num {
        text-align: right;
        white-space: nowrap;
    }
    table.planilla-table tbody td.label-wrap {
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        font-size: 5.8px;
        line-height: 1.2;
    }
    table.planilla-table td.amount {
        font-family: 'Courier New', Courier, monospace;
    }

    .firmantes-section { margin-top: 20px; page-break-inside: avoid; }
    .firmantes-section h2 { font-size: 8px; margin-bottom: 6px; }
    table.firmantes-table { width: 100%; border: none; border-collapse: collapse; }
    table.firmantes-table td {
        border: none;
        text-align: center;
        vertical-align: top;
        padding: 6px;
        font-size: 7px;
    }
    .firma-line {
        border-top: 1px solid #333;
        width: 75%;
        margin: 28px auto 6px;
        height: 0;
    }
</style>
