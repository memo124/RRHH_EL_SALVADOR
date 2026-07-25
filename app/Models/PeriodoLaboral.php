<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoLaboral extends Model
{
    protected $table = 'PERIODO_LABORAL';
    protected $primaryKey = 'ID_PERIODO';
    public $incrementing = false;
    public $timestamps = false;
}
