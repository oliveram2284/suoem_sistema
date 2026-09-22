<?php

namespace App\Filament\Resources\Movimientos\Tables;

use App\Enums\EstadoMovimiento;
use App\Models\Movimiento;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MovimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('concepto.nombre')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('periodo_liquidacion')
                    ->label('Período')
                    ->sortable(['anio_liquidacion', 'mes_liquidacion']),
                TextColumn::make('cantidad_cuotas')
                    ->label('Cuotas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (EstadoMovimiento $state) => $state->color())
                    ->formatStateUsing(fn (EstadoMovimiento $state) => $state->label()),
            ])
            ->filters([
                SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('concepto_id')
                    ->label('Concepto')
                    ->relationship('concepto', 'nombre')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('estado')
                    ->options(EstadoMovimiento::class),
                SelectFilter::make('anio_liquidacion')
                    ->label('Año de liquidación')
                    ->options(fn () => Movimiento::query()
                        ->distinct()
                        ->orderByDesc('anio_liquidacion')
                        ->pluck('anio_liquidacion', 'anio_liquidacion')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->color('amber'),
                    EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-o-pencil')
                        ->color('primary'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
