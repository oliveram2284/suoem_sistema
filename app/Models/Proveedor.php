<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    /** @use HasFactory<\Database\Factories\ProveedorFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'proveedores';
    protected static ?string $modelLabel = 'Proveedor';
    protected $fillable = [
        'codigo',
        'nombre',
        'razon_social',
        'cuit',
        'direccion',
        'ciudad',
        'telefono',
        'email'
    ];

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
}
