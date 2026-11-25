<?php

namespace App\Filament\Resources\DonationAccountChanges;

use App\Filament\Resources\DonationAccountChanges\Pages\ListDonationAccountChanges;
use App\Models\DonationAccount;
use App\Models\DonationAccountChange;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DonationAccountChangeResource extends Resource
{
    protected static ?string $model = DonationAccountChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Infak';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'pengajuan';

    protected static ?string $pluralModelLabel = 'Persetujuan infak';

    protected static ?string $slug = 'persetujuan-infak';

    public static function getNavigationBadge(): ?string
    {
        $n = DonationAccountChange::where('status', 'menunggu')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['account', 'proposer', 'reviewer'])->orderByRaw("status = 'menunggu' desc"))
            ->columns([
                TextColumn::make('created_at')->label('Diajukan')->since()->description(fn (DonationAccountChange $r) => $r->proposer?->name ?? 'Data awal sistem'),
                TextColumn::make('action')->label('Jenis')->badge()->formatStateUsing(fn ($state) => DonationAccountChange::ACTIONS[$state] ?? $state)
                    ->color(fn ($state) => ['create' => 'info', 'update' => 'warning', 'retire' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('purpose')->label('Peruntukan')->state(fn (DonationAccountChange $r) => $r->purposeLabel())
                    ->description(fn (DonationAccountChange $r) => $r->action === 'update'
                        ? 'Mengubah: '.collect(array_keys($r->payload ?? []))->map(fn ($k) => mb_strtolower(DonationAccount::LABELS[$k] ?? $k))->implode(', ')
                        : null),
                TextColumn::make('source')->label('Dasar')->limit(40)->tooltip(fn (DonationAccountChange $r) => $r->source),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => DonationAccountChange::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => ['menunggu' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger'][$state] ?? 'gray')
                    ->description(fn (DonationAccountChange $r) => $r->reviewer ? 'oleh '.$r->reviewer->name.($r->review_note ? ': '.$r->review_note : '') : null),
            ])
            ->filters([SelectFilter::make('status')->options(DonationAccountChange::STATUSES)])
            ->recordActions([
                Action::make('approve')->label('Periksa & setujui')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->visible(fn (DonationAccountChange $r) => auth()->user()->can('approve', $r))
                    ->modalHeading(fn (DonationAccountChange $r) => 'Setujui: '.(DonationAccountChange::ACTIONS[$r->action] ?? '').' '.$r->purposeLabel())
                    ->modalSubmitActionLabel('Setujui & tayangkan')
                    ->schema(fn (DonationAccountChange $r) => [
                        Text::make($r->describe()),
                        TextInput::make('confirmation')
                            ->label($r->action === 'retire' ? 'Ketik NONAKTIFKAN' : 'Ketik ulang nomor rekening dari sumber aslinya')
                            ->helperText($r->action === 'retire' ? null : 'Jangan salin dari tabel di atas. Cocokkan dengan buku rekening atau surat bank.')
                            ->visible($r->action === 'retire' || ($r->resulting()['kind'] ?? null) === 'rekening')
                            ->required()->autocomplete(false),
                        Checkbox::make('qris_checked')->label('Saya sudah memindai QRIS ini dan nama merchant yang muncul sesuai.')
                            ->visible($r->needsQrisCheck())->accepted(),
                    ])
                    ->action(function (DonationAccountChange $r, array $data, Action $action) {
                        try {
                            $r->approve(auth()->user(), $data['confirmation'] ?? null, (bool) ($data['qris_checked'] ?? false));
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title('Belum disetujui')->body($e->getMessage())->send();
                            $action->halt();
                        }
                        Notification::make()->success()->title('Disetujui dan tayang di halaman Infak')->send();
                    }),
                Action::make('reject')->label('Tolak')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn (DonationAccountChange $r) => auth()->user()->can('approve', $r))
                    ->schema([Textarea::make('note')->label('Alasan penolakan')->required()->rows(2)])
                    ->action(function (DonationAccountChange $r, array $data) {
                        $r->reject(auth()->user(), $data['note']);
                        Notification::make()->title('Pengajuan ditolak')->send();
                    }),
                Action::make('cancel')->label('Batalkan')->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (DonationAccountChange $r) => auth()->user()->can('cancel', $r))
                    ->requiresConfirmation()
                    ->action(function (DonationAccountChange $r) {
                        $r->cancel(auth()->user());
                        Notification::make()->title('Pengajuan dibatalkan')->send();
                    }),
                Action::make('detail')->label('Rincian')->icon(Heroicon::OutlinedEye)->color('gray')
                    ->visible(fn (DonationAccountChange $r) => $r->status !== 'menunggu')
                    ->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                    ->schema(fn (DonationAccountChange $r) => [Text::make($r->describe())]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDonationAccountChanges::route('/')];
    }
}
