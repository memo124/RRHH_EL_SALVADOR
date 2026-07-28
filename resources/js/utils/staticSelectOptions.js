/** Opciones estáticas para AsyncSelect (virtual scroll local). */
export const GENERO_OPTIONS = [
    { value: 'M', label: 'Masculino' },
    { value: 'F', label: 'Femenino' },
];

export const SI_NO_BOOL_OPTIONS = [
    { value: true, label: 'Sí' },
    { value: false, label: 'No' },
];

export const ACTIVO_BOOL_OPTIONS = [
    { value: true, label: 'Activo' },
    { value: false, label: 'Inactivo' },
];

export const FORMA_PAGO_OPTIONS = [
    { value: 'Transferencia', label: 'Transferencia Bancaria' },
    { value: 'Cheque', label: 'Cheque' },
    { value: 'Efectivo', label: 'Efectivo' },
];

export const TIPO_MARCACION_OPTIONS = [
    { value: 'ENTRADA', label: 'Entrada' },
    { value: 'SALIDA', label: 'Salida' },
];

export const MODALIDAD_HE_OPTIONS = [
    { value: 'ADICIONAL', label: 'Adicional' },
    { value: 'FIJA', label: 'Fija' },
];

export const JORNADA_HE_OPTIONS = [
    { value: 'DIURNA', label: 'Diurna' },
    { value: 'NOCTURNA', label: 'Nocturna' },
];

export const CATEGORIA_DESCUENTO_OPTIONS = [
    { value: 'LEY', label: 'Ley (ISSS, AFP, Renta)' },
    { value: 'PRESTAMO', label: 'Préstamo / Anticipo' },
    { value: 'DESCUENTO', label: 'Descuento general' },
];

export const THEME_OPTIONS = [
    { value: 'auto', label: 'Automático (horario)', icon: 'clock' },
    { value: 'system', label: 'Seguir navegador', icon: 'monitor' },
    { value: 'light', label: 'Claro', icon: 'sun' },
    { value: 'dark', label: 'Oscuro', icon: 'moon' },
];

export const BANK_DELIMITER_OPTIONS = [
    { value: ',', label: 'Coma (,)' },
    { value: ';', label: 'Punto y coma (;)' },
    { value: '|', label: 'Pipe (|)' },
    { value: '\t', label: 'Tabulador' },
];

export const BANK_AMOUNT_FORMAT_OPTIONS = [
    { value: 'decimal', label: 'Decimal (1234.56)' },
    { value: 'cents', label: 'Centavos (123456)' },
    { value: 'plain', label: 'Entero (1234)' },
];
