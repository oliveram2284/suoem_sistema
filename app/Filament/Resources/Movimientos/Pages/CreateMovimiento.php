<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMovimiento extends CreateRecord
{
    protected static string $resource = MovimientoResource::class;

    public function getTitle(): string
    {
        return 'Crear Movimiento';
    }

    public function getBreadcrumb(): string
    {
        return 'Crear Movimiento';
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Movimiento creado')
            ->body('El movimiento ha sido creado exitosamente.');
    }
}
