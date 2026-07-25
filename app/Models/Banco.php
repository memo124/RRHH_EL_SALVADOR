<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banco extends Model
{
    protected $table = 'BANCO';
    protected $primaryKey = 'ID_BANCO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'BANCOACTIVO' => 'boolean',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'ID_PAIS', 'ID_PAIS');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_BANCO', 'ID_BANCO');
    }
}
