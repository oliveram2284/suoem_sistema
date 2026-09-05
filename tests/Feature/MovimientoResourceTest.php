<?php

namespace Tests\Feature;

use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Models\Concepto;
use App\Models\Movimiento;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\GeneradorCuotas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovimientoResourceTest extends TestCase
{
    use RefreshDatabase;

    private function datosBase(Proveedor $proveedor, Concepto $concepto): array
    {
        return [
            'proveedor_id' => $proveedor->id,
            'concepto_id' => $concepto->id,
            'ejercicio' => 2026,
            'anio_liquidacion' => 2026,
            'mes_liquidacion' => 1,
            'desfasaje_primer_pago' => 1,
            'dia_pago' => 10,
            'descripcion' => null,
        ];
    }

    public function test_no_se_puede_guardar_si_la_suma_de_cuotas_no_coincide_con_el_monto_total(): void
    {
        $this->actingAs(User::factory()->create());

        $proveedor = Proveedor::factory()->create();
        $concepto = Concepto::factory()->create();

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                ...$this->datosBase($proveedor, $concepto),
                'monto_total' => 100_000,
                'cantidad_cuotas' => 2,
                'cuotas' => [
                    ['nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 2, 'fecha_vencimiento' => '2026-02-10', 'importe' => 10],
                    ['nro_cuota' => 2, 'anio_pago' => 2026, 'mes_pago' => 3, 'fecha_vencimiento' => '2026-03-10', 'importe' => 20],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_al_crear_un_movimiento_se_generan_las_cuotas_correctamente(): void
    {
        $this->actingAs(User::factory()->create());

        $proveedor = Proveedor::factory()->create();
        $concepto = Concepto::factory()->create();

        $cuotas = app(GeneradorCuotas::class)->generar(2026, 1, 1, 10, 115_000, 3);

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                ...$this->datosBase($proveedor, $concepto),
                'monto_total' => 115_000,
                'cantidad_cuotas' => 3,
                'cuotas' => $cuotas,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('movimientos', 1);
        $this->assertDatabaseHas('movimientos', [
            'proveedor_id' => $proveedor->id,
            'monto_total' => 115_000,
            'cantidad_cuotas' => 3,
        ]);

        $movimiento = Movimiento::first();

        $this->assertCount(3, $movimiento->cuotas);
        $this->assertEqualsWithDelta(115_000, $movimiento->cuotas->sum('importe'), 0.001);
    }
}
