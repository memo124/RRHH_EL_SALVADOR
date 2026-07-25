<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroCosto extends Model
{
    protected $table = 'CENTRO_COSTO';
    protected $primaryKey = 'ID_CENTROCOSTO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }
}
