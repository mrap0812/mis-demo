<?php

namespace App\Filament\Resources\KajianSeries\Schemas;

use App\Models\KajianSeries;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KajianSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        $weeks = [1 => 'Pekan 1', 2 => 'Pekan 2', 3 => 'Pekan 3', 4 => 'Pekan 4', 5 => 'Pekan 5'];

        return $schema->columns(2)->components([
            Section::make('Kajian')->columnSpan(1)->schema([
                TextInput::make('title')->label('Judul / tema seri')->required(),
                TextInput::make('kitab')->label('Kitab yang dikaji'),
                TextInput::make('kitab_ar')->label('Judul kitab (huruf Arab)')->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
                Select::make('speaker_id')->label('Pemateri')->relationship('speaker', 'name')->searchable()->preload()
                    ->createOptionForm([TextInput::make('name')->label('Nama lengkap dengan gelar')->required()]),
                Select::make('category')->label('Jenis')->options(KajianSeries::CATEGORIES)->required()->default('kajian'),
                TextInput::make('playlist_url')->label('Playlist YouTube')->url(),
            ]),
            Section::make('Jadwal')->columnSpan(1)->schema([
                Select::make('day_of_week')->label('Hari')->options(KajianSeries::DAYS)->required(),
                Select::make('slot')->label('Waktu')->options(KajianSeries::SLOTS)->required()
                    ->helperText('Jam dihitung otomatis dari jadwal shalat tanggal tersebut.'),
                CheckboxList::make('weeks')->label('Hanya pada pekan ke- (kosongkan bila setiap pekan)')->options($weeks)->columns(5),
                CheckboxList::make('except_weeks')->label('Libur pada pekan ke- (mis. diganti kajian berkala)')->options($weeks)->columns(5),
                TextInput::make('badge')->label('Label tambahan')->placeholder('Pekan ke-4, Khusus Muslimah'),
                Select::make('color')->label('Warna kartu')->options(KajianSeries::TONES)->default(array_key_first(KajianSeries::TONES))->required()
                    ->helperText('Mengikuti palet situs: merah, merah tua, atau emas.'),
                Toggle::make('is_tentative')->label('Slot tentatif (belum ada kajian tetap)'),
                Toggle::make('is_active')->label('Aktif')->default(true),
                TextInput::make('code')->label('Kode URL')->required()->unique(ignoreRecord: true)
                    ->helperText('Huruf kecil dan tanda hubung, mis. "fikih-ibadah". Jangan diubah setelah dipakai.'),
                TextInput::make('source')->label('Sumber data')->placeholder('Poster jadwal ... '),
                TextInput::make('sort')->label('Urutan')->numeric()->default(0),
            ]),
        ]);
    }
}
