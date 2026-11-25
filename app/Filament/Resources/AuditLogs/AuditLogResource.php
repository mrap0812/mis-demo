<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna';

    protected static ?string $modelLabel = 'catatan';

    protected static ?string $pluralModelLabel = 'Log aktivitas';

    protected static ?int $navigationSort = 9;

    public static function table(Table $table): Table
    {
        $labels = ['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus', 'restored' => 'Dipulihkan'];

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Oleh')->placeholder('Sistem / seeder'),
                TextColumn::make('action')->label('Aksi')->badge()
                    ->formatStateUsing(fn ($state) => $labels[$state] ?? $state)
                    ->color(fn ($state) => ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger', 'restored' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('auditable_type')->label('Data')->description(fn (AuditLog $r) => '#'.$r->auditable_id),
                TextColumn::make('changes')->label('Perubahan')->wrap()->limit(160)
                    ->formatStateUsing(fn ($state, AuditLog $r) => collect($r->changes ?? [])
                        ->map(fn ($pair, $k) => $k.': '.json_encode($pair[0] ?? null, JSON_UNESCAPED_UNICODE).' → '.json_encode($pair[1] ?? null, JSON_UNESCAPED_UNICODE))
                        ->implode(' · ')),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')->label('Aksi')->options($labels),
                SelectFilter::make('auditable_type')->label('Data')->options(fn () => AuditLog::query()->distinct()->pluck('auditable_type', 'auditable_type')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
