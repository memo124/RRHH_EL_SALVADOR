<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleado extends Model
{
    protected $table = 'EMPLEADO';
    protected $primaryKey = 'ID_EMPLEADO';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'FECHANACIMIENTO' => 'datetime',
        'FECHAINGRESO' => 'datetime',
        'SALARIOMENSUAL' => 'decimal:2',
        'SALARIODIARIO' => 'decimal:4',
        'HORAS_EXTRAS_FIJAS_DIURAS' => 'decimal:2',
        'HORAS_EXTRAS_FIJAS_NOCTURNAS' => 'decimal:2',
        'JUBILADO' => 'boolean',
        'APLICA_ISSS_OVERRIDE' => 'boolean',
        'APLICA_AFP_OVERRIDE' => 'boolean',
        'APLICA_RENTA_OVERRIDE' => 'boolean',
        'ESACTIVO' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $table_belongs = $this->belongsTo(Empresa::class, 'ID_EMPRESA', 'ID_EMPRESA');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'ID_DEPARTAMENTO', 'ID_DEPARTAMENTO');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'ID_CARGO', 'ID_CARGO');
    }

    public function jefeInmediato(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'ID_JEFE_INMEDIATO', 'ID_EMPLEADO');
    }

    public function tipoContratacion(): BelongsTo
    {
        return $this->belongsTo(TipoContratacion::class, 'ID_TIPOCONTRATACION', 'ID_TIPOCONTRATACION');
    }

    public function afp(): BelongsTo
    {
        return $this->belongsTo(Afp::class, 'ID_AFP', 'ID_AFP');
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class, 'ID_BANCO', 'ID_BANCO');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class, 'ID_DISTRITO', 'ID_DISTRITO');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horarios::class, 'ID_HORARIO', 'ID_HORARIO');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'ID_ESTABLECIMIENTO', 'ID_ESTABLECIMIENTO');
    }

    public function actividadEconomica(): BelongsTo
    {
        return $this->belongsTo(ActividadEconomica::class, 'ID_ACTIVIDAD_ECONOMICA', 'ID_ACTIVIDAD_ECONOMICA');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'ID_CENTROCOSTO', 'ID_CENTROCOSTO');
    }

    public function subalternos(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_JEFE_INMEDIATO', 'ID_EMPLEADO');
    }
}
