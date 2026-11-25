<?php

namespace App\Filament\Resources\DonationAccounts;

use App\Models\DonationAccount;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/** Kolom formulir pengajuan rekening/QRIS (dipakai untuk tambah dan ubah). */
class DonationAccountForm
{
    public static function fields(bool $creating): array
    {
        $isRek = fn (Get $get) => $get('kind') === 'rekening';
        $isQris = fn (Get $get) => $get('kind') === 'qris';

        return [
            Select::make('kind')->label('Jenis')->options(DonationAccount::KINDS)->required()->live()->disabled(! $creating)->dehydrated($creating),
            TextInput::make('purpose')->label('Peruntukan')->required()->maxLength(120)->placeholder('Contoh: Infak operasional masjid'),
            TextInput::make('bank_name')->label('Bank')->maxLength(80)->visible($isRek)->required($isRek)->placeholder('Contoh: BSI (Bank Syariah Indonesia)'),
            TextInput::make('account_number')->label('Nomor rekening')->visible($isRek)->required($isRek)
                ->regex('/^[0-9][0-9\s\-.]{4,38}$/')->helperText('Salin dari buku rekening atau surat bank, bukan dari pesan berantai.'),
            TextInput::make('account_holder')->label('Atas nama')->required()->maxLength(120),
            FileUpload::make('qris_image')->label('Gambar QRIS')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('qris')->maxSize(2048)
                ->visible($isQris)->required(fn (Get $get) => $creating && $get('kind') === 'qris')
                ->helperText('Unggah file QRIS asli dari bank, bukan tangkapan layar yang sudah diedit.'),
            TextInput::make('qris_merchant')->label('Nama merchant / NMID')->maxLength(120)->visible($isQris)->required($isQris),
            Textarea::make('notes')->label('Catatan untuk jamaah')->rows(2)->maxLength(300)->placeholder('Contoh: Tambahkan angka 1 di akhir nominal untuk infak operasional.'),
            TextInput::make('sort')->label('Urutan tampil')->numeric()->minValue(0)->maxValue(999)->default(0),
            TextInput::make('source')->label('Dasar perubahan')->required()->maxLength(200)
                ->helperText('Contoh: "Surat bendahara tgl 12/10/2026" atau "Buku rekening BSI". Akan dibaca penyetuju.'),
        ];
    }
}
