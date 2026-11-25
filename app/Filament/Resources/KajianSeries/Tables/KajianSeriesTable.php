<?php

namespace App\Filament\Resources\KajianSeries\Tables;

use App\Models\KajianSeries;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KajianSeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->orderByRaw('CASE day_of_week WHEN 0 THEN 7 ELSE day_of_week END')->orderBy('sort'))
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('day_of_week')->label('Hari')->formatStateUsing(fn ($state) => KajianSeries::DAYS[$state] ?? $state),
                TextColumn::make('slot')->label('Waktu')->formatStateUsing(fn ($state) => KajianSeries::SLOTS[$state] ?? $state),
                TextColumn::make('title')->label('Kajian')->searchable()->wrap()->description(fn (KajianSeries $r) => $r->kitab),
                TextColumn::make('speaker.name')->label('Pemateri')->placeholder('Bergilir / belum ada')->wrap(),
                TextColumn::make('badge')->label('Aturan')->badge()->color('warning')->placeholder('Setiap pekan'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('category')->label('Jenis')->options(KajianSeries::CATEGORIES),
                SelectFilter::make('day_of_week')->label('Hari')->options(KajianSeries::DAYS),
            ])
            ->recordActions([EditAction::make()]);
    }
}
