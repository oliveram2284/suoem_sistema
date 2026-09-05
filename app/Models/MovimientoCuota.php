<?php

namespace App\Models;

use App\Enums\EstadoCuota;
use Illuminate\Database\Eloquent\Model;

class MovimientoCuota extends Model
{
    protected $table = 'movimiento_cuotas';

    protected $fillable = [
        'movimiento_id',
        'nro_cuota',
        'anio_pago',
        'mes_pago',
        'fecha_vencimiento',
        'importe',
        'estado',
        'fecha_pago',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'importe' => 'decimal:2',
            'fecha_vencimiento' => 'date',
            'fecha_pago' => 'date',
            'estado' => EstadoCuota::class,
        ];
    }

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class);
    }
}
