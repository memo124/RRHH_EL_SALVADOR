<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $table = 'DEPARTAMENTO';
    protected $primaryKey = 'ID_DEPARTAMENTO';
    public $incrementing = false;
    public $timestamps = false;

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_AREA', 'ID_AREA');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'ID_DEPARTAMENTO', 'ID_DEPARTAMENTO');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_DEPARTAMENTO', 'ID_DEPARTAMENTO');
    }
}
