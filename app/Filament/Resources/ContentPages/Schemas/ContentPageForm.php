<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use App\Models\ContentPage;
use App\Support\AccountNumberGuard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Isi halaman')->columnSpan(2)->schema([
                TextInput::make('title')->label('Judul')->required()->maxLength(120),
                Textarea::make('lede')->label('Pengantar singkat')->rows(2)->maxLength(300)->rule(AccountNumberGuard::rule())
                    ->helperText('Tampil di bawah judul dan sebagai deskripsi di hasil pencarian.'),
                RichEditor::make('body')->label('Isi')->rule(AccountNumberGuard::rule())
                    ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']])
                    ->helperText(fn (?ContentPage $record) => $record?->slug === 'profil'
                        ? 'Belum diterima dari pengurus: sejarah lengkap, visi misi, fasilitas, struktur pengurus. Jangan diisi perkiraan.'
                        : null),
            ]),
            Section::make('Pengaturan')->columnSpan(1)->schema([
                TextInput::make('slug')->label('Alamat halaman')->prefix('/halaman/')->maxLength(80)
                    ->alphaDash()->unique(ignoreRecord: true)
                    ->disabled(fn (?ContentPage $record) => $record?->isReserved())
                    ->helperText(fn (?ContentPage $record) => $record?->isReserved() ? 'Halaman ini memakai alamat khusus /'.$record->slug.'.' : 'Kosongkan untuk dibuat otomatis dari judul.'),
                FileUpload::make('cover')->label('Foto sampul')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('pages')->maxSize(4096)
                    ->helperText('Hindari foto yang menampilkan wajah jamaah tanpa izin.'),
                Toggle::make('is_published')->label('Tampil di situs')->default(true),
            ]),
        ]);
    }
}
