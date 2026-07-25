<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDescuento extends Model
{
    public const CATEGORIA_LEY = 'LEY';
    public const CATEGORIA_PRESTAMO = 'PRESTAMO';
    public const CATEGORIA_DESCUENTO = 'DESCUENTO';

    protected $table = 'TIPO_DESCUENTO';
    protected $primaryKey = 'ID_TIPODESCUENTO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'NOMBRETIPODESC',
        'DESCRIPCIONTIPODESC',
        'CATEGORIA',
        'ESACTIVO',
    ];
}
