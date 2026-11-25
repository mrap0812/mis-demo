<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\KajianSeries;
use App\Models\Post;
use App\Models\Service;
use App\Models\Speaker;
use App\Services\KajianSchedule;
use App\Services\PrayerTimes;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use IntlDateFormatter;

class SiteController extends Controller
{
    public function __construct(private KajianSchedule $schedule, private PrayerTimes $prayer) {}

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(KajianSchedule::TZ)->startOfDay();
    }

    public static function hijri(CarbonImmutable $d): string
    {
        $f = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE,
            KajianSchedule::TZ, IntlDateFormatter::TRADITIONAL, 'd MMMM y');

        return $f->format($d->setTime(12, 0)).' H';
    }

    public function home()
    {
        $today = $this->today();
        $week = $this->schedule->occurrences($today, $today->addDays(6));
        $upcoming = $this->schedule->occurrences($today, $today->addDays(14))
            ->filter(fn ($o) => in_array(KajianSchedule::status($o), ['terjadwal', 'berlangsung'], true))
            ->values();

        // Tabel sepekan: enam hari kajian (Senin..Sabtu) dimulai dari hari ini, sehingga tanggal selalu berurutan maju.
        // Termasuk slot tentatif dan kajian berkala di pekan yang bukan gilirannya.
        $series = KajianSeries::query()->active()->with('speaker')->get();
        $days = collect([1, 2, 3, 4, 5, 6])->sortBy(fn ($dow) => ($dow - $today->dayOfWeek + 7) % 7)->values()->map(function ($dow) use ($today, $series) {
            $date = $today->addDays(($dow - $today->dayOfWeek + 7) % 7);
            $items = $series->where('day_of_week', $dow)
                ->sortBy(fn ($s) => KajianSeries::SLOT_ORDER[$s->slot] ?? 9)
                ->map(fn ($s) => ['s' => $s, 'active' => $s->is_tentative ? null : $s->occursOn($date)])
                ->values();

            return ['dow' => $dow, 'date' => $date, 'items' => $items];
        });

        return view('site.home', [
            'today' => $today,
            'hijri' => self::hijri($today),
            'prayer' => $this->prayer->forDate($today),
            'days' => $days,
            'nextKajian' => $upcoming->first(),
            'posts' => Post::published()->latest('published_at')->take(3)->get(),
            'programs' => \App\Models\Program::published()->take(4)->get(),
            'campaigns' => \App\Models\Campaign::forPublic($today),
            'programsToday' => \App\Models\Program::published()->get()->filter(fn ($p) => $p->daysUntil($today) === 0)->values(),
            'weekCount' => $week->count(),
        ]);
    }

    public function kajian(Request $request)
    {
        $today = $this->today();
        $past = $request->boolean('selesai');
        $items = $this->schedule->occurrences($past ? $today->subDays(14) : $today, $today->addDays(14))
            ->when($request->filled('jenis'), fn ($c) => $c->filter(fn ($o) => $o['series']->category === $request->string('jenis')->toString()))
            ->when($request->filled('q'), function ($c) use ($request) {
                $q = mb_strtolower($request->string('q')->toString());

                return $c->filter(fn ($o) => str_contains(mb_strtolower($o['title'].' '.$o['series']->kitab.' '.$o['speaker']), $q));
            })
            ->when(! $past, fn ($c) => $c->filter(fn ($o) => KajianSchedule::status($o) !== 'selesai'))
            ->values();

        $series = KajianSeries::query()->active()->where('is_tentative', false)->where('category', '!=', 'khutbah')
            ->with('speaker')->orderBy('sort')->get();

        return view('site.kajian', compact('items', 'series', 'past'));
    }

    public function kajianShow(string $code, string $date)
    {
        abort_unless(preg_match('/^\d{8}$/', $date), 404);
        $o = $this->schedule->find($code, CarbonImmutable::createFromFormat('Ymd', $date, KajianSchedule::TZ)->toDateString());
        abort_if(! $o, 404);

        // Tiga pertemuan berikutnya dari seri yang sama (8 pekan ke depan), untuk kotak "Pertemuan berikutnya".
        $next = $this->schedule->occurrences($o['date']->addDay(), $o['date']->addDays(56))
            ->filter(fn ($x) => $x['series']->id === $o['series']->id)->take(3)->values();

        return view('site.kajian-show', ['o' => $o, 'hijri' => self::hijri($o['date']), 'next' => $next]);
    }

    /** Pencarian seluruh situs: kajian, berita, program, layanan, dan halaman. */
    public function cari(Request $request)
    {
        $q = mb_substr(trim($request->string('q')->toString()), 0, 80);
        $groups = [];
        if (mb_strlen($q) >= 2) {
            $like = '%'.addcslashes($q, '\\%_').'%';
            $m = fn ($cols) => fn ($w) => $w->where(function ($w) use ($cols, $like) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', $like);
                }
            });
            $groups['Kajian'] = KajianSeries::query()->active()->where('is_tentative', false)->with('speaker')
                ->where(fn ($w) => $m(['title', 'kitab'])($w)->orWhereHas('speaker', fn ($sp) => $sp->where('name', 'like', $like)))
                ->limit(8)->get()->map(fn ($s) => ['url' => route('kajian', ['q' => $s->title]), 'title' => $s->title,
                    'meta' => 'Setiap '.KajianSeries::DAYS[$s->day_of_week].' · '.$s->slotLabel(), 'text' => trim(($s->speaker?->name ?? '').($s->kitab ? ' · '.$s->kitab : ''), ' ·')]);
            $groups['Program'] = \App\Models\Program::published()->where($m(['title', 'tagline', 'summary', 'body']))->limit(6)->get()
                ->map(fn ($p) => ['url' => route('program.show', $p->slug), 'title' => $p->title, 'meta' => $p->schedule, 'text' => $p->summary]);
            $groups['Berita & artikel'] = Post::published()->where($m(['title', 'excerpt', 'body']))->latest('published_at')->limit(8)->get()
                ->map(fn ($p) => ['url' => route('berita.show', $p->slug), 'title' => $p->title, 'meta' => $p->published_at->translatedFormat('j F Y'), 'text' => \Illuminate\Support\Str::limit($p->excerpt, 140)]);
            $groups['Layanan'] = Service::query()->active()->where($m(['name', 'summary', 'details']))->limit(6)->get()
                ->map(fn ($s) => ['url' => route('layanan').'#'.$s->slug, 'title' => $s->name, 'meta' => $s->schedule, 'text' => $s->summary]);
            $groups['Halaman'] = ContentPage::published()->where($m(['title', 'lede', 'body']))->limit(6)->get()
                ->map(fn ($p) => ['url' => $p->url(), 'title' => $p->title, 'meta' => null, 'text' => $p->lede]);
            $groups = array_filter($groups, fn ($g) => $g->isNotEmpty());
        }

        return view('site.cari', ['q' => $q, 'groups' => $groups, 'total' => array_sum(array_map('count', $groups))]);
    }

    public function berita(Request $request)
    {
        $type = $request->string('jenis')->toString();
        $type = array_key_exists($type, Post::TYPES) ? $type : '';
        $q = mb_substr(trim($request->string('q')->toString()), 0, 80);
        $like = '%'.addcslashes($q, '\%_').'%';
        $posts = Post::published()
            ->when($type !== '', fn ($w) => $w->where('type', $type))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like)))
            ->latest('published_at')->paginate(9)->withQueryString();

        return view('site.berita', compact('posts', 'type', 'q'));
    }

    public function beritaShow(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $base = Post::published()->whereKeyNot($post->id);
        $prev = (clone $base)->where('published_at', '<', $post->published_at)->latest('published_at')->first(['id', 'slug', 'title']);
        $next = (clone $base)->where('published_at', '>', $post->published_at)->oldest('published_at')->first(['id', 'slug', 'title']);
        $related = (clone $base)->where('type', $post->type)->latest('published_at')->take(3)->get();
        if ($related->count() < 3) {
            $related = $related->merge((clone $base)->whereNotIn('id', $related->pluck('id'))->latest('published_at')->take(3 - $related->count())->get());
        }

        return view('site.berita-show', compact('post', 'prev', 'next', 'related'));
    }

    public function profil()
    {
        $page = ContentPage::published()->where('slug', 'profil')->firstOrFail();

        return view('site.profil', [
            'page' => $page,
            'history' => Post::published()->where('type', 'berita')->latest('published_at')->take(6)->get(['title', 'slug', 'published_at']),
            'speakers' => Speaker::query()->where('is_active', true)->withCount('series')->orderByDesc('series_count')->orderBy('name')->get(),
            'pages' => ContentPage::published()->where('slug', '!=', 'profil')->orderBy('title')->get(),
        ]);
    }

    public function halaman(string $slug)
    {
        abort_if($slug === 'profil', 404);

        return view('site.halaman', ['page' => ContentPage::published()->where('slug', $slug)->firstOrFail()]);
    }

    public function program()
    {
        $today = $this->today();
        // Program terdekat di atas: hari ini, besok, dst.; yang belum punya hari pelaksanaan di akhir.
        $programs = \App\Models\Program::query()->published()->get()
            ->sortBy(fn ($p) => [$p->daysUntil($today) ?? 9, $p->sort])->values();

        return view('site.program', ['programs' => $programs, 'today' => $today]);
    }

    public function programShow(string $slug)
    {
        $program = \App\Models\Program::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('site.program-show', [
            'program' => $program,
            'today' => $this->today(),
            'others' => \App\Models\Program::query()->published()->whereKeyNot($program->getKey())->limit(4)->get(),
        ]);
    }

    public function layanan()
    {
        return view('site.layanan', ['services' => Service::query()->active()->get()]);
    }

    public function kontak()
    {
        return view('site.kontak');
    }

    public function infak()
    {
        return view('site.infak', ['accounts' => \App\Models\DonationAccount::query()->live()->get(), 'campaigns' => \App\Models\Campaign::forPublic($today = $this->today()), 'today' => $today]);
    }

    /** Jadwal shalat satu bulan (?bulan=YYYY-MM), maksimal 12 bulan ke belakang dan ke depan. */
    public function jadwalShalat(Request $request)
    {
        $today = $this->today();
        $current = $today->startOfMonth();
        $month = $current;
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', (string) $request->query('bulan'), $m)) {
            $month = CarbonImmutable::create((int) $m[1], (int) $m[2], 1, 0, 0, 0, KajianSchedule::TZ);
        }
        abort_if(abs($current->diffInMonths($month, false)) > 12, 404);

        $hf = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE,
            KajianSchedule::TZ, IntlDateFormatter::TRADITIONAL, 'd MMMM');
        $rows = [];
        for ($d = $month; $d->month === $month->month; $d = $d->addDay()) {
            $rows[] = ['date' => $d, 'today' => $d->isSameDay($today), 't' => $this->prayer->forDate($d), 'hijri' => $hf->format($d->setTime(12, 0))];
        }
        $prev = $month->subMonth();
        $next = $month->addMonth();

        return view('site.jadwal-shalat', [
            'month' => $month, 'rows' => $rows, 'isCurrent' => $month->equalTo($current),
            'prev' => abs($current->diffInMonths($prev, false)) <= 12 ? $prev : null,
            'next' => abs($current->diffInMonths($next, false)) <= 12 ? $next : null,
            'hijriRange' => self::hijri($month).' sampai '.self::hijri($month->endOfMonth()),
        ]);
    }

    /**
     * Kalender kajian untuk dilanggan (Google Calendar, iPhone, Outlook). Pembatalan ikut terkirim sebagai STATUS:CANCELLED,
     * sehingga kalender pelanggan ikut diperbarui saat aplikasi kalender menyegarkan langganannya.
     */
    public function kajianIcs(Request $request)
    {
        $today = $this->today();
        $jenis = $request->string('jenis')->toString();
        $items = $this->schedule->occurrences($today->subDays(7), $today->addDays(60))
            ->when(array_key_exists($jenis, KajianSeries::CATEGORIES), fn ($c) => $c->filter(fn ($o) => $o['series']->category === $jenis));

        $esc = fn ($s) => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $s);
        $fold = function ($line) {
            $out = '';
            while (strlen($line) > 74) {
                $cut = 74;
                while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) $cut--; // jangan memotong karakter UTF-8
                $out .= substr($line, 0, $cut)."\r\n ";
                $line = substr($line, $cut);
            }

            return $out.$line."\r\n";
        };
        $utc = fn ($d) => $d->utc()->format('Ymd\THis\Z');
        $stamp = $utc(CarbonImmutable::now());

        $ics = $fold('BEGIN:VCALENDAR').$fold('VERSION:2.0').$fold('PRODID:-//Masjid Imam Syafii Banjarmasin//Jadwal Kajian//ID')
            .$fold('CALSCALE:GREGORIAN').$fold('METHOD:PUBLISH').$fold("X-WR-CALNAME:Kajian Masjid Imam Syafi'i")
            .$fold('X-WR-TIMEZONE:Asia/Makassar').$fold('REFRESH-INTERVAL;VALUE=DURATION:PT6H').$fold('X-PUBLISHED-TTL:PT6H');
        foreach ($items as $o) {
            $status = $o['status_override'];
            $title = $o['title'].($status === 'dibatalkan' ? ' (DIBATALKAN)' : ($status === 'ditunda' ? ' (DITUNDA)' : ''));
            $desc = trim(($o['speaker'] ? 'Pemateri: '.$o['speaker']."\n" : '').$o['time_label']."\n"
                .($o['status_reason'] ? 'Keterangan: '.$o['status_reason']."\n" : '').'Jam mulai adalah perkiraan dari jadwal shalat.');
            $url = route('kajian.show', [$o['series']->code, $o['date']->format('Ymd')]);
            $ics .= $fold('BEGIN:VEVENT')
                .$fold('UID:'.$o['id'].'@mis-banjarmasin')
                .$fold('DTSTAMP:'.$stamp)
                .$fold('DTSTART:'.$utc($o['starts_at']))
                .$fold('DTEND:'.$utc($o['ends_at']))
                .$fold('SUMMARY:'.$esc($title))
                .$fold('DESCRIPTION:'.$esc($desc."\n".$url))
                .$fold("LOCATION:".$esc("Masjid Imam Syafi'i, Pemurus Dalam, Banjarmasin"))
                .$fold('URL:'.$url)
                .$fold('STATUS:'.($status === 'dibatalkan' ? 'CANCELLED' : ($status === 'ditunda' ? 'TENTATIVE' : 'CONFIRMED')))
                .$fold('END:VEVENT');
        }
        $ics .= $fold('END:VCALENDAR');

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="kajian-mis.ics"',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    /**
     * Alamat lama dari WordPress mis.id: formulir Google, tulisan, dan file unggahan.
     * Alamat yang tidak dikenal tetap 404 seperti biasa.
     */
    public function legacy(Request $request)
    {
        $path = trim($request->path(), '/');
        $map = config('mis.legacy_redirects', []);
        if (isset($map[$path])) {
            $to = $map[$path];

            // Formulir di luar situs memakai 302 agar tujuan bisa diganti kapan saja tanpa tersimpan permanen di peramban.
            return str_starts_with($to, 'http') ? redirect()->away($to, 302) : redirect($to, 301);
        }
        if (preg_match('#^[a-z0-9-]+$#', $path)) {
            $post = Post::published()->where('source_url', 'https://www.mis.id/'.$path.'/')->first();
            if ($post) {
                return redirect()->route('berita.show', $post->slug, 301);
            }
        }
        if (str_starts_with($path, 'wp-content/uploads/')) {
            $rel = 'wp/'.substr($path, strlen('wp-content/uploads/'));
            if (! str_contains($rel, '..') && \Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
                return redirect(\Illuminate\Support\Facades\Storage::disk('public')->url($rel), 301);
            }
        }
        abort(404);
    }

    public function sitemap()
    {
        $today = $this->today();
        $urls = collect([
            ['loc' => route('home'), 'freq' => 'daily'],
            ['loc' => route('kajian'), 'freq' => 'daily'],
            ['loc' => route('berita'), 'freq' => 'weekly'],
            ['loc' => route('laporan'), 'freq' => 'monthly'],
            ['loc' => route('profil'), 'freq' => 'monthly'],
            ['loc' => route('layanan'), 'freq' => 'monthly'],
            ['loc' => route('program'), 'freq' => 'monthly'],
            ['loc' => route('infak'), 'freq' => 'monthly'],
            ['loc' => route('kontak'), 'freq' => 'monthly'],
            ['loc' => route('shalat'), 'freq' => 'monthly'],
        ]);
        foreach ($this->schedule->occurrences($today, $today->addDays(14)) as $o) {
            $urls->push(['loc' => route('kajian.show', [$o['series']->code, $o['date']->format('Ymd')]), 'freq' => 'daily']);
        }
        foreach (ContentPage::published()->where('slug', '!=', 'profil')->get(['slug']) as $pg) {
            $urls->push(['loc' => route('halaman', $pg->slug), 'freq' => 'monthly']);
        }
        foreach (\App\Models\Program::published()->get(['slug', 'updated_at']) as $pr) {
            $urls->push(['loc' => route('program.show', $pr->slug), 'freq' => 'monthly', 'mod' => $pr->updated_at?->toAtomString()]);
        }
        foreach (Post::published()->latest('published_at')->get(['slug', 'updated_at']) as $p) {
            $urls->push(['loc' => route('berita.show', $p->slug), 'freq' => 'monthly', 'mod' => $p->updated_at->toAtomString()]);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>'.e($u['loc']).'</loc>'.(isset($u['mod']) ? '<lastmod>'.$u['mod'].'</lastmod>' : '').'<changefreq>'.$u['freq'].'</changefreq></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
