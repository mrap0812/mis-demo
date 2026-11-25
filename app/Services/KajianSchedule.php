<?php

namespace App\Services;

use App\Models\KajianSeries;
use App\Models\KajianSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menggabungkan aturan jadwal seri (rutin/berkala) dengan data per pertemuan (judul poster, batal/tunda, rekaman).
 * Semua tanggal dan jam dalam zona Asia/Makassar.
 */
class KajianSchedule
{
    public const TZ = 'Asia/Makassar';

    public function __construct(private PrayerTimes $prayer) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function occurrences(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $from = CarbonImmutable::parse($from->toDateString(), self::TZ);
        $to = CarbonImmutable::parse($to->toDateString(), self::TZ);

        $series = KajianSeries::query()->active()->with('speaker')->get();
        $sessions = KajianSession::query()->with('speaker')
            // Kolom date tersimpan sebagai 'Y-m-d 00:00:00'; whereBetween dengan string tanggal melewatkan batas akhir.
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->get()->keyBy(fn ($s) => $s->kajian_series_id.'|'.$s->date->toDateString());

        $out = collect();
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            foreach ($series as $s) {
                $session = $sessions->get($s->id.'|'.$d->toDateString());
                if (! $s->occursOn($d) && ! $session) {
                    continue;
                }
                $out->push($this->build($s, $d, $session));
            }
        }

        return $out->sortBy('starts_at')->values();
    }

    /** Satu pertemuan berdasarkan kode seri dan tanggal (untuk halaman detail). */
    public function find(string $code, string $date): ?array
    {
        $s = KajianSeries::query()->with('speaker')->where('code', $code)->first();
        if (! $s) {
            return null;
        }
        $d = CarbonImmutable::parse($date, self::TZ);
        $session = KajianSession::query()->with('speaker')->where('kajian_series_id', $s->id)->whereDate('date', $d)->first();
        if (! $s->occursOn($d) && ! $session) {
            return null;
        }

        return $this->build($s, $d, $session);
    }

    private function build(KajianSeries $s, CarbonImmutable $d, ?KajianSession $session): array
    {
        [$startMin, $endMin, $timeLabel] = $this->slotTimes($s->slot, $d);
        $speaker = $session?->speaker ?? $s->speaker;

        return [
            'id' => $s->code.'-'.$d->format('Ymd'),
            'series' => $s,
            'session' => $session,
            'date' => $d,
            'title' => $session?->title ?: $s->title,
            'speaker' => $speaker?->name,
            'speaker_short' => $speaker?->displayShort(),
            'status_override' => $session && $session->status !== 'terjadwal' ? $session->status : null,
            'status_reason' => $session?->status_reason,
            'source' => $session && $session->source === 'poster' ? 'poster' : 'jadwal',
            'recording_url' => $session?->recording_url,
            'notes' => $session?->notes,
            'starts_at' => $d->startOfDay()->addMinutes($startMin),
            'ends_at' => $d->startOfDay()->addMinutes($endMin),
            'time_label' => $timeLabel,
        ];
    }

    /** Jam perkiraan berdasarkan jadwal shalat tanggal tersebut. @return array{0:int,1:int,2:string} */
    public function slotTimes(string $slot, CarbonInterface $d): array
    {
        $p = $this->prayer->forDate($d);

        return match ($slot) {
            'subuh' => [$p['fajr'] + 20, $p['fajr'] + 80, "Ba'da Subuh s.d. selesai (± ".PrayerTimes::hhmm($p['fajr'] + 20).' WITA)'],
            'ashar' => [$p['asr'] + 15, $p['asr'] + 90, "Ba'da Ashar s.d. selesai (± ".PrayerTimes::hhmm($p['asr'] + 15).' WITA)'],
            'jumat' => [$p['dhuhr'], $p['dhuhr'] + 50, 'Adzan '.PrayerTimes::hhmm($p['dhuhr']).' WITA (hitungan)'],
            default => [$p['maghrib'] + 10, $p['isha'], "Ba'da Maghrib s.d. Isya (± ".PrayerTimes::hhmm($p['maghrib'] + 10).' WITA)'],
        };
    }

    /** Status berdasarkan waktu sekarang. */
    public static function status(array $o, ?CarbonInterface $now = null): string
    {
        if ($o['status_override']) {
            return $o['status_override'];
        }
        $now ??= CarbonImmutable::now(self::TZ);

        return $now->lt($o['starts_at']) ? 'terjadwal' : ($now->lt($o['ends_at']) ? 'berlangsung' : 'selesai');
    }
}
