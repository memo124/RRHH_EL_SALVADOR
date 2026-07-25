<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoIncapacidad extends Model
{
    protected $table = 'TIPO_INCAPACIDAD';
    protected $primaryKey = 'ID_TIPOINCAPACIDAD';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'PORCENTAJE_SUBSIDIO_ISSS' => 'decimal:2',
        'PORCENTAJE_PAGO_PATRONO' => 'decimal:2',
        'DIAS_INICIO_SUBSIDIO_ISSS' => 'integer',
        'DIAS_MAXIMOS_COBERTURA_PATRONO' => 'integer',
        'ES_MATERNIDAD' => 'boolean',
        'ES_ACCIDENTE_TRABAJO' => 'boolean',
        'ESACTIVO' => 'boolean',
    ];

    public function incapacidades(): HasMany
    {
        return $this->hasMany(Incapacidad::class, 'ID_TIPOINCAPACIDAD', 'ID_TIPOINCAPACIDAD');
    }
}
