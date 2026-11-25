<?php

namespace App\Filament\Pages;

use App\Filament\Resources\KajianSessions\KajianSessionResource;
use App\Services\KajianSchedule;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Agenda 14 hari: tempat paling cepat untuk mengisi judul pertemuan, membatalkan, atau menunda satu kajian. */
class Agenda extends Page
{
    protected string $view = 'filament.pages.agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Kajian';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Agenda 14 hari';

    protected static ?string $navigationLabel = 'Agenda 14 hari';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::now(KajianSchedule::TZ)->startOfDay();
        $items = app(KajianSchedule::class)->occurrences($today, $today->addDays(13))
            ->map(function ($o) {
                $o['status_now'] = KajianSchedule::status($o);
                $o['edit_url'] = $o['session']
                    ? KajianSessionResource::getUrl('edit', ['record' => $o['session']])
                    : KajianSessionResource::getUrl('create', ['series' => $o['series']->id, 'date' => $o['date']->toDateString()]);
                $o['cancel_url'] = $o['session'] ? $o['edit_url']
                    : KajianSessionResource::getUrl('create', ['series' => $o['series']->id, 'date' => $o['date']->toDateString(), 'status' => 'dibatalkan']);

                return $o;
            })
            ->groupBy(fn ($o) => $o['date']->toDateString());

        return ['days' => $items];
    }
}
