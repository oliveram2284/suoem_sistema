<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Crear Usuario';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl())
                ->color('gray'),
            Action::make('create')
                ->label('Guardar Usuario')
                ->submit('create')
                ->keyBindings(['mod+s']),
        ];
    }
}
