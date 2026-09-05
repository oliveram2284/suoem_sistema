<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimiento_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();

            $table->unsignedSmallInteger('nro_cuota');
            $table->smallInteger('anio_pago');
            $table->smallInteger('mes_pago');
            $table->date('fecha_vencimiento');

            $table->decimal('importe', 14, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->string('observacion')->nullable();

            $table->timestamps();

            $table->unique(['movimiento_id', 'nro_cuota']);
            $table->index(['anio_pago', 'mes_pago']);
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_cuotas');
    }
};
