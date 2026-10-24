<?php

namespace Tests\Feature;

use App\Enums\EstadoCuota;
use App\Filament\Pages\ResumenPagosMensual as ResumenPagosMensualPage;
use App\Models\Movimiento;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ResumenPagosMensual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResumenPagosMensualServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suma_las_cuotas_pendientes_de_un_proveedor_en_su_mes(): void
    {
        $proveedor = Proveedor::factory()->create(['nombre' => 'Acme']);
        $movimiento = Movimiento::factory()->for($proveedor)->create();

        $movimiento->cuotas()->create([
            'nro_cuota' => 1,
            'anio_pago' => 2026,
            'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10',
            'importe' => 1000,
            'estado' => EstadoCuota::Pendiente,
        ]);

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9);

        $this->assertCount(1, $resumen['filas']);
        $this->assertSame('Acme', $resumen['filas'][0]['nombre']);
        $this->assertEqualsWithDelta(1000.0, $resumen['filas'][0]['montos']['2026-09'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $resumen['filas'][0]['total'], 0.001);
    }

    public function test_excluye_cuotas_pagadas_y_anuladas(): void
    {
        $proveedor = Proveedor::factory()->create();
        $movimiento = Movimiento::factory()->for($proveedor)->create();

        $movimiento->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10', 'importe' => 500, 'estado' => EstadoCuota::Pagada,
        ]);
        $movimiento->cuotas()->create([
            'nro_cuota' => 2, 'anio_pago' => 2026, 'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10', 'importe' => 700, 'estado' => EstadoCuota::Anulada,
        ]);

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9);

        $this->assertCount(0, $resumen['filas']);
    }

    public function test_excluye_cuotas_fuera_del_rango_de_meses(): void
    {
        $proveedor = Proveedor::factory()->create();
        $movimiento = Movimiento::factory()->for($proveedor)->create();

        $movimiento->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2027, 'mes_pago' => 10,
            'fecha_vencimiento' => '2027-10-10', 'importe' => 900, 'estado' => EstadoCuota::Pendiente,
        ]);

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9, cantidadMeses: 12);

        $this->assertCount(0, $resumen['filas']);
    }

    public function test_suma_varios_movimientos_del_mismo_proveedor_en_el_mismo_mes(): void
    {
        $proveedor = Proveedor::factory()->create();
        $movimientoA = Movimiento::factory()->for($proveedor)->create();
        $movimientoB = Movimiento::factory()->for($proveedor)->create();

        $movimientoA->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10', 'importe' => 300, 'estado' => EstadoCuota::Pendiente,
        ]);
        $movimientoB->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10', 'importe' => 450, 'estado' => EstadoCuota::Pendiente,
        ]);

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9);

        $this->assertCount(1, $resumen['filas']);
        $this->assertEqualsWithDelta(750.0, $resumen['filas'][0]['montos']['2026-09'], 0.001);
    }

    public function test_calcula_totales_por_mes_y_total_general(): void
    {
        $proveedorA = Proveedor::factory()->create();
        $proveedorB = Proveedor::factory()->create();
        $movimientoA = Movimiento::factory()->for($proveedorA)->create();
        $movimientoB = Movimiento::factory()->for($proveedorB)->create();

        $movimientoA->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
            'fecha_vencimiento' => '2026-09-10', 'importe' => 100, 'estado' => EstadoCuota::Pendiente,
        ]);
        $movimientoB->cuotas()->create([
            'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 10,
            'fecha_vencimiento' => '2026-10-10', 'importe' => 200, 'estado' => EstadoCuota::Pendiente,
        ]);

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9);

        $this->assertEqualsWithDelta(100.0, $resumen['totales_por_mes']['2026-09'], 0.001);
        $this->assertEqualsWithDelta(200.0, $resumen['totales_por_mes']['2026-10'], 0.001);
        $this->assertEqualsWithDelta(300.0, $resumen['total_general'], 0.001);
    }

    public function test_filtra_por_proveedor_id(): void
    {
        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        $otro = Proveedor::factory()->create(['nombre' => 'Otro Comercio', 'codigo' => '222']);

        foreach ([$acme, $otro] as $proveedor) {
            $movimiento = Movimiento::factory()->for($proveedor)->create();
            $movimiento->cuotas()->create([
                'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
                'fecha_vencimiento' => '2026-09-10', 'importe' => 100, 'estado' => EstadoCuota::Pendiente,
            ]);
        }

        $resumen = app(ResumenPagosMensual::class)->generar(anioInicio: 2026, mesInicio: 9, proveedorId: $acme->id);

        $this->assertCount(1, $resumen['filas']);
        $this->assertSame('Acme', $resumen['filas'][0]['nombre']);
    }

    public function test_la_pagina_renderiza_el_resumen_y_responde_a_los_filtros(): void
    {
        $this->actingAs(User::factory()->create());

        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        $otro = Proveedor::factory()->create(['nombre' => 'Otro Comercio', 'codigo' => '222']);

        foreach ([$acme, $otro] as $proveedor) {
            $movimiento = Movimiento::factory()->for($proveedor)->create();
            $movimiento->cuotas()->create([
                'nro_cuota' => 1, 'anio_pago' => 2026, 'mes_pago' => 9,
                'fecha_vencimiento' => '2026-09-10', 'importe' => 1500, 'estado' => EstadoCuota::Pendiente,
            ]);
        }

        Livewire::test(ResumenPagosMensualPage::class)
            ->set('periodoInicio', '2026-09')
            ->assertOk()
            ->assertSee('rpm-fila-'.$acme->id)
            ->assertSee('rpm-fila-'.$otro->id)
            ->set('proveedorId', $acme->id)
            ->assertOk()
            ->assertSee('rpm-fila-'.$acme->id)
            ->assertDontSee('rpm-fila-'.$otro->id);
    }

    public function test_escribir_el_codigo_exacto_completa_el_nombre_y_filtra(): void
    {
        $this->actingAs(User::factory()->create());

        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        Movimiento::factory()->for($acme)->create();

        $component = Livewire::test(ResumenPagosMensualPage::class)
            ->set('buscarCodigo', '111')
            ->assertOk();

        $this->assertSame($acme->id, $component->get('proveedorId'));
        $this->assertSame('Acme', $component->get('buscarNombre'));
    }

    public function test_escribir_el_nombre_exacto_completa_el_codigo_y_filtra(): void
    {
        $this->actingAs(User::factory()->create());

        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        Movimiento::factory()->for($acme)->create();

        $component = Livewire::test(ResumenPagosMensualPage::class)
            ->set('buscarNombre', 'Acme')
            ->assertOk();

        $this->assertSame($acme->id, $component->get('proveedorId'));
        $this->assertSame('111', $component->get('buscarCodigo'));
    }

    public function test_un_codigo_que_no_coincide_con_ningun_proveedor_no_filtra(): void
    {
        $this->actingAs(User::factory()->create());

        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        Movimiento::factory()->for($acme)->create();

        $component = Livewire::test(ResumenPagosMensualPage::class)
            ->set('buscarCodigo', '11')
            ->assertOk();

        $this->assertNull($component->get('proveedorId'));
    }

    public function test_vaciar_la_busqueda_limpia_el_filtro(): void
    {
        $this->actingAs(User::factory()->create());

        $acme = Proveedor::factory()->create(['nombre' => 'Acme', 'codigo' => '111']);
        Movimiento::factory()->for($acme)->create();

        $component = Livewire::test(ResumenPagosMensualPage::class)
            ->set('buscarCodigo', '111')
            ->set('buscarCodigo', '')
            ->assertOk();

        $this->assertNull($component->get('proveedorId'));
        $this->assertSame('', $component->get('buscarNombre'));
    }
}
