<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
     protected static ?string $title = 'Listado de Usuarios';
    protected static ?string $navigationLabel = 'Usuarios';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear Usuario')
                ->url(UserResource::getUrl('create'))
                ->iconButton('heroicon-o-plus'),
        ];
    }
}
