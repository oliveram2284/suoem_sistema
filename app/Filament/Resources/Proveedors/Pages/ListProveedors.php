<?php

namespace App\Filament\Resources\Proveedors\Pages;

use App\Filament\Resources\Proveedors\Actions\ImportProveedoresAction;
use App\Filament\Resources\Proveedors\ProveedorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProveedors extends ListRecords
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportProveedoresAction::make(),
            CreateAction::make()
                ->label('Crear Proveedor')
                ->successNotificationTitle('Proveedor creado')
                ->successRedirectUrl($this->getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return 'Proveedores';
    }
}

