<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Models\Program;
use App\Support\AccountNumberGuard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        $guard = AccountNumberGuard::rule();

        return $schema->columns(3)->components([
            Section::make('Program')->columnSpan(2)->schema([
                TextInput::make('title')->label('Nama program')->required()->maxLength(120),
                TextInput::make('tagline')->label('Slogan singkat')->maxLength(160)->rule($guard)
                    ->placeholder('Contoh: Meraih banyak faedah dari pengelolaan sampah'),
                Textarea::make('summary')->label('Penjelasan singkat ("Apa itu?")')->rows(3)->required()->maxLength(300)->rule($guard),
                RichEditor::make('body')->label('Keterangan tambahan (opsional)')->rule($guard)
                    ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
            ]),
            Section::make('Waktu & kontak')->columnSpan(1)->schema([
                Select::make('category')->label('Kategori')->options(Program::CATEGORIES)->default('sosial')->required(),
                TextInput::make('schedule')->label('Jadwal')->maxLength(120)->placeholder("Setiap Ahad ba'da Maghrib"),
                \Filament\Forms\Components\CheckboxList::make('days')->label('Hari pelaksanaan')->options(Program::DAYS)->columns(2)
                    ->helperText('Untuk penanda "Hari ini" dan "Besok" di situs.'),
                TextInput::make('location')->label('Tempat')->maxLength(160),
                TextInput::make('audience')->label('Untuk siapa')->maxLength(160)->placeholder('Contoh: Umum, Muslim laki-laki'),
                TextInput::make('organizer')->label('Penyelenggara')->maxLength(160)
                    ->helperText("Isi bila diselenggarakan bersama lembaga lain."),
                TextInput::make('organizer_url')->label('Tautan penyelenggara')->rules(['nullable', 'url', 'starts_with:https://']),
                Toggle::make('is_free')->label('Gratis')->default(true),
                TextInput::make('contact_label')->label('Nama kontak')->maxLength(60),
                TextInput::make('contact_number')->label('Nomor WhatsApp')->regex('/^\+?[0-9][0-9\s\-]{7,18}$/'),
                TextInput::make('registration_url')->label('Tautan pendaftaran')->rules(['nullable', 'url', 'starts_with:https://']),
                TextInput::make('slug')->label('Alamat')->prefix('/program/')->alphaDash()->maxLength(80)->unique(ignoreRecord: true)
                    ->helperText('Kosongkan untuk dibuat otomatis dari nama.'),
                Toggle::make('is_published')->label('Tampil di situs')->default(false)
                    ->helperText('Tayangkan hanya setelah isinya dicocokkan dengan poster atau keputusan pengurus.'),
            ]),
            Section::make('Rincian')->columnSpan(2)->collapsible()->schema([
                TextInput::make('items_title')->label('Judul daftar kartu')->maxLength(80)->placeholder('Contoh: Kriteria sampah yang diterima / Pilihan kelas'),
                Repeater::make('items')->label('Kartu')->schema([
                    TextInput::make('title')->label('Judul')->required()->maxLength(60),
                    Textarea::make('text')->label('Keterangan')->rows(2)->maxLength(200)->rule($guard),
                ])->columns(2)->reorderable()->collapsible()->defaultItems(0)->addActionLabel('Tambah kartu'),
                TextInput::make('notes_title')->label('Judul daftar ketentuan')->maxLength(80)->placeholder('Contoh: Syarat & ketentuan / Penting diperhatikan'),
                Repeater::make('notes')->label('Ketentuan')->simple(TextInput::make('value')->required()->maxLength(200)->rule($guard))
                    ->reorderable()->defaultItems(0)->addActionLabel('Tambah ketentuan'),
                Repeater::make('steps')->label('Alur kegiatan (berurutan)')->simple(TextInput::make('value')->required()->maxLength(120))
                    ->reorderable()->defaultItems(0)->addActionLabel('Tambah langkah'),
                Textarea::make('closing')->label('Kalimat penutup / pengingat niat')->rows(2)->maxLength(400)->rule($guard),
            ]),
            Section::make('Poster')->columnSpan(1)->schema([
                FileUpload::make('poster')->label('Poster resmi')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('programs')->maxSize(4096),
                Textarea::make('poster_alt')->label('Deskripsi poster (untuk pembaca layar)')->rows(3)->maxLength(300),
                TextInput::make('source')->label('Sumber informasi (catatan)')->maxLength(200)
                    ->helperText('Misalnya: Poster resmi, diterima 13 Okt 2026.'),
            ]),
        ]);
    }
}
