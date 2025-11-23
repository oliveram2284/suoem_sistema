<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $title = 'Usuarios';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $breadcrumb = 'Usuarios';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table)
            ->recordActions([
                Action::make('edit')
                    ->label('Editar')
                    ->icon(Heroicon::PencilSquare)
                    ->color('primary')
                    ->url(fn (User $record): string => static::getUrl('edit', ['record' => $record])),
                Action::make('delete')
                    ->label('Eliminar')
                    ->icon(Heroicon::Trash)
                    ->requiresConfirmation()
                    ->modalButton('Eliminar')
                    ->color('danger')
                    ->action(function (User $record): void {
                        $record->delete();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

}
