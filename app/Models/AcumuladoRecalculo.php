<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcumuladoRecalculo extends Model
{
    protected $table = 'ACUMULADO_RECALCULO';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'ID_EMPLEADO',
        'ID_PLANILLA',
        'MSR',
        'RENTA',
        'RENTA_PENDIENTE_APLICAR',
        'MES',
        'ANIO',
        'DEVENGADO_ACUMULADO',
        'ISSS_EMPLEADO_ACUMULADO',
        'ISSS_PATRONAL_ACUMULADO',
        'INSAFORP_ACUMULADO',
    ];
}
