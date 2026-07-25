<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'EMPRESA';
    protected $primaryKey = 'ID_EMPRESA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'EMPRESAACTIVA' => 'boolean',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }
}
