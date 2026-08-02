<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaContrato extends Model
{
    protected $table = 'PLANTILLA_CONTRATO';
    protected $primaryKey = 'ID_PLANTILLA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
        'FECHA_CREACION' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'ID_PLANTILLA', 'ID_PLANTILLA');
    }
}
