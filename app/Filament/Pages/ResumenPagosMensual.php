<?php

namespace App\Filament\Pages;

use App\Models\Proveedor;
use App\Services\ResumenPagosMensual as ResumenPagosMensualService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ResumenPagosMensual extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Resumen mensual';

    protected static ?string $title = 'Resumen mensual de pagos';

    protected string $view = 'filament.pages.resumen-pagos-mensual';

    public ?int $proveedorId = null;

    public string $buscarCodigo = '';

    public string $buscarNombre = '';

    public string $periodoInicio;

    public function mount(): void
    {
        $this->periodoInicio = now()->format('Y-m');
    }

    public function updatedBuscarCodigo(): void
    {
        if ($this->buscarCodigo === '') {
            $this->proveedorId = null;
            $this->buscarNombre = '';

            return;
        }

        $proveedor = Proveedor::query()->where('codigo', $this->buscarCodigo)->first();

        if (! $proveedor) {
            return;
        }

        $this->proveedorId = $proveedor->id;
        $this->buscarNombre = $proveedor->nombre;
    }

    public function updatedBuscarNombre(): void
    {
        if ($this->buscarNombre === '') {
            $this->proveedorId = null;
            $this->buscarCodigo = '';

            return;
        }

        $proveedor = Proveedor::query()->where('nombre', $this->buscarNombre)->first();

        if (! $proveedor) {
            return;
        }

        $this->proveedorId = $proveedor->id;
        $this->buscarCodigo = $proveedor->codigo;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Proveedor>
     */
    public function proveedoresPorCodigo()
    {
        return Proveedor::query()->orderBy('codigo')->get(['id', 'codigo', 'nombre']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Proveedor>
     */
    public function proveedoresPorNombre()
    {
        return Proveedor::query()->orderBy('nombre')->get(['id', 'codigo', 'nombre']);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function opcionesPeriodo(): array
    {
        $base = now()->startOfMonth()->subMonths(6);

        return collect(range(0, 23))
            ->map(function (int $offset) use ($base) {
                $fecha = $base->clone()->addMonths($offset);

                return [
                    'value' => $fecha->format('Y-m'),
                    'label' => $fecha->translatedFormat('F Y'),
                ];
            })
            ->all();
    }

    /**
     * @return array{meses: array, filas: array, totales_por_mes: array, total_general: float}
     */
    public function resumen(): array
    {
        [$anio, $mes] = array_map('intval', explode('-', $this->periodoInicio));

        return app(ResumenPagosMensualService::class)->generar(
            anioInicio: $anio,
            mesInicio: $mes,
            proveedorId: $this->proveedorId,
        );
    }
}
