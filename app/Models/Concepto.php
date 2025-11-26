<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concepto extends Model
{
    /** @use HasFactory<\Database\Factories\ConceptoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'conceptos';
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
    ];
}
