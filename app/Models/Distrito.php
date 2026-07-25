<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distrito extends Model
{
    protected $table = 'DISTRITO';
    protected $primaryKey = 'ID_DISTRITO';
    public $incrementing = false;
    public $timestamps = false;

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'ID_MUNICIPIO', 'ID_MUNICIPIO');
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'ID_DISTRITO', 'ID_DISTRITO');
    }

    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class, 'ID_DISTRITO', 'ID_DISTRITO');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_DISTRITO', 'ID_DISTRITO');
    }
}
