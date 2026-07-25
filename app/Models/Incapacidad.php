<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incapacidad extends Model
{
    protected $table = 'INCAPACIDAD';
    protected $primaryKey = 'ID_INCAPACIDAD';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'FECHA_EMISION' => 'date',
        'FECHA_INICIO' => 'date',
        'FECHA_FIN' => 'date',
        'DIAS_TOTALES' => 'integer',
        'DIAS_PAGADOS_PATRONO' => 'integer',
        'DIAS_SUBSIDIADOS_ISSS' => 'integer',
        'DIAS_NO_PAGADOS' => 'integer',
        'FECHA_REGISTRO' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_EMPLEADO', 'ID_EMPLEADO');
    }

    public function tipoIncapacidad(): BelongsTo
    {
        return $this->belongsTo(TipoIncapacidad::class, 'ID_TIPOINCAPACIDAD', 'ID_TIPOINCAPACIDAD');
    }

    public function subsidios(): HasMany
    {
        return $this->hasMany(SubsidioIsss::class, 'ID_INCAPACIDAD', 'ID_INCAPACIDAD');
    }
}
