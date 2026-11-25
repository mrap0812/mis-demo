<?php

namespace App\Filament\Resources\Campaigns;

use App\Models\Campaign;
use App\Models\DonationAccount;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Infak';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'kampanye donasi';

    protected static ?string $pluralModelLabel = 'Kampanye donasi';

    protected static ?string $slug = 'kampanye';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Kampanye')->columnSpan(2)->schema([
                TextInput::make('title')->label('Nama kampanye')->required()->maxLength(120),
                Select::make('kind')->label('Jenis')->options(Campaign::KINDS)->default('target')->required()->live(),
                Textarea::make('summary')->label('Keterangan singkat')->rows(3)->maxLength(400),
                TextInput::make('target_amount')->label('Target dana (Rp)')->numeric()->integer()->minValue(1)
                    ->visible(fn (Get $get) => $get('kind') === 'target')->helperText('Kosongkan bila poster tidak memuat angka; progres tidak ditampilkan.'),
                TextInput::make('collected_amount')->label('Dana terkumpul (Rp)')->numeric()->integer()->minValue(0)->default(0)
                    ->visible(fn (Get $get) => $get('kind') === 'target'),
                DatePicker::make('data_date')->label('Angka per tanggal')->native(false)
                    ->visible(fn (Get $get) => $get('kind') === 'target')->helperText('Perbarui setiap kali angka terkumpul diubah.'),
                TextInput::make('hijri_year')->label('Tahun Hijriah Ramadhan')->numeric()->integer()->minValue(1440)->maxValue(1500)
                    ->visible(fn (Get $get) => $get('kind') === 'ramadhan')->required(fn (Get $get) => $get('kind') === 'ramadhan')
                    ->helperText('Hitung mundur dihitung otomatis; kampanye hilang sendiri setelah 1 Syawal.'),
                TextInput::make('quote')->label('Kutipan ayat/hadits (opsional)')->maxLength(300),
                TextInput::make('quote_ref')->label('Sumber kutipan')->maxLength(80),
            ]),
            Section::make('Rekening & tampilan')->columnSpan(1)->schema([
                Select::make('donation_account_id')->label('Rekening tujuan')
                    ->options(fn () => DonationAccount::query()->live()->get()->mapWithKeys(fn ($a) => [$a->id => $a->purpose.' · '.$a->account_number])->all())
                    ->helperText('Hanya rekening yang sudah disetujui. Rekening baru diajukan lewat menu Pengajuan rekening.'),
                TextInput::make('poster_bank')->label('Bank di poster')->maxLength(60),
                TextInput::make('poster_account_number')->label('Nomor rekening di poster')->maxLength(40)
                    ->helperText('Salin persis dari poster resmi. Dipakai hanya bila rekening tujuan di atas kosong.'),
                TextInput::make('poster_account_holder')->label('Atas nama (poster)')->maxLength(100),
                FileUpload::make('poster')->label('Poster resmi')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')->directory('kampanye')->maxSize(4096),
                TextInput::make('poster_alt')->label('Deskripsi poster (untuk tunanetra)')->maxLength(300),
                TextInput::make('source')->label('Sumber data')->maxLength(160),
                Toggle::make('is_active')->label('Tampil di situs')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('title')->label('Kampanye')->weight('bold')->description(fn (Campaign $c) => Campaign::KINDS[$c->kind] ?? $c->kind),
                TextColumn::make('progres')->label('Progres')->state(fn (Campaign $c) => $c->kind === 'target' && ! $c->target_amount ? 'Tanpa angka' : ($c->kind === 'target'
                    ? Campaign::rupiah($c->collected_amount).' dari '.Campaign::rupiah((int) $c->target_amount).' ('.number_format($c->percent(), 2, ',', '.').'%)'
                    : $c->daysToRamadhan(now('Asia/Makassar')->toImmutable()).' hari lagi')),
                TextColumn::make('account.account_number')->label('Rekening')->placeholder('Belum dipilih'),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
