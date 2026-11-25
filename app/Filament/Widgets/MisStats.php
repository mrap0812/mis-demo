<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Agenda;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\KajianSchedule;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MisStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Kuerinya ringan; tampil langsung tanpa placeholder "Loading...".
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $today = CarbonImmutable::now(KajianSchedule::TZ)->startOfDay();
        $occ = app(KajianSchedule::class)->occurrences($today, $today->addDays(6));
        $changed = $occ->filter(fn ($o) => $o['status_override'])->count();
        $review = Post::where('status', 'ditinjau')->count();

        $stats = [
            Stat::make('Kajian 7 hari ke depan', $occ->count())
                ->description($changed ? $changed.' dibatalkan/ditunda' : 'Semua sesuai jadwal')
                ->color($changed ? 'warning' : 'success')->url(Agenda::getUrl()),
            Stat::make('Menunggu tinjauan', $review)
                ->description($review ? 'Perlu diperiksa peninjau' : 'Tidak ada antrean')
                ->color($review ? 'warning' : 'gray')->url(PostResource::getUrl('index')),
            Stat::make('Tulisan terbit', Post::published()->count())->description('Berita, pengumuman, artikel')
                ->url(PostResource::getUrl('index')),
        ];

        if (auth()->user()?->isTreasurer() || auth()->user()?->isReviewer()) {
            // Tulisan terbit yang memuat nomor mirip rekening di luar menu Infak (jalur yang tidak melewati persetujuan ganda).
            $risky = Post::published()->get(['title', 'excerpt', 'body'])
                ->filter(fn ($p) => \App\Support\AccountNumberGuard::scan($p->title, $p->excerpt, $p->body));
            if ($risky->isNotEmpty()) {
                $stats[] = Stat::make('Nomor rekening di berita', $risky->count())
                    ->description('Belum terdaftar di menu Infak: '.\Illuminate\Support\Str::limit($risky->pluck('title')->implode('; '), 80))
                    ->color('danger')->url(PostResource::getUrl('index'));
            }
        }

        if (auth()->user()?->isTreasurer()) {
            $pending = \App\Models\DonationAccountChange::where('status', 'menunggu')->count();
            $stats[] = \Filament\Widgets\StatsOverviewWidget\Stat::make('Persetujuan infak', $pending)
                ->description($pending ? 'Perlu diperiksa orang kedua' : 'Tidak ada antrean')
                ->color($pending ? 'danger' : 'gray')
                ->url(\App\Filament\Resources\DonationAccountChanges\DonationAccountChangeResource::getUrl('index'));
        }

        return $stats;
    }
}
