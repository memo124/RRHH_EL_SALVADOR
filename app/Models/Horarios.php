<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horarios extends Model
{
    protected $table = 'HORARIOS';
    protected $primaryKey = 'ID_HORARIO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ES_ROTATIVO' => 'boolean',
        'ESACTIVO' => 'boolean',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(HorarioDetalle::class, 'ID_HORARIO', 'ID_HORARIO');
    }
}
