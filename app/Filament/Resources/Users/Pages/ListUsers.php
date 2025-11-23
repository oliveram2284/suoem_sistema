<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource         = UserResource::class;
    protected static ?string $title           = 'Listado de Usuarios';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $breadcrumb      = 'Listado de Usuarios';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear Usuario')
                ->icon(Heroicon::UserPlus)
                ->url(UserResource::getUrl('create'))
        ];
    }

    protected function getTableTabs(): array
    {
        return [
            Tab::make('Todos')
                ->label('Todos')
                ->icon(Heroicon::Users)
                ->isActiveByDefault(),
        ];
    }
}
