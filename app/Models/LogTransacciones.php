<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class LogTransacciones extends Model { protected $table='LOG_TRANSACCIONES'; protected $primaryKey='ID_LOG'; public $incrementing=false; public $timestamps=false; }
