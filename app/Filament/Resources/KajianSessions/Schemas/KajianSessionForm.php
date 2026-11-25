<?php

namespace App\Filament\Resources\KajianSessions\Schemas;

use App\Models\KajianSession;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class KajianSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Pertemuan')->columnSpan(1)->schema([
                Select::make('kajian_series_id')->label('Seri kajian')->relationship('series', 'title')->searchable()->preload()->required(),
                DatePicker::make('date')->label('Tanggal')->required()->native(false)->displayFormat('d M Y')
                    ->rule(fn (Get $get, ?KajianSession $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                        $exists = KajianSession::query()->where('kajian_series_id', $get('kajian_series_id'))->whereDate('date', $value)
                            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))->exists();
                        if ($exists) {
                            $fail('Data pertemuan seri ini pada tanggal tersebut sudah ada. Ubah data yang sudah ada.');
                        }
                    }),
                TextInput::make('title')->label('Judul / tema pertemuan')->helperText('Kosongkan bila sama dengan judul seri.'),
                Select::make('speaker_id')->label('Pemateri pengganti / khatib')->relationship('speaker', 'name')->searchable()->preload()
                    ->helperText('Kosongkan bila pemateri sama dengan jadwal rutin.'),
                Select::make('status')->label('Status')->options(KajianSession::STATUSES)->default('terjadwal')->required()->live(),
                TextInput::make('status_reason')->label('Alasan / pengumuman')
                    ->visible(fn (Get $get) => $get('status') !== 'terjadwal')
                    ->required(fn (Get $get) => $get('status') !== 'terjadwal'),
            ]),
            Section::make('Materi & sumber')->columnSpan(1)->schema([
                FileUpload::make('poster')->label('Poster')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('posters')->maxSize(4096),
                TextInput::make('recording_url')->label('Tautan rekaman YouTube')->url(),
                Textarea::make('notes')->label('Catatan')->rows(3),
                Select::make('source')->label('Sumber')->options(['poster' => 'Poster resmi', 'admin' => 'Input admin'])->default('admin'),
            ]),
        ]);
    }
}
