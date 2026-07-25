<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetencionIsr extends Model
{
    protected $table = 'RETENCION_ISR';
    protected $primaryKey = 'ID_RETENCIONISR';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'MONTOINICIAL' => 'decimal:2',
        'MONTOFINA' => 'decimal:2',
        'PORCENTAJEAPLICA' => 'decimal:2',
        'SOBREEXCESO' => 'decimal:2',
        'CUOTAFIJA' => 'decimal:2',
    ];
}
