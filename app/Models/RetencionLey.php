<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetencionLey extends Model
{
    protected $table = 'RETENCION_LEY';
    protected $primaryKey = 'ID_RETENCIONLEY';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'APORTACIONPATRONAL' => 'decimal:2',
        'APORTACIONEMPLEADO' => 'decimal:2',
        'SALARIOMINIMO' => 'decimal:2',
        'SALARIOMAXIMO' => 'decimal:2',
    ];
}
