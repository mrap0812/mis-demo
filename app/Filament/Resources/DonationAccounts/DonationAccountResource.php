<?php

namespace App\Filament\Resources\DonationAccounts;

use App\Filament\Resources\DonationAccountChanges\DonationAccountChangeResource;
use App\Filament\Resources\DonationAccounts\Pages\ListDonationAccounts;
use App\Models\DonationAccount;
use App\Models\DonationAccountChange;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DonationAccountResource extends Resource
{
    protected static ?string $model = DonationAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Infak';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'rekening/QRIS';

    protected static ?string $pluralModelLabel = 'Rekening & QRIS';

    protected static ?string $slug = 'rekening-infak';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->description('Data ini tampil di halaman Infak. Perubahan apa pun harus diajukan lalu disetujui orang kedua (admin atau bendahara lain).')
            ->columns([
                TextColumn::make('purpose')->label('Peruntukan')->weight('bold')->searchable()
                    ->description(fn (DonationAccount $r) => $r->pendingChange() ? 'Ada pengajuan yang menunggu' : null),
                TextColumn::make('kind')->label('Jenis')->badge()->formatStateUsing(fn ($state) => DonationAccount::KINDS[$state] ?? $state),
                TextColumn::make('bank_name')->label('Bank')->placeholder('-'),
                TextColumn::make('account_number')->label('Nomor')->placeholder('-')->copyable(),
                TextColumn::make('account_holder')->label('Atas nama'),
                ImageColumn::make('qris_image')->label('QRIS')->disk('public')->height(48),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
                TextColumn::make('approved_at')->label('Disetujui')->since()
                    ->description(fn (DonationAccount $r) => $r->approver?->name),
            ])
            ->recordActions([
                Action::make('proposeUpdate')->label('Ajukan perubahan')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                    ->visible(fn (DonationAccount $r) => $r->is_active && ! $r->pendingChange())
                    ->fillForm(fn (DonationAccount $r) => $r->only(DonationAccount::FIELDS))
                    ->schema(DonationAccountForm::fields(false))
                    ->modalDescription('Data publik tidak berubah sampai pengajuan ini disetujui orang kedua.')
                    ->action(fn (DonationAccount $r, array $data) => self::submit('update', $r, $data)),
                Action::make('proposeRetire')->label('Ajukan nonaktif')->icon(Heroicon::OutlinedEyeSlash)->color('danger')
                    ->visible(fn (DonationAccount $r) => $r->is_active && ! $r->pendingChange())
                    ->schema([Textarea::make('source')->label('Alasan')->required()->rows(2)])
                    ->modalDescription('Rekening/QRIS ini akan disembunyikan dari situs setelah disetujui orang kedua.')
                    ->action(fn (DonationAccount $r, array $data) => self::submit('retire', $r, $data)),
            ])
            ->headerActions([
                Action::make('proposeCreate')->label('Ajukan rekening/QRIS baru')->icon(Heroicon::OutlinedPlus)
                    ->schema(DonationAccountForm::fields(true))
                    ->modalDescription('Pengajuan baru tidak tampil di situs sampai disetujui orang kedua.')
                    ->action(fn (array $data) => self::submit('create', null, $data)),
            ]);
    }

    public static function submit(string $action, ?DonationAccount $account, array $data): void
    {
        $source = $data['source'] ?? null;
        unset($data['source']);
        try {
            DonationAccountChange::propose(auth()->user(), $action, $account, $data, $source);
        } catch (DomainException $e) {
            Notification::make()->danger()->title('Pengajuan tidak dibuat')->body($e->getMessage())->send();

            return;
        }
        Notification::make()->success()->title('Pengajuan dibuat')
            ->body('Minta admin atau bendahara lain memeriksa dan menyetujuinya di menu Persetujuan infak.')
            ->actions([Action::make('lihat')->label('Buka persetujuan')->url(DonationAccountChangeResource::getUrl('index'))])
            ->send();
    }

    public static function getPages(): array
    {
        return ['index' => ListDonationAccounts::route('/')];
    }
}
