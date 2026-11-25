<?php

namespace App\Filament\Resources\KajianSeries;

use App\Filament\Resources\KajianSeries\Pages\CreateKajianSeries;
use App\Filament\Resources\KajianSeries\Pages\EditKajianSeries;
use App\Filament\Resources\KajianSeries\Pages\ListKajianSeries;
use App\Filament\Resources\KajianSeries\Schemas\KajianSeriesForm;
use App\Filament\Resources\KajianSeries\Tables\KajianSeriesTable;
use App\Models\KajianSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KajianSeriesResource extends Resource
{
    protected static ?string $model = KajianSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Kajian';

    protected static ?string $modelLabel = 'seri kajian';

    protected static ?string $pluralModelLabel = 'Jadwal kajian';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return KajianSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KajianSeriesTable::configure($table);
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
            'index' => ListKajianSeries::route('/'),
            'create' => CreateKajianSeries::route('/create'),
            'edit' => EditKajianSeries::route('/{record}/edit'),
        ];
    }
}
