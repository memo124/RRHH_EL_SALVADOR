<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorasExtras extends Model
{
    protected $table = 'HORAS_EXTRAS';
    protected $primaryKey = 'ID_HORASEXTRAS';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'PORCENTAJEEXTRA' => 'decimal:2',
        'FACTOR' => 'decimal:4',
        'ES_DOMINICAL' => 'boolean',
    ];

    protected $fillable = [
        'TIPOHORAEXTRA',
        'PORCENTAJEEXTRA',
        'FACTOR',
        'MODALIDAD',
        'JORNADA',
        'ES_DOMINICAL',
        'CODIGO',
    ];
}
