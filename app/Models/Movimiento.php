<?php

namespace App\Models;

use App\Enums\EstadoCuota;
use App\Enums\EstadoMovimiento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    /** @use HasFactory<\Database\Factories\MovimientoFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'proveedor_id',
        'concepto_id',
        'ejercicio',
        'anio_liquidacion',
        'mes_liquidacion',
        'desfasaje_primer_pago',
        'dia_pago',
        'monto_total',
        'cantidad_cuotas',
        'descripcion',
        'estado',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'estado' => EstadoMovimiento::class,
        ];
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function concepto()
    {
        return $this->belongsTo(Concepto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cuotas()
    {
        return $this->hasMany(MovimientoCuota::class)->orderBy('nro_cuota');
    }

    public function getTotalCuotasAttribute(): float
    {
        return (float) $this->cuotas->sum('importe');
    }

    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->cuotas->where('estado', EstadoCuota::Pagada)->sum('importe');
    }

    public function getSaldoAttribute(): float
    {
        return (float) $this->monto_total - $this->total_pagado;
    }

    public function getPeriodoLiquidacionAttribute(): string
    {
        return sprintf('%02d/%d', $this->mes_liquidacion, $this->anio_liquidacion);
    }

    public function cuadra(): bool
    {
        return abs($this->total_cuotas - (float) $this->monto_total) < 0.01;
    }
}
