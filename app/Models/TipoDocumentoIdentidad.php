<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumentoIdentidad extends Model
{
    protected $table = 'TIPO_DOCUMENTO_IDENTIDAD';
    protected $primaryKey = 'ID_TIPODOCUMENTO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ESACTIVO' => 'boolean',
    ];
}
