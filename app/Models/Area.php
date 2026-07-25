<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'AREA';
    protected $primaryKey = 'ID_AREA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ACTIVA'      => 'boolean',
        'PRORRATEADA' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class, 'ID_AREA', 'ID_AREA');
    }
}
