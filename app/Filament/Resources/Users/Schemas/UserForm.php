<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            Select::make('role')->label('Peran')->options(Role::options())->required()->default('editor'),
            TextInput::make('password')->label('Kata sandi')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->minLength(10)
                ->helperText('Kosongkan saat mengedit bila tidak ingin mengganti.'),
        ]);
    }
}
