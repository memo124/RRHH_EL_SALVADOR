<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametrosAguinaldos extends Model
{
    protected $table = 'PARAMETROS_AGUINALDOS';
    protected $primaryKey = 'ID_PARAMETRO_AGUINALDO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'DESDE_ANOS' => 'integer',
        'HASTA_ANOS' => 'integer',
        'NUMERO_DIAS' => 'integer',
        'SOBRE_EXCEDENTE' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }
}
