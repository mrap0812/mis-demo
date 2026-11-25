<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Isi tulisan')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')->label('Judul')->required()->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, ?Post $record) => $record ? null : $set('slug', Str::slug($state))),
                        TextInput::make('slug')->label('Alamat (slug)')->helperText('Dipakai di URL. Terisi otomatis dari judul.')
                            ->unique(ignoreRecord: true)->maxLength(200),
                        Textarea::make('excerpt')->label('Ringkasan')->rows(3)->maxLength(400)
                            ->helperText('Tampil di kartu berita. Maksimal sekitar dua kalimat.'),
                        RichEditor::make('body')->label('Isi')->columnSpanFull()
                            ->helperText('Untuk konten agama: cantumkan rujukan ayat atau hadits beserta sumbernya.'),
                    ]),
                Section::make('Pengaturan')
                    ->columnSpan(1)
                    ->schema([
                        Text::make(fn (?Post $record) => 'Status: '.($record?->status?->label() ?? PostStatus::Draf->label())),
                        Text::make(fn (?Post $record) => $record?->review_note ? 'Catatan peninjau: '.$record->review_note : '')
                            ->visible(fn (?Post $record) => filled($record?->review_note)),
                        Select::make('type')->label('Jenis')->options(Post::TYPES)->required()->default('berita'),
                        TextInput::make('category')->label('Kategori')->placeholder('Sosial, Kegiatan, Kajian ...')
                            ->datalist(['Sosial', 'Kegiatan', 'Kajian', 'Kurban', 'Pengumuman']),
                        FileUpload::make('cover')->label('Foto sampul')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('posts')->maxSize(4096)
                            ->imageEditor()->maxSize(4096)
                            ->helperText('Pastikan ada izin pakai foto. Hindari wajah anak tanpa persetujuan wali.'),
                        DateTimePicker::make('published_at')->label('Jadwal terbit')->seconds(false)
                            ->helperText('Kosongkan untuk terbit saat disetujui.'),
                        TextInput::make('source_url')->label('Tautan sumber')->url(),
                    ]),
            ]);
    }
}
