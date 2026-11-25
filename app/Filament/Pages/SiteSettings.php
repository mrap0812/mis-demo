<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AccountNumberGuard;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Pengaturan situs. Hanya admin: nomor kontak dan tautan adalah sasaran utama pemalsuan,
 * jadi setiap perubahan juga tercatat di Log aktivitas.
 */
class SiteSettings extends Page
{
    protected string $view = 'filament.pages.site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Situs';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Pengaturan situs';

    protected static ?string $navigationLabel = 'Pengaturan situs';

    protected static ?string $slug = 'pengaturan-situs';

    public ?array $data = [];

    private const PHONE = '/^\+?[0-9][0-9\s\-]{7,18}$/';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $this->form->fill(Setting::site());
    }

    public function form(Schema $schema): Schema
    {
        $https = ['url', 'starts_with:https://'];

        return $schema->statePath('data')->components([
            Tabs::make('pengaturan')->persistTabInQueryString()->tabs([
                Tab::make('Beranda')->icon(Heroicon::OutlinedHome)->schema([
                    TextInput::make('util_text')->label('Teks baris paling atas')->maxLength(90)->required(),
                    TextInput::make('hero_kicker')->label('Teks kecil di atas judul')->maxLength(80)->required(),
                    TextInput::make('hero_title')->label('Judul utama beranda')->maxLength(80)->required(),
                    Textarea::make('hero_sub')->label('Kalimat pengantar')->rows(3)->maxLength(260)->required(),
                    Repeater::make('hero_trust')->label('Keterangan singkat di bawah tombol (desktop)')->simple(TextInput::make('value')->required()->maxLength(50))
                        ->maxItems(4)->reorderable()->addActionLabel('Tambah keterangan')
                        ->helperText('Jumlah majelis pekan ini ditambahkan otomatis. Tulis hanya yang benar adanya.'),
                    Section::make('Ayat pilihan (blok gelap di bawah beranda)')->schema([
                        Textarea::make('verse_ar')->label('Teks Arab')->rows(2)->maxLength(400)->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
                        Textarea::make('verse_id')->label('Terjemah')->rows(2)->maxLength(400)->required(),
                        TextInput::make('verse_ref')->label('Rujukan')->maxLength(80)->placeholder('QS. At-Taubah: 18')->required(),
                    ]),
                ]),
                Tab::make('Kajian')->icon(Heroicon::OutlinedBookOpen)->schema([
                    Section::make('Akun pengumuman kajian')->description('Dipakai di kalimat seperti "Khatib diumumkan di ...".')->columns(2)->schema([
                        TextInput::make('info_handle')->label('Nama akun')->required()->maxLength(40),
                        TextInput::make('info_url')->label('Alamat')->rules($https)->required(),
                    ]),
                    Section::make('Poster jadwal kajian')->description('Tampil di halaman Kajian. Ganti setiap ada poster jadwal baru.')->schema([
                        \Filament\Forms\Components\FileUpload::make('kajian_poster')->label('Poster')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('posters')->maxSize(4096)
                            ->helperText('Kosongkan untuk memakai poster bawaan (update Shafar 1448 H).'),
                        Textarea::make('kajian_poster_alt')->label('Deskripsi poster untuk pembaca layar')->rows(2)->maxLength(300)->required(),
                    ]),
                    Section::make('Hadits pilihan (halaman Kajian)')->schema([
                        Textarea::make('hadith_ar')->label('Teks Arab')->rows(2)->maxLength(400)->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
                        Textarea::make('hadith_id')->label('Terjemah')->rows(2)->maxLength(400)
                            ->helperText('Kosongkan untuk menyembunyikan kotak hadits.'),
                        TextInput::make('hadith_ref')->label('Rujukan')->maxLength(80)->placeholder('HR. Muslim no. 2699'),
                    ]),
                ]),
                Tab::make('Pengumuman')->icon(Heroicon::OutlinedMegaphone)->schema([
                    Toggle::make('notice_active')->label('Tampilkan pita pengumuman di semua halaman')->live(),
                    Textarea::make('notice_text')->label('Isi pengumuman')->rows(2)->maxLength(220)->rule(AccountNumberGuard::rule())
                        ->helperText('Singkat dan jelas, misalnya: "Kajian ba\'da Maghrib Kamis ini diliburkan."')
                        ->required(fn (Get $get) => (bool) $get('notice_active')),
                    TextInput::make('notice_url')->label('Tautan "Selengkapnya" (opsional)')->rules(['nullable', 'url'])
                        ->helperText('Boleh tautan berita di situs ini atau postingan Instagram resmi.'),
                    Select::make('notice_tone')->label('Warna')->options(['gold' => 'Emas (umum)', 'info' => 'Biru (informasi)', 'alert' => 'Merah (penting/perubahan jadwal)'])->required(),
                    DatePicker::make('notice_until')->label('Tampil sampai tanggal')->native(false)->displayFormat('d M Y')
                        ->helperText('Setelah tanggal ini pita hilang sendiri. Kosongkan bila tidak ada batas.'),
                ]),
                Tab::make('Kontak & lokasi')->icon(Heroicon::OutlinedPhone)->schema([
                    Section::make('Nomor kontak')->description('Tampil di halaman Kontak dan Layanan. Centang "Footer" untuk nomor yang juga tampil di bagian bawah setiap halaman.')->schema([
                        Repeater::make('contacts')->hiddenLabel()->addActionLabel('Tambah nomor')->reorderable()->columns(3)->minItems(1)->maxItems(12)
                            ->schema([
                                TextInput::make('label')->label('Unit / keperluan')->required()->maxLength(60),
                                TextInput::make('number')->label('Nomor WhatsApp')->required()->regex(self::PHONE)->placeholder('0811-500-822'),
                                Toggle::make('footer')->label('Footer')->inline(false),
                            ]),
                    ]),
                    Section::make('Tombol WhatsApp melayang')->columns(2)->schema([
                        TextInput::make('fab_label')->label('Label')->required()->maxLength(40),
                        TextInput::make('fab_number')->label('Nomor')->required()->regex(self::PHONE),
                    ]),
                    Section::make('Alamat & peta')->columns(2)->schema([
                        Textarea::make('address')->label('Alamat')->rows(2)->required()->columnSpanFull(),
                        TextInput::make('postal_code')->label('Kode pos')->maxLength(5)->rules(['nullable', 'digits:5'])
                            ->helperText('Belum dibakukan: 70235 di mis.id, 70248 di Instagram. Isi setelah ada keputusan pengurus.'),
                        TextInput::make('latitude')->label('Lintang')->numeric()->required()->minValue(-90)->maxValue(90)
                            ->helperText('Untuk tautan dan peta. Jadwal shalat memakai koordinat tetap yang sudah diverifikasi.'),
                        TextInput::make('longitude')->label('Bujur')->numeric()->required()->minValue(-180)->maxValue(180),
                    ]),
                ]),
                Tab::make('Media & tautan')->icon(Heroicon::OutlinedLink)->schema([
                    TextInput::make('live_url')->label('Tautan siaran langsung')->rules($https)->required(),
                    TextInput::make('playlist_url')->label('Tautan playlist rekaman')->rules($https)->required(),
                    TextInput::make('wa_group_url')->label('Tautan pendaftaran grup info kajian')->rules($https)->required(),
                    Repeater::make('socials')->label('Akun media sosial resmi')->addActionLabel('Tambah akun')->reorderable()->columns(4)->maxItems(12)
                        ->schema([
                            Select::make('platform')->options(['instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'link' => 'Tautan lain'])->required(),
                            TextInput::make('handle')->label('Nama akun')->required()->maxLength(60),
                            TextInput::make('url')->label('Alamat')->rules($https)->required(),
                            TextInput::make('note')->label('Keterangan')->maxLength(60),
                        ]),
                ]),
                Tab::make('Footer')->icon(Heroicon::OutlinedBars3BottomLeft)->schema([
                    Textarea::make('footer_about')->label('Keterangan singkat masjid')->rows(2)->maxLength(200)->required(),
                ]),
            ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach (['contacts', 'socials'] as $list) {
            $data[$list] = array_values(array_map(fn ($row) => array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row), $data[$list] ?? []));
        }
        $data['contacts'] = array_map(fn ($c) => ['label' => $c['label'], 'number' => $c['number'], 'footer' => (bool) ($c['footer'] ?? false)], $data['contacts']);
        $data['socials'] = array_map(fn ($s) => ['platform' => $s['platform'], 'handle' => $s['handle'], 'url' => $s['url'], 'note' => $s['note'] ?? null], $data['socials']);
        $data['hero_trust'] = array_values(array_filter(array_map('trim', $data['hero_trust'] ?? []), 'strlen'));
        $data['kajian_poster'] = blank($data['kajian_poster'] ?? null) ? null : (is_array($data['kajian_poster']) ? (array_values($data['kajian_poster'])[0] ?? null) : $data['kajian_poster']);
        \App\Support\Img::make($data['kajian_poster']);
        $data['notice_active'] = (bool) ($data['notice_active'] ?? false);
        $data['latitude'] = (float) $data['latitude'];
        $data['longitude'] = (float) $data['longitude'];
        foreach (['notice_url', 'postal_code', 'notice_until'] as $k) {
            $data[$k] = blank($data[$k] ?? null) ? ($k === 'notice_url' ? '' : null) : $data[$k];
        }

        Setting::put($data);

        Notification::make()->success()->title('Pengaturan tersimpan')->body('Perubahan langsung tampil di situs dan tercatat di Log aktivitas.')->send();
    }
}
