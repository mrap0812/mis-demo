<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Models\Program;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('poster')->label('')->disk('public')->imageHeight(56),
                TextColumn::make('title')->label('Program')->searchable()->weight('bold')->description(fn (Program $r) => $r->schedule),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn (string $state) => Program::CATEGORIES[$state] ?? $state),
                TextColumn::make('contact_number')->label('Kontak')->placeholder('-'),
                IconColumn::make('is_published')->label('Tampil')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
