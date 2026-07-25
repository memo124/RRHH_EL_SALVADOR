<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoContratacion extends Model
{
    protected $table = 'TIPO_CONTRATACION';
    protected $primaryKey = 'ID_TIPOCONTRATACION';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ES_EVENTUAL' => 'boolean',
        'APLICA_ISSS' => 'boolean',
        'APLICA_AFP' => 'boolean',
        'APLICA_RENTA_TABLA' => 'boolean',
        'APLICA_RENTA_FIJA' => 'boolean',
        'PORCENTAJE_RENTA_FIJA' => 'decimal:2',
        'APLICA_INSAFORP' => 'boolean',
        'ESACTIVO' => 'boolean',
    ];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_TIPOCONTRATACION', 'ID_TIPOCONTRATACION');
    }
}
