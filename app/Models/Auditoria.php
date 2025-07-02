<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = [
        'user_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'cambios',
        'fecha_evento',
    ];

    // Si solo usas fecha_evento y no quieres los timestamps por defecto:
    // public $timestamps = false;

    // Si quieres que 'cambios' sea un array automáticamente
    protected $casts = [
        'cambios' => 'array',
        'fecha_evento' => 'datetime',
    ];
}
