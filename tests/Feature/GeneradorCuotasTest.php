<?php

namespace Tests\Feature;

use App\Services\GeneradorCuotas;
use Tests\TestCase;

class GeneradorCuotasTest extends TestCase
{
    private GeneradorCuotas $generador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generador = new GeneradorCuotas;
    }

    public function test_genera_la_cantidad_correcta_de_cuotas(): void
    {
        $cuotas = $this->generador->generar(2025, 10, 1, 10, 115_000, 4);

        $this->assertCount(4, $cuotas);
    }

    public function test_la_suma_de_las_cuotas_es_exactamente_igual_al_monto_total(): void
    {
        $cuotas = $this->generador->generar(2025, 10, 1, 10, 100_000, 3);
        $suma = array_sum(array_column($cuotas, 'importe'));

        $this->assertEqualsWithDelta(100_000, $suma, 0.001);
        $this->assertSame(33_333.33, $cuotas[0]['importe']);
        $this->assertSame(33_333.33, $cuotas[1]['importe']);
        $this->assertSame(33_333.34, $cuotas[2]['importe']);
    }

    public function test_la_suma_cuadra_con_montos_que_no_dividen_exacto(): void
    {
        $cuotas = $this->generador->generar(2025, 10, 1, 10, 115_000, 3);
        $suma = array_sum(array_column($cuotas, 'importe'));

        $this->assertEqualsWithDelta(115_000, $suma, 0.001);
        $this->assertSame(38_333.33, $cuotas[0]['importe']);
        $this->assertSame(38_333.33, $cuotas[1]['importe']);
        $this->assertSame(38_333.34, $cuotas[2]['importe']);
    }

    public function test_los_periodos_respetan_el_desfasaje(): void
    {
        $cuotas = $this->generador->generar(2025, 10, 1, 10, 115_000, 4);

        $periodos = array_map(fn ($c) => [$c['anio_pago'], $c['mes_pago']], $cuotas);

        $this->assertSame([
            [2025, 11],
            [2025, 12],
            [2026, 1],
            [2026, 2],
        ], $periodos);
    }

    public function test_el_desfasaje_hace_rollover_de_anio_correctamente(): void
    {
        $cuotas = $this->generador->generar(2025, 11, 2, 10, 100_000, 4);

        $periodos = array_map(fn ($c) => [$c['anio_pago'], $c['mes_pago']], $cuotas);

        $this->assertSame([
            [2026, 1],
            [2026, 2],
            [2026, 3],
            [2026, 4],
        ], $periodos);
    }

    public function test_clamp_de_dia_pago_31_en_febrero(): void
    {
        $cuotas = $this->generador->generar(2025, 12, 1, 31, 100_000, 2);

        $this->assertSame('2026-01-31', $cuotas[0]['fecha_vencimiento']);
        $this->assertSame('2026-02-28', $cuotas[1]['fecha_vencimiento']);
    }

    public function test_clamp_de_dia_pago_31_en_mes_de_30_dias(): void
    {
        $cuotas = $this->generador->generar(2026, 3, 1, 31, 100_000, 1);

        $this->assertSame('2026-04-30', $cuotas[0]['fecha_vencimiento']);
    }
}
