<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'USUARIO';
    protected $primaryKey = 'ID_USUARIO';
    public $incrementing = false;
    protected $keyType = 'int';
    
    const CREATED_AT = 'FECHACREACION';
    const UPDATED_AT = null; // USUARIO table does not have FECHAMODIFICACION

    protected $fillable = [
        'ID_USUARIO',
        'ID_EMPLEADO',
        'USUARIO',
        'CONTRASENA_HASH',
        'EMAIL',
        'ESACTIVO',
        'BLOQUEADO',
        'TEMA',
    ];

    protected $hidden = [
        'CONTRASENA_HASH',
    ];

    public function getAuthPassword()
    {
        return $this->CONTRASENA_HASH;
    }
}
