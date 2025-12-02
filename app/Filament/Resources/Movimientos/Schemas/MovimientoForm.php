<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\Proveedor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MovimientoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload()
                    ->placeholder('Selecciona un proveedor')
                    ->required(),
                Select::make('concepto_id')
                    ->label('Concepto')
                    ->relationship('concepto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->placeholder('Selecciona un concepto')
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                Textarea::make('observacion')
                    ->columnSpanFull(),
                Select::make('estado')
                ->options(
                    [
                        'pendiente' => 'Pendiente',
                        'pagado'    => 'Pagado',
                        'anulado'   => 'Anulado',
                        'cancelado' => 'Cancelado',
                    ])->default('pendiente')
                    ->required(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
