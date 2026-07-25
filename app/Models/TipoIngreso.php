<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIngreso extends Model
{
    protected $table = 'TIPO_INGRESO';
    protected $primaryKey = 'ID_TIPOINGRESO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'TIPOINGRESO',
        'ESACTIVO',
    ];
}
