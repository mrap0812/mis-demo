<?php

namespace App\Filament\Resources\ContentPages\Tables;

use App\Models\ContentPage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->weight('bold'),
                TextColumn::make('slug')->label('Alamat')->formatStateUsing(fn (ContentPage $r) => parse_url($r->url(), PHP_URL_PATH)),
                IconColumn::make('is_published')->label('Tampil')->boolean(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->recordActions([
                Action::make('lihat')->label('Lihat')->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                    ->url(fn (ContentPage $r) => $r->url(), shouldOpenInNewTab: true)->visible(fn (ContentPage $r) => $r->is_published),
                EditAction::make(),
            ]);
    }
}
