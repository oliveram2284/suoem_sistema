<?php

namespace Database\Factories;

use App\Models\Concepto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movimiento>
 */
class MovimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $anioLiquidacion = fake()->numberBetween(2024, 2026);
        $mesLiquidacion = fake()->numberBetween(1, 12);

        return [
            'proveedor_id' => Proveedor::factory(),
            'concepto_id' => Concepto::factory(),
            'ejercicio' => $anioLiquidacion,
            'anio_liquidacion' => $anioLiquidacion,
            'mes_liquidacion' => $mesLiquidacion,
            'desfasaje_primer_pago' => fake()->numberBetween(0, 3),
            'dia_pago' => fake()->numberBetween(1, 28),
            'monto_total' => fake()->randomFloat(2, 100_000, 15_000_000),
            'cantidad_cuotas' => fake()->numberBetween(1, 24),
            'descripcion' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
