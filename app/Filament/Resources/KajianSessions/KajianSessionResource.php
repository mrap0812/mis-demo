<?php

namespace App\Filament\Resources\KajianSessions;

use App\Filament\Resources\KajianSessions\Pages\CreateKajianSession;
use App\Filament\Resources\KajianSessions\Pages\EditKajianSession;
use App\Filament\Resources\KajianSessions\Pages\ListKajianSessions;
use App\Filament\Resources\KajianSessions\Schemas\KajianSessionForm;
use App\Filament\Resources\KajianSessions\Tables\KajianSessionsTable;
use App\Models\KajianSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KajianSessionResource extends Resource
{
    protected static ?string $model = KajianSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Kajian';

    protected static ?string $modelLabel = 'pertemuan';

    protected static ?string $pluralModelLabel = 'Pertemuan & pengumuman';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return KajianSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KajianSessionsTable::configure($table);
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
            'index' => ListKajianSessions::route('/'),
            'create' => CreateKajianSession::route('/create'),
            'edit' => EditKajianSession::route('/{record}/edit'),
        ];
    }
}
