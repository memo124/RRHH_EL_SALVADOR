<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    protected $table = 'CARGO';
    protected $primaryKey = 'ID_CARGO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'CARGOESTADO' => 'boolean',
        'NIVEL_JERARQUICO' => 'integer',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'ID_DEPARTAMENTO', 'ID_DEPARTAMENTO');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }

    public function cargoPadre(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'ID_CARGO_PADRE', 'ID_CARGO');
    }

    public function cargosHijos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'ID_CARGO_PADRE', 'ID_CARGO');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_CARGO', 'ID_CARGO');
    }
}
