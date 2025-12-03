<?php

namespace App\Filament\Resources\Movimientos\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MovimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monto')
                    ->label('Importe Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'pendiente' => 'heroicon-o-clock',
                        'pagado'    => 'heroicon-o-check-circle',
                        'anulado'   => 'heroicon-o-x-circle',
                        'cancelado' => 'heroicon-o-minus-circle',
                        default     => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'info',
                        'pagado'    => 'success',
                        'anulado'   => 'orange',
                        'cancelado' => 'danger',
                        default     => 'primary',
                    })
                    ->label('Estado')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->searchable()
                    ->sortable(),
                /*TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),*/
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([

                ActionGroup::make([
                    EditAction::make()
                    ->label('Editar')
                    ->iconPosition('before')
                    ->size('sm')
                    ->icon('heroicon-o-pencil'),
                    DeleteAction::make()
                    ->label("Eliminar")
                    ->size('sm')
                    ->icon('heroicon-o-trash'),
                ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->iconSize('md')
                ->label('Acciones'),
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
