<?php

return [
    'formats' => [
        'csv' => [
            'label' => 'CSV (Excel)',
            'extension' => 'csv',
            'mime' => 'text/csv; charset=UTF-8',
            'description' => 'Separado por comas, compatible con Excel.',
        ],
        'txt_csv' => [
            'label' => 'TXT delimitado',
            'extension' => 'txt',
            'mime' => 'text/plain; charset=UTF-8',
            'description' => 'Texto plano con delimitador configurable (; , tab o |).',
        ],
        'txt_fixed' => [
            'label' => 'TXT ancho fijo',
            'extension' => 'txt',
            'mime' => 'text/plain; charset=UTF-8',
            'description' => 'Columnas alineadas por ancho (layout bancario clásico).',
        ],
        'json' => [
            'label' => 'JSON',
            'extension' => 'json',
            'mime' => 'application/json; charset=UTF-8',
            'description' => 'Estructura JSON para integraciones u otros sistemas.',
        ],
        'xlsx' => [
            'label' => 'Excel (.xlsx)',
            'extension' => 'xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'description' => 'Libro Excel nativo, ideal para revisión y carga bancaria.',
            'client_side' => true,
        ],
    ],

    'delimiters' => [
        ';' => 'Punto y coma (;)',
        ',' => 'Coma (,)',
        "\t" => 'Tabulador',
        '|' => 'Pipe (|)',
    ],

    'amount_formats' => [
        'decimal' => 'Decimal (850.50)',
        'cents' => 'Centavos sin decimal (85050)',
    ],

    'column_groups' => [
        'banco' => 'Datos bancarios',
        'identificacion' => 'Identificación',
        'planilla' => 'Planilla / concepto',
        'montos' => 'Montos y totales',
    ],

    'columns' => [
        'NUMERO_CUENTA' => [
            'label' => 'Cuenta bancaria empleado',
            'group' => 'banco',
            'default' => true,
            'width' => 20,
        ],
        'BANCO_NOMBRE' => [
            'label' => 'Nombre del banco',
            'group' => 'banco',
            'default' => true,
            'width' => 30,
        ],
        'BANCO_ALIAS' => [
            'label' => 'Código / alias banco',
            'group' => 'banco',
            'default' => false,
            'width' => 8,
        ],
        'CODIGO_EMPLEADO' => [
            'label' => 'Código empleado',
            'group' => 'identificacion',
            'default' => true,
            'width' => 15,
        ],
        'NOM_EMPLEADO' => [
            'label' => 'Nombre empleado',
            'group' => 'identificacion',
            'default' => true,
            'width' => 40,
        ],
        'DUI' => [
            'label' => 'DUI',
            'group' => 'identificacion',
            'default' => false,
            'width' => 12,
        ],
        'NIT' => [
            'label' => 'NIT empleado',
            'group' => 'identificacion',
            'default' => false,
            'width' => 20,
        ],
        'TITULO_PLANILLA' => [
            'label' => 'Título / concepto planilla',
            'group' => 'planilla',
            'default' => true,
            'width' => 40,
        ],
        'PERIODO' => [
            'label' => 'Periodo laboral',
            'group' => 'planilla',
            'default' => true,
            'width' => 20,
        ],
        'FECHA_PAGO' => [
            'label' => 'Fecha de pago',
            'group' => 'planilla',
            'default' => true,
            'width' => 12,
        ],
        'FORMA_PAGO' => [
            'label' => 'Forma de pago',
            'group' => 'planilla',
            'default' => false,
            'width' => 18,
        ],
        'CUENTA_EMPRESA' => [
            'label' => 'Cuenta origen empresa',
            'group' => 'planilla',
            'default' => false,
            'width' => 20,
        ],
        'CONCEPTO_EMPRESA' => [
            'label' => 'Concepto cuenta empresa',
            'group' => 'planilla',
            'default' => false,
            'width' => 30,
        ],
        'TOTAL_DEVENGADO' => [
            'label' => 'Total devengado',
            'group' => 'montos',
            'default' => false,
            'width' => 14,
            'numeric' => true,
        ],
        'TOTAL_DEDUCCIONES' => [
            'label' => 'Total descuentos',
            'group' => 'montos',
            'default' => true,
            'width' => 14,
            'numeric' => true,
        ],
        'LIQUIDO_A_RECIBIR' => [
            'label' => 'Total a pagar (líquido)',
            'group' => 'montos',
            'default' => true,
            'width' => 14,
            'numeric' => true,
        ],
        'AFP_EMPLEADO' => [
            'label' => 'AFP empleado',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
        'ISSS_EMPLEADO' => [
            'label' => 'ISSS empleado',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
        'RENTA_EMPLEADO' => [
            'label' => 'Renta (ISR)',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
        'PRESTAMOS' => [
            'label' => 'Préstamos',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
        'OTRO_DESCUENTOS' => [
            'label' => 'Otros descuentos',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
        'SALARIO_BASE' => [
            'label' => 'Salario base',
            'group' => 'montos',
            'default' => false,
            'width' => 12,
            'numeric' => true,
        ],
    ],

    // Sugerencias por banco (El Salvador)
    'bank_presets' => [
        1 => [
            'label' => 'Banco Agrícola',
            'format' => 'txt_csv',
            'delimiter' => ';',
            'amount_format' => 'decimal',
            'columns' => ['NUMERO_CUENTA', 'NOM_EMPLEADO', 'TITULO_PLANILLA', 'LIQUIDO_A_RECIBIR', 'FECHA_PAGO'],
        ],
        2 => [
            'label' => 'Banco Cuscatlán',
            'format' => 'txt_csv',
            'delimiter' => '|',
            'amount_format' => 'decimal',
            'columns' => ['NUMERO_CUENTA', 'CODIGO_EMPLEADO', 'NOM_EMPLEADO', 'LIQUIDO_A_RECIBIR', 'TITULO_PLANILLA'],
        ],
        3 => [
            'label' => 'Banco Davivienda',
            'format' => 'txt_fixed',
            'delimiter' => ';',
            'amount_format' => 'cents',
            'columns' => ['NUMERO_CUENTA', 'NOM_EMPLEADO', 'LIQUIDO_A_RECIBIR', 'TITULO_PLANILLA', 'FECHA_PAGO'],
        ],
        4 => [
            'label' => 'BAC',
            'format' => 'csv',
            'delimiter' => ',',
            'amount_format' => 'decimal',
            'columns' => ['NUMERO_CUENTA', 'NOM_EMPLEADO', 'DUI', 'LIQUIDO_A_RECIBIR', 'TITULO_PLANILLA', 'PERIODO'],
        ],
    ],
];
