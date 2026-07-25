<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establecimiento extends Model
{
    protected $table = 'ESTABLECIMIENTO';
    protected $primaryKey = 'ID_ESTABLECIMIENTO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class, 'ID_DISTRITO', 'ID_DISTRITO');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_ESTABLECIMIENTO', 'ID_ESTABLECIMIENTO');
    }
}
