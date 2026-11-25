<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->label('Peran')->badge()->formatStateUsing(fn ($state) => Role::from($state)->label()),
            ])
            ->recordActions([EditAction::make()]);
    }
}
