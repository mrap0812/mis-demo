<?php

namespace App\Filament\Resources\FinancialReports;

use App\Models\FinancialReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class FinancialReportResource extends Resource
{
    protected static ?string $model = FinancialReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Infak';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'laporan keuangan';

    protected static ?string $pluralModelLabel = 'Laporan keuangan';

    protected static ?string $slug = 'laporan-keuangan';

    private static function items(string $name, string $label): Repeater
    {
        return Repeater::make($name)->label($label)->defaultItems(1)->reorderable()->collapsible()
            ->itemLabel(fn (array $state) => trim(($state['label'] ?? '').' '.(isset($state['amount']) && $state['amount'] !== '' ? FinancialReport::rupiah((int) $state['amount']) : '')))
            ->columns(3)->live(debounce: 500)
            ->schema([
                TextInput::make('label')->label('Pos')->required()->maxLength(80),
                TextInput::make('note')->label('Keterangan (opsional)')->maxLength(160),
                TextInput::make('amount')->label('Nominal (Rp)')->numeric()->integer()->minValue(0)->maxValue(100_000_000_000)->required(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        $sum = fn (?array $items) => (int) collect($items ?? [])->sum(fn ($i) => (int) ($i['amount'] ?? 0));

        return $schema->columns(3)->components([
            Section::make('Rincian')->columnSpan(2)->schema([
                self::items('incomes', 'Pemasukan'),
                self::items('expenses', 'Pengeluaran'),
            ]),
            Section::make('Periode & ringkasan')->columnSpan(1)->schema([
                DatePicker::make('period')->label('Bulan laporan')->native(false)->displayFormat('F Y')->required()
                    ->helperText('Pilih tanggal berapa saja; disimpan sebagai bulan itu.')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $state) => $rule->where('period', $state ? \Carbon\Carbon::parse($state)->startOfMonth()->toDateString() : null)),
                TextInput::make('opening_balance')->label('Saldo awal (Rp)')->numeric()->integer()->required()->live(debounce: 500)
                    ->helperText(function (Get $get) {
                        if (! $get('period')) {
                            return null;
                        }
                        $prev = FinancialReport::query()->where('period', \Carbon\Carbon::parse($get('period'))->startOfMonth()->subMonth()->toDateString())->first();

                        return $prev ? 'Saldo akhir '.$prev->label().': '.FinancialReport::rupiah($prev->closingBalance()) : null;
                    }),
                Placeholder::make('ringkas')->label('Dihitung otomatis')->content(function (Get $get) use ($sum) {
                    $in = $sum($get('incomes'));
                    $out = $sum($get('expenses'));
                    $close = (int) $get('opening_balance') + $in - $out;

                    return new HtmlString('Pemasukan: <b>'.e(FinancialReport::rupiah($in)).'</b><br>Pengeluaran: <b>'.e(FinancialReport::rupiah($out)).'</b><br>Saldo akhir: <b>'.e(FinancialReport::rupiah($close)).'</b>');
                }),
                FileUpload::make('poster')->label('Poster resmi (bukti, opsional)')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')->directory('laporan')->maxSize(4096),
                TextInput::make('source_note')->label('Sumber data')->maxLength(255)->placeholder('Contoh: Buku kas bendahara, diperiksa 2 Nov 2026'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('period', 'desc')
            ->columns([
                TextColumn::make('period')->label('Bulan')->formatStateUsing(fn (FinancialReport $r) => $r->label())->sortable()->weight('bold'),
                TextColumn::make('masuk')->label('Pemasukan')->state(fn (FinancialReport $r) => FinancialReport::rupiah($r->incomeTotal())),
                TextColumn::make('keluar')->label('Pengeluaran')->state(fn (FinancialReport $r) => FinancialReport::rupiah($r->expenseTotal())),
                TextColumn::make('saldo')->label('Saldo akhir')->state(fn (FinancialReport $r) => FinancialReport::rupiah($r->closingBalance())),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state) => FinancialReport::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'terbit' => 'success', 'diajukan' => 'warning', default => 'gray' }),
            ])
            ->recordActions([
                Action::make('submit')->label('Ajukan')->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (FinancialReport $r) => $r->status === 'draf' && auth()->user()?->can('update', $r))
                    ->requiresConfirmation()->modalDescription('Laporan akan diperiksa pengurus lain sebelum tampil di situs.')
                    ->action(function (FinancialReport $r) {
                        $r->forceFill(['status' => 'diajukan', 'submitted_by' => auth()->id()])->save();
                        Notification::make()->success()->title('Laporan diajukan')->send();
                    }),
                Action::make('approve')->label('Setujui & terbitkan')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->visible(fn (FinancialReport $r) => auth()->user()?->can('approve', $r))
                    ->requiresConfirmation()
                    ->modalDescription(fn (FinancialReport $r) => 'Pastikan angka sudah dicocokkan dengan buku kas. Saldo akhir '.FinancialReport::rupiah($r->closingBalance()).'.'
                        .(($gap = $r->openingGap()) ? ' Perhatian: saldo awal berbeda '.FinancialReport::rupiah($gap).' dari saldo akhir bulan sebelumnya.' : ''))
                    ->action(function (FinancialReport $r) {
                        abort_unless(auth()->user()->can('approve', $r), 403);
                        $r->forceFill(['status' => 'terbit', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
                        Notification::make()->success()->title('Laporan terbit di situs')->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialReports::route('/'),
            'create' => Pages\CreateFinancialReport::route('/create'),
            'edit' => Pages\EditFinancialReport::route('/{record}/edit'),
        ];
    }
}
