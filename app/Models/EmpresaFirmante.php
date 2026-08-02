<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaFirmante extends Model
{
    protected $table = 'EMPRESA_FIRMANTE';
    protected $primaryKey = 'ID_FIRMANTE';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }
}
