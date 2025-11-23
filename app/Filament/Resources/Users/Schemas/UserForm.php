<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $title = 'Usuarios Alta';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                     ->columnSpanFull()
                    ->required(),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                     ->columnSpanFull()
                    ->required(),
                TextInput::make('password')
                    ->label('Contraseña')
                     ->columnSpanFull()
                    ->password()
                    ->required(),

                DateTimePicker::make('email_verified_at'),
            ]);
    }
}
