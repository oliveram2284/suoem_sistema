<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMovimiento extends ViewRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil'),
        ];
    }

    public function getTitle(): string
    {
        return "Movimiento #{$this->record->id}";
    }

    public function getBreadcrumb(): string
    {
        return "#{$this->record->id}";
    }
}
