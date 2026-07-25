<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarcacionRaw extends Model
{
    protected $table = 'MARCACION_RAW';
    protected $primaryKey = 'ID_MARCACION';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'FECHA_HORA_MARCACION' => 'datetime',
        'PROCESADO' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }
}
