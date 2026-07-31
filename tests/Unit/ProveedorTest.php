<?php

namespace Tests\Unit;

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
        $user = User::factory()->create();

        $movimiento = Movimiento::create([
            'proveedor_id' => $proveedor->id,
            'monto' => 1500.50,
            'estado' => Movimiento::ESTADO_PENDIENTE,
            'user_id' => $user->id,
        ]);

        Movimiento::create([
            'proveedor_id' => $otroProveedor->id,
            'monto' => 200,
            'estado' => Movimiento::ESTADO_PENDIENTE,
            'user_id' => $user->id,
        ]);

        $movimientos = $proveedor->movimientos;

        $this->assertCount(1, $movimientos);
        $this->assertTrue($movimientos->first()->is($movimiento));
    }
}
