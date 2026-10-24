<?php

namespace App\Services;

use App\Enums\EstadoCuota;
use App\Models\MovimientoCuota;
use Carbon\Carbon;

class ResumenPagosMensual
{
    /**
     * @return array{
     *     meses: array<int, array{periodo: string, label: string}>,
     *     filas: array<int, array{proveedor_id: int, codigo: string, nombre: string, montos: array<string, float>, total: float}>,
     *     totales_por_mes: array<string, float>,
     *     total_general: float,
     * }
     */
    public function generar(int $anioInicio, int $mesInicio, int $cantidadMeses = 12, ?int $proveedorId = null): array
    {
        $meses = $this->rangoDeMeses($anioInicio, $mesInicio, $cantidadMeses);
        $periodos = array_column($meses, 'periodo');

        $cuotas = MovimientoCuota::query()
            ->where('estado', EstadoCuota::Pendiente)
            ->with('movimiento.proveedor')
            ->get()
            ->filter(fn (MovimientoCuota $cuota) => in_array($this->periodo($cuota->anio_pago, $cuota->mes_pago), $periodos, true));

        $filasPorProveedor = [];

        foreach ($cuotas as $cuota) {
            $proveedor = $cuota->movimiento->proveedor;

            if ($proveedorId !== null && $proveedor->id !== $proveedorId) {
                continue;
            }

            $periodo = $this->periodo($cuota->anio_pago, $cuota->mes_pago);

            if (! isset($filasPorProveedor[$proveedor->id])) {
                $filasPorProveedor[$proveedor->id] = [
                    'proveedor_id' => $proveedor->id,
                    'codigo' => $proveedor->codigo,
                    'nombre' => $proveedor->nombre,
                    'montos' => array_fill_keys($periodos, 0.0),
                    'total' => 0.0,
                ];
            }

            $importe = (float) $cuota->importe;
            $filasPorProveedor[$proveedor->id]['montos'][$periodo] += $importe;
            $filasPorProveedor[$proveedor->id]['total'] += $importe;
        }

        $filas = array_values($filasPorProveedor);
        usort($filas, fn ($a, $b) => $a['nombre'] <=> $b['nombre']);

        $totalesPorMes = array_fill_keys($periodos, 0.0);
        foreach ($filas as $fila) {
            foreach ($fila['montos'] as $periodo => $monto) {
                $totalesPorMes[$periodo] += $monto;
            }
        }

        return [
            'meses' => $meses,
            'filas' => $filas,
            'totales_por_mes' => $totalesPorMes,
            'total_general' => array_sum($totalesPorMes),
        ];
    }

    /**
     * @return array<int, array{periodo: string, label: string}>
     */
    private function rangoDeMeses(int $anioInicio, int $mesInicio, int $cantidadMeses): array
    {
        $base = Carbon::create($anioInicio, $mesInicio, 1);

        return collect(range(0, $cantidadMeses - 1))
            ->map(function (int $offset) use ($base) {
                $fecha = $base->clone()->addMonths($offset);

                return [
                    'periodo' => $fecha->format('Y-m'),
                    'label' => $fecha->format('m/Y'),
                ];
            })
            ->all();
    }

    private function periodo(int $anio, int $mes): string
    {
        return sprintf('%04d-%02d', $anio, $mes);
    }
}
