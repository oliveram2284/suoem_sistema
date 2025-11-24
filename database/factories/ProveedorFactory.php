<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proveedor>
 */
class ProveedorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->bothify('####'),
            'nombre' => fake()->name(),
            'razon_social' => fake()->company(),
            'cuit' => fake()->bothify('##########'),
            'direccion' => fake()->address(),
            'ciudad' => fake()->city(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->email(),
        ];
    }
}
