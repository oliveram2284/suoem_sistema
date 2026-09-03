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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('conceptos');

            $table->smallInteger('ejercicio');
            $table->smallInteger('anio_liquidacion');
            $table->smallInteger('mes_liquidacion');
            $table->unsignedTinyInteger('desfasaje_primer_pago')->default(1);
            $table->unsignedTinyInteger('dia_pago');

            $table->decimal('monto_total', 14, 2);
            $table->unsignedSmallInteger('cantidad_cuotas');

            $table->text('descripcion')->nullable();

            $table->string('estado', 20)->default('pendiente')->index();

            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['proveedor_id', 'anio_liquidacion', 'mes_liquidacion']);
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
