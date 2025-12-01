<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    /** @use HasFactory<\Database\Factories\MovimientoFactory> */
    use HasFactory, SoftDeletes;

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO    = 'pagado';
    const ESTADO_ANULADO   = 'anulado';
    const ESTADO_CANCELADO = 'cancelado';

    const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_PAGADO,
        self::ESTADO_ANULADO,
        self::ESTADO_CANCELADO,
    ];

    protected $fillable = [
        'proveedor_id',
        'monto',
        'observacion',
        'estado',
        'usuer_id'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
