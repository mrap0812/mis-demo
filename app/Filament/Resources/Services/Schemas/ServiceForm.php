<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
use App\Support\AccountNumberGuard;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Layanan')->columnSpan(2)->schema([
                TextInput::make('name')->label('Nama layanan')->required()->maxLength(80),
                Textarea::make('summary')->label('Ringkasan')->rows(2)->required()->maxLength(240)->rule(AccountNumberGuard::rule()),
                RichEditor::make('details')->label('Syarat & alur')->rule(AccountNumberGuard::rule())
                    ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->helperText('Tulis hanya yang sudah ditetapkan pengurus. Bila belum, biarkan kosong dan pilih status "menunggu".'),
                TextInput::make('schedule')->label('Jam / jadwal layanan')->maxLength(120)->placeholder('Contoh: Setiap hari, 24 jam'),
            ]),
            Section::make('Kontak & tampilan')->columnSpan(1)->schema([
                Select::make('status')->label('Status')->options(Service::STATUSES)->default('menunggu')->required(),
                Select::make('icon')->label('Ikon')->options(Service::ICONS)->default('heart')->required(),
                TextInput::make('contact_label')->label('Nama kontak')->maxLength(60)->placeholder('Contoh: Sekretariat'),
                TextInput::make('contact_number')->label('Nomor WhatsApp')->regex('/^\+?[0-9][0-9\s\-]{7,18}$/')
                    ->helperText('Kosongkan untuk mengarahkan jamaah ke halaman Kontak.'),
                TextInput::make('registration_url')->label('Tautan pendaftaran')->rules(['nullable', 'url', 'starts_with:https://']),
                Toggle::make('is_active')->label('Tampil di situs')->default(true),
            ]),
        ]);
    }
}
