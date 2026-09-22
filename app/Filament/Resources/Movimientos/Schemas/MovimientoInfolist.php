<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Enums\EstadoMovimiento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MovimientoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la deuda')
                    ->schema([
                        TextEntry::make('proveedor.nombre')
                            ->label('Proveedor'),
                        TextEntry::make('concepto.nombre')
                            ->label('Concepto'),
                        TextEntry::make('periodo_liquidacion')
                            ->label('Período'),
                        TextEntry::make('estado')
                            ->badge()
                            ->color(fn (EstadoMovimiento $state) => $state->color())
                            ->formatStateUsing(fn (EstadoMovimiento $state) => $state->label()),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y')
                    ])
                    ->columns(6)
                    ->columnSpanFull(),

                Section::make('Montos')
                    ->schema([
                        TextEntry::make('monto_total')
                            ->label('Monto total')
                            ->money('ARS'),
                        TextEntry::make('cantidad_cuotas')
                            ->label('Cuotas'),
                        TextEntry::make('total_pagado')
                            ->label('Total pagado')
                            ->money('ARS'),
                        TextEntry::make('saldo')
                            ->label('Saldo')
                            ->money('ARS'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}
