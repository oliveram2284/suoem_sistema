<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->table('proveedores')->constrained()->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->text('observacion')->nullable();
            $table->enum('estado', self::ESTADOS)->default(self::ESTADO_PENDIENTE);
            $table->foreignId('user_id')->table('users')->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
