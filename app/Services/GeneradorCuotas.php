<?php

namespace App\Services;

use Carbon\Carbon;

class GeneradorCuotas
{
    /**
     * @return array<int, array{nro_cuota:int, anio_pago:int, mes_pago:int,
     *                          fecha_vencimiento:string, importe:float}>
     */
    public function generar(
        int $anioLiquidacion,
        int $mesLiquidacion,
        int $desfasaje,
        int $diaPago,
        float $montoTotal,
        int $cantidadCuotas,
    ): array {
        $base = Carbon::create($anioLiquidacion, $mesLiquidacion, 1)->addMonths($desfasaje);
        $cuotaBase = round($montoTotal / $cantidadCuotas, 2);

        $filas = [];

        for ($n = 1; $n <= $cantidadCuotas; $n++) {
            $periodo = $base->clone()->addMonths($n - 1);

            $importe = $n < $cantidadCuotas
                ? $cuotaBase
                : round($montoTotal - $cuotaBase * ($cantidadCuotas - 1), 2);

            $filas[] = [
                'nro_cuota' => $n,
                'anio_pago' => $periodo->year,
                'mes_pago' => $periodo->month,
                'fecha_vencimiento' => $this->fechaVencimiento($periodo->year, $periodo->month, $diaPago),
                'importe' => $importe,
            ];
        }

        return $filas;
    }

    private function fechaVencimiento(int $anio, int $mes, int $diaPago): string
    {
        $finDeMes = Carbon::create($anio, $mes, 1)->endOfMonth();
        $dia = min($diaPago, $finDeMes->day);

        return Carbon::create($anio, $mes, $dia)->toDateString();
    }
}
