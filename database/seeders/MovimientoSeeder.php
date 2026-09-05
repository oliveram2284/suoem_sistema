<?php

namespace Database\Seeders;

use App\Models\Concepto;
use App\Models\Movimiento;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\GeneradorCuotas;
use Illuminate\Database\Seeder;

class MovimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedores = Proveedor::inRandomOrder()->limit(20)->get();
        $concepto = Concepto::where('codigo', '00')->first();
        $user = User::first();

        if ($proveedores->isEmpty() || ! $concepto || ! $user) {
            return;
        }

        $generador = app(GeneradorCuotas::class);

        foreach ($proveedores as $proveedor) {
            $anioLiquidacion = fake()->numberBetween(2024, 2026);
            $mesLiquidacion = fake()->numberBetween(1, 12);
            $desfasaje = fake()->numberBetween(0, 3);
            $diaPago = fake()->numberBetween(1, 28);
            $montoTotal = fake()->randomFloat(2, 100_000, 15_000_000);
            $cantidadCuotas = fake()->numberBetween(1, 24);

            $movimiento = Movimiento::create([
                'proveedor_id' => $proveedor->id,
                'concepto_id' => $concepto->id,
                'ejercicio' => $anioLiquidacion,
                'anio_liquidacion' => $anioLiquidacion,
                'mes_liquidacion' => $mesLiquidacion,
                'desfasaje_primer_pago' => $desfasaje,
                'dia_pago' => $diaPago,
                'monto_total' => $montoTotal,
                'cantidad_cuotas' => $cantidadCuotas,
                'descripcion' => fake()->optional()->sentence(),
                'user_id' => $user->id,
            ]);

            $cuotas = $generador->generar(
                $anioLiquidacion,
                $mesLiquidacion,
                $desfasaje,
                $diaPago,
                $montoTotal,
                $cantidadCuotas,
            );

            $movimiento->cuotas()->createMany($cuotas);
        }
    }
}
