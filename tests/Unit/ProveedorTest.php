<?php

namespace Tests\Unit;

use App\Models\Concepto;
use App\Models\Movimiento;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_assignment_fills_expected_attributes(): void
    {
        $proveedor = Proveedor::create([
            'codigo' => '0001',
            'nombre' => 'Proveedor Uno',
            'razon_social' => 'Proveedor Uno SRL',
            'cuit' => '20-12345678-9',
            'direccion' => 'Av. Siempre Viva 123',
            'ciudad' => 'San Juan',
            'telefono' => '4231013',
            'email' => 'contacto@proveedoruno.com',
        ]);

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'codigo' => '0001',
            'nombre' => 'Proveedor Uno',
            'razon_social' => 'Proveedor Uno SRL',
            'cuit' => '20-12345678-9',
        ]);
    }

    public function test_codigo_must_be_unique(): void
    {
        Proveedor::factory()->create(['codigo' => 'DUP-1']);

        $this->expectException(QueryException::class);

        Proveedor::factory()->create(['codigo' => 'DUP-1']);
    }

    public function test_deleting_a_proveedor_soft_deletes_it(): void
    {
        $proveedor = Proveedor::factory()->create();

        $proveedor->delete();

        $this->assertSoftDeleted('proveedores', ['id' => $proveedor->id]);
        $this->assertNull(Proveedor::find($proveedor->id));
        $this->assertNotNull(Proveedor::withTrashed()->find($proveedor->id));
    }

    public function test_movimientos_relationship_returns_related_movimientos(): void
    {
        $proveedor = Proveedor::factory()->create();
        $otroProveedor = Proveedor::factory()->create();
        $concepto = Concepto::factory()->create();
        $user = User::factory()->create();

        $datosBase = [
            'concepto_id' => $concepto->id,
            'ejercicio' => 2026,
            'anio_liquidacion' => 2026,
            'mes_liquidacion' => 7,
            'desfasaje_primer_pago' => 1,
            'dia_pago' => 10,
            'monto_total' => 1500.50,
            'cantidad_cuotas' => 1,
            'user_id' => $user->id,
        ];

        $movimiento = Movimiento::create([
            'proveedor_id' => $proveedor->id,
            ...$datosBase,
        ]);

        Movimiento::create([
            'proveedor_id' => $otroProveedor->id,
            ...$datosBase,
            'monto_total' => 200,
        ]);

        $movimientos = $proveedor->movimientos;

        $this->assertCount(1, $movimientos);
        $this->assertTrue($movimientos->first()->is($movimiento));
    }
}
