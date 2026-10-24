<x-filament-panels::page>
    @php
        $resumen = $this->resumen();
    @endphp

    <style>
        .rpm-table-wrap {
            overflow-x: auto;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.5rem;
        }
        .dark .rpm-table-wrap {
            border-color: rgba(255, 255, 255, 0.1);
        }
        .rpm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .rpm-table th,
        .rpm-table td {
            padding: 0.5rem 0.75rem;
            white-space: nowrap;
            border-bottom: 1px solid rgb(243 244 246);
        }
        .dark .rpm-table th,
        .dark .rpm-table td {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        .rpm-table th {
            text-align: right;
            font-weight: 600;
            color: rgb(55 65 81);
            background: rgb(249 250 251);
            border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .rpm-table th {
            color: rgb(229 231 235);
            background: rgb(31 41 55);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .rpm-table th.rpm-col-text,
        .rpm-table td.rpm-col-text {
            text-align: left;
        }
        .rpm-table td.rpm-col-num {
            text-align: right;
        }
        .rpm-table tbody tr:hover {
            background: rgb(249 250 251);
        }
        .dark .rpm-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .rpm-table .rpm-sticky-cod,
        .rpm-table .rpm-sticky-nombre {
            position: sticky;
            background: white;
            z-index: 1;
        }
        .dark .rpm-table .rpm-sticky-cod,
        .dark .rpm-table .rpm-sticky-nombre {
            background: rgb(17 24 39);
        }
        .rpm-table thead .rpm-sticky-cod,
        .rpm-table thead .rpm-sticky-nombre,
        .rpm-table tfoot .rpm-sticky-cod {
            background: rgb(249 250 251);
        }
        .dark .rpm-table thead .rpm-sticky-cod,
        .dark .rpm-table thead .rpm-sticky-nombre,
        .dark .rpm-table tfoot .rpm-sticky-cod {
            background: rgb(31 41 55);
        }
        .rpm-table .rpm-sticky-cod {
            left: 0;
            min-width: 4rem;
        }
        .rpm-table .rpm-sticky-nombre {
            left: 4rem;
            min-width: 12rem;
        }
        .rpm-table .rpm-muted {
            color: rgb(156 163 175);
        }
        .rpm-table tfoot tr {
            border-top: 2px solid rgb(209 213 219);
            font-weight: 600;
            background: rgb(249 250 251);
        }
        .dark .rpm-table tfoot tr {
            border-top-color: rgba(255, 255, 255, 0.2);
            background: rgb(31 41 55);
        }
        .rpm-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .rpm-filter-field {
            flex: 1 1 12rem;
            min-width: 12rem;
        }
        .rpm-filter-field label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(55 65 81);
        }
        .dark .rpm-filter-field label {
            color: rgb(209 213 219);
        }
    </style>

    <x-filament::section>
        <div class="rpm-filters">
            <div class="rpm-filter-field">
                <label>Proveedor por código</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        list="rpm-codigos"
                        wire:model.live.debounce.400ms="buscarCodigo"
                        placeholder="Buscar código..."
                        autocomplete="off"
                    />
                </x-filament::input.wrapper>
                <datalist id="rpm-codigos">
                    @foreach ($this->proveedoresPorCodigo() as $proveedor)
                        <option value="{{ $proveedor->codigo }}">{{ $proveedor->nombre }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="rpm-filter-field">
                <label>Proveedor por nombre</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        list="rpm-nombres"
                        wire:model.live.debounce.400ms="buscarNombre"
                        placeholder="Buscar nombre..."
                        autocomplete="off"
                    />
                </x-filament::input.wrapper>
                <datalist id="rpm-nombres">
                    @foreach ($this->proveedoresPorNombre() as $proveedor)
                        <option value="{{ $proveedor->nombre }}">{{ $proveedor->codigo }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="rpm-filter-field">
                <label>Mes de inicio</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="periodoInicio">
                        @foreach ($this->opcionesPeriodo() as $opcion)
                            <option value="{{ $opcion['value'] }}">{{ ucfirst($opcion['label']) }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        @if (empty($resumen['filas']))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No hay cuotas pendientes para el rango y búsqueda seleccionados.
            </p>
        @else
            <div class="rpm-table-wrap">
                <table class="rpm-table">
                    <thead>
                        <tr>
                            <th class="rpm-col-text rpm-sticky-cod">Cód.</th>
                            <th class="rpm-col-text rpm-sticky-nombre">Proveedor</th>
                            @foreach ($resumen['meses'] as $mes)
                                <th>{{ $mes['label'] }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resumen['filas'] as $fila)
                            <tr wire:key="rpm-fila-{{ $fila['proveedor_id'] }}" id="rpm-fila-{{ $fila['proveedor_id'] }}">
                                <td class="rpm-col-text rpm-sticky-cod">{{ $fila['codigo'] }}</td>
                                <td class="rpm-col-text rpm-sticky-nombre">{{ $fila['nombre'] }}</td>
                                @foreach ($resumen['meses'] as $mes)
                                    <td class="rpm-col-num">
                                        @if ($fila['montos'][$mes['periodo']] > 0)
                                            $ {{ number_format($fila['montos'][$mes['periodo']], 2, ',', '.') }}
                                        @else
                                            <span class="rpm-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="rpm-col-num" style="font-weight: 600;">
                                    $ {{ number_format($fila['total'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="rpm-col-text rpm-sticky-cod" colspan="2">Total</td>
                            @foreach ($resumen['meses'] as $mes)
                                <td class="rpm-col-num">
                                    $ {{ number_format($resumen['totales_por_mes'][$mes['periodo']], 2, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="rpm-col-num">
                                $ {{ number_format($resumen['total_general'], 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
