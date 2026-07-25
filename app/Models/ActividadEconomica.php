<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActividadEconomica extends Model
{
    protected $table = 'ACTIVIDAD_ECONOMICA';
    protected $primaryKey = 'ID_ACTIVIDAD_ECONOMICA';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
    ];

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'ID_ACTIVIDAD_ECONOMICA', 'ID_ACTIVIDAD_ECONOMICA');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_ACTIVIDAD_ECONOMICA', 'ID_ACTIVIDAD_ECONOMICA');
    }
}
