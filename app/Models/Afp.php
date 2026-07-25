<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Afp extends Model
{
    protected $table = 'AFP';
    protected $primaryKey = 'ID_AFP';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'PORCENTAJEPATRONAL'  => 'decimal:4',
        'PORCENTAJEEMPLEADOR' => 'decimal:4',
        'DEVENGADOMAXIMO'     => 'decimal:2',
        'DEVENGADOMINIMO'     => 'decimal:2',
        'ESACTIVO'            => 'boolean',
    ];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_AFP', 'ID_AFP');
    }
}
