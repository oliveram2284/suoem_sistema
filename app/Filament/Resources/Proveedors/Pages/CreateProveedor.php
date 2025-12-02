<?php

namespace App\Filament\Resources\Proveedors\Pages;

use App\Filament\Resources\Proveedors\ProveedorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    protected static string $resource       = ProveedorResource::class;
    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Crear Proveedor';
    }

    public function getBreadcrumb(): string
    {
        return 'Crear Proveedor';
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        // Aquí puedes modificar los datos del formulario antes de crearlos si es necesario
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Proveedor creado')
            ->body('El proveedor ha sido creado exitosamente.');
    }
}
