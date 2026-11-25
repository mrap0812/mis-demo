<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan situs yang bisa diubah admin dari panel.
 * Nilai bawaan di bawah berasal dari kanal resmi masjid (mis.id, Linktree, poster @kajianmisbjm), per 12 Okt 2026.
 */
class Setting extends Model
{
    use Concerns\Auditable { writeAudit as protected baseWriteAudit; }

    public const CACHE = 'site-settings';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'util_text' => 'Kajian Islam ilmiah, gratis & terbuka untuk umum',
        'hero_kicker' => "Masjid Imam Syafi'i Banjarmasin · sejak 1435 H",
        'hero_title' => 'Menuntut ilmu secara ilmiah dan terbuka untuk kaum muslimin.',
        'hero_sub' => 'Kajian kitab-kitab para ulama setiap pekan bersama para asatidzah. Gratis, terbuka untuk Muslim dan Muslimah, disiarkan langsung dan diarsipkan sehingga bisa diikuti siapa saja.',
        'hero_trust' => ['Membahas kitab para ulama', 'Gratis dan terbuka untuk umum', 'Rekening infak diverifikasi dua pengurus'],
        'verse_ar' => 'إِنَّمَا يَعْمُرُ مَسَاجِدَ اللَّهِ مَنْ آمَنَ بِاللَّهِ وَالْيَوْمِ الْآخِرِ',
        'verse_id' => '"Sesungguhnya yang memakmurkan masjid-masjid Allah hanyalah orang yang beriman kepada Allah dan hari akhir."',
        'verse_ref' => 'QS. At-Taubah: 18',
        'hadith_ar' => 'مَنْ سَلَكَ طَرِيقًا يَلْتَمِسُ فِيهِ عِلْمًا سَهَّلَ اللَّهُ لَهُ بِهِ طَرِيقًا إِلَى الْجَنَّةِ',
        'hadith_id' => '"Barang siapa menempuh suatu jalan untuk mencari ilmu, Allah mudahkan baginya dengan sebab itu jalan menuju surga."',
        'hadith_ref' => 'HR. Muslim no. 2699',
        'info_handle' => '@kajianmisbjm',
        'info_url' => 'https://www.instagram.com/kajianmisbjm/',
        'kajian_poster' => null,
        'kajian_poster_alt' => 'Poster jadwal kajian rutin dan berkala, update Shafar 1448 H. Isi lengkapnya tersedia sebagai teks di halaman ini.',
        'notice_active' => false,
        'notice_text' => '',
        'notice_url' => '',
        'notice_tone' => 'gold',
        'notice_until' => null,
        'address' => 'Jl. AMD XII, Pemurus Dalam, Kec. Banjarmasin Selatan, Kota Banjarmasin, Kalimantan Selatan', // dikonfirmasi pengurus 13 Okt 2026
        'postal_code' => null,
        'latitude' => -3.3569571,
        'longitude' => 114.6156541,
        'live_url' => 'https://www.youtube.com/@masjidimamsyafiibjm/streams',
        'playlist_url' => 'https://www.youtube.com/@masjidimamsyafiibjm/playlists',
        'wa_group_url' => 'https://s.id/Daftar-WAG-MIS',
        'fab_label' => 'Tanya admin kajian',
        'fab_number' => '0811-501-145',
        'contacts' => [
            ['label' => 'WhatsApp pusat & sekretariat', 'number' => '0811-500-822', 'footer' => true],
            ['label' => 'Admin kajian', 'number' => '0811-501-145', 'footer' => true],
            ['label' => 'Kemuslimahan', 'number' => '0811-5117-776', 'footer' => false],
            ['label' => 'Qurban', 'number' => '0811-517-4444', 'footer' => false],
            ['label' => 'TPQ', 'number' => '0811-505-750', 'footer' => false],
            ['label' => 'Remaja masjid (REMIS)', 'number' => '0811-5020-300', 'footer' => false],
        ],
        'socials' => [
            ['platform' => 'instagram', 'handle' => '@masjidimamsyafiibjm', 'url' => 'https://www.instagram.com/masjidimamsyafiibjm/', 'note' => 'Akun pusat'],
            ['platform' => 'instagram', 'handle' => '@kajianmisbjm', 'url' => 'https://www.instagram.com/kajianmisbjm/', 'note' => 'Info kajian & khutbah'],
            ['platform' => 'instagram', 'handle' => '@mediamisbjm', 'url' => 'https://www.instagram.com/mediamisbjm/', 'note' => 'Media dakwah'],
            ['platform' => 'youtube', 'handle' => '@masjidimamsyafiibjm', 'url' => 'https://www.youtube.com/@masjidimamsyafiibjm', 'note' => 'Khutbah dan pengajian'],
            ['platform' => 'facebook', 'handle' => 'masjidimamsyafiibjm', 'url' => 'https://web.facebook.com/masjidimamsyafiibjm', 'note' => null],
            ['platform' => 'link', 'handle' => 'Linktree resmi', 'url' => 'https://linktr.ee/mis_bjm', 'note' => null],
        ],
        'footer_about' => 'Pusat kajian Islam ilmiah, dikelola Dewan Kemakmuran Masjid.',
        'maintenance_active' => false,
        'maintenance_title' => 'Situs sedang diperbarui',
        'maintenance_text' => 'Mohon maaf, kami sedang merapikan situs. Silakan kembali beberapa saat lagi. Info kajian tetap bisa diikuti lewat Instagram @kajianmisbjm.',
        'maintenance_until' => null,
    ];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE));
        static::deleted(fn () => Cache::forget(self::CACHE));
    }

    /** Semua pengaturan: nilai tersimpan menimpa nilai bawaan. */
    public static function site(): array
    {
        // TTL 10 menit (bukan selamanya): bila suatu saat cache terisi nilai lama karena dua request bersamaan,
        // kesalahannya pulih sendiri.
        return Cache::remember(self::CACHE, 600, fn () => array_replace(
            self::DEFAULTS,
            static::query()->pluck('value', 'key')->only(array_keys(self::DEFAULTS))->all(),
        ));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::site()[$key] ?? $default;
    }

    /** Simpan sekumpulan pengaturan; kunci yang tidak dikenal diabaikan, nilai yang tidak berubah tidak ditulis. */
    public static function put(array $data): void
    {
        foreach (array_intersect_key($data, self::DEFAULTS) as $key => $value) {
            $row = static::firstOrNew(['key' => $key]);
            if ($row->exists && $row->value === $value) {
                continue;
            }
            $row->value = $value;
            $row->save();
        }
        Cache::forget(self::CACHE);
    }

    /** Pita pengumuman tampil bila aktif, berisi, dan belum lewat tanggal berakhirnya (WITA). */
    public static function noticeVisible(?array $s = null): bool
    {
        $s ??= static::site();
        if (! $s['notice_active'] || blank($s['notice_text'])) {
            return false;
        }

        return blank($s['notice_until'])
            || CarbonImmutable::now('Asia/Makassar')->toDateString() <= CarbonImmutable::parse($s['notice_until'])->toDateString();
    }

    /** Log aktivitas menyertakan nama kunci agar jelas pengaturan mana yang berubah. */
    protected function writeAudit(string $action, ?array $changes): void
    {
        $this->baseWriteAudit($action, ['key' => [null, $this->key]] + ($changes ?? []));
    }
}
