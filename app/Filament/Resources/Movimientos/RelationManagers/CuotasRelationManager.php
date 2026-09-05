<?php

namespace App\Filament\Resources\Movimientos\RelationManagers;

use App\Enums\EstadoCuota;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CuotasRelationManager extends RelationManager
{
    protected static string $relationship = 'cuotas';

    protected static ?string $title = 'Cuotas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nro_cuota')
            ->columns([
                TextColumn::make('nro_cuota')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('anio_pago')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('mes_pago')
                    ->label('Mes')
                    ->sortable(),
                TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('importe')
                    ->money('ARS'),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (EstadoCuota $state) => $state->color())
                    ->formatStateUsing(fn (EstadoCuota $state) => $state->label()),
                TextColumn::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('observacion')
                    ->limit(30),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
