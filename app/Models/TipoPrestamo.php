<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPrestamo extends Model
{
    protected $table = 'TIPO_PRESTAMO';
    protected $primaryKey = 'ID_TIPOPRESTAMO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'NOMBREPRESTAMO',
        'DESCRIPCION',
        'ESACTIVO',
    ];
}
