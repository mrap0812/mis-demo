<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\KajianSeries;
use App\Models\KajianSession;
use App\Models\Post;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data awal dari sumber resmi yang sudah diaudit (12 Oktober 2026):
 * - Jadwal kajian: poster jadwal @kajianmisbjm, update Shafar 1448 H.
 * - Judul pertemuan 5 s.d. 12 Okt 2026: poster per pertemuan @kajianmisbjm dan rekaman YouTube.
 * - Berita: arsip mis.id.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun uji HANYA untuk lingkungan lokal. Produksi: buat akun lewat `php artisan make:filament-user`.
        if (app()->environment('local', 'testing')) {
            foreach ([['Admin Uji', 'admin@mis.test', 'admin'], ['Editor Uji', 'editor@mis.test', 'editor'], ['Peninjau Uji', 'peninjau@mis.test', 'peninjau'], ['Bendahara Uji', 'bendahara@mis.test', 'bendahara']] as [$n, $e, $r]) {
                User::updateOrCreate(['email' => $e], ['name' => $n, 'password' => 'password', 'role' => $r]);
            }
        }

        $sp = [];
        foreach ([
            'hafizd' => ['Ustadz Muhammad Hafizd Anshari, B.A.', 'Ust. Muhammad Hafizd Anshari'],
            'arif' => ['Ustadz Arif Usman Anugraha, B.A.', 'Ust. Arif Usman Anugraha'],
            'zainuddin' => ['Ustadz Ahmad Zainuddin Al-Banjary', 'Ust. Ahmad Zainuddin Al-Banjary'],
            'nur' => ['Ustadz Muhammad Nur Al-Banjary', 'Ust. Muhammad Nur Al-Banjary'],
            'haikal' => ['Ustadz Dr. Muhammad Haikal Ali Basyarahil, Lc., M.A.', 'Ust. Dr. Muhammad Haikal Ali Basyarahil'],
            'syadam' => ['Ustadz Dr. Syadam Husein Al-Katiri, Lc., M.A.', 'Ust. Dr. Syadam Husein Al-Katiri'],
            'ayman' => ['Ustadz Ayman Abdillah, Lc., M.A.', 'Ust. Ayman Abdillah'],
            'husaini' => ['Ustadz Muhammad Husaini', 'Ust. Muhammad Husaini'],
        ] as $k => [$name, $short]) {
            $sp[$k] = Speaker::updateOrCreate(['name' => $name], ['short_name' => $short, 'is_active' => true])->id;
        }

        $src = 'Poster jadwal @kajianmisbjm, update Shafar 1448 H';
        $series = [
            ['tafsir', "Tafsir Al-Qur'an", "Tafsir Al-Qur'an (saat ini Juz 28)", 'تفسير القرآن', 'kajian', 'subuh', 1, 'hafizd', '#A3121A'],
            ['talbis-iblis', 'Talbis Iblis', 'Talbis Iblis (tipu muslihat dan perangkap Iblis)', 'تلبيس إبليس', 'kajian', 'maghrib', 1, 'hafizd', '#5A2226'],
            ['sirah', 'Sirah Nabawiyah', 'Sirah Nabawiyah', 'السيرة النبوية', 'kajian', 'subuh', 2, 'arif', '#2F6B4F'],
            ['akhlak', 'Ensiklopedia Akhlak Islam', "Mausu'ah Al-Akhlak Al-Islamiyyah", 'موسوعة الأخلاق الإسلامية', 'kajian', 'maghrib', 2, 'arif', '#1F5E70', null, [4]],
            ['daful-iham', "Sinkronisasi Dugaan Kontradiksi dalam Al-Qur'an", "Daf'u Iihaam Al-Idhthiraab", 'دفع إيهام الاضطراب', 'berkala', 'maghrib', 2, 'ayman', '#4B3A8C', [4], null, 'Pekan ke-4'],
            ['kaifa', 'Ajaran Rasulullah ﷺ dalam Berinteraksi & Bergaul', "Kaifa 'Amaluhum", 'كيف عاملهم', 'kajian', 'subuh', 3, 'hafizd', '#B5651D'],
            ['ushulul-iman', "Pokok Keimanan dalam Naungan Al-Qur'an & Sunnah", "Ushulul Iman fi Dhau'il Kitabi was Sunnah", 'أصول الإيمان في ضوء الكتاب والسنة', 'kajian', 'maghrib', 3, 'zainuddin', '#2E5E45'],
            ['jami-bulugh', 'Syarah Kitab Akhlak & Adab Bulughul Maram', "Kitabul Jami' dari Bulughul Maram", 'كتاب الجامع من بلوغ المرام', 'kajian', 'subuh', 4, 'nur', '#8A2550'],
            ['fikih', 'Fikih Ibadah', 'Fikih Muyassar', 'الفقه الميسر', 'kajian', 'maghrib', 4, 'nur', '#3D6B2A'],
            ['khutbah', 'Khutbah & Shalat Jumat', null, 'خطبة الجمعة', 'khutbah', 'jumat', 5, null, '#22150F'],
            ['mukhtashar-bukhari', 'Ringkasan Shahih Bukhari', 'Mukhtashar Shahih Al-Bukhari karya Imam Az-Zabidi', 'مختصر صحيح البخاري', 'kajian', 'maghrib', 5, 'haikal', '#B07A12'],
            ['muslimah-adab', 'Hadits-Hadits Seputar Adab (khusus Muslimah)', "Syarah Kitabul Jami'", 'كتاب الجامع', 'muslimah', 'ashar', 6, 'arif', '#9C3D6B', [1, 2], null, 'Pekan 1 & 2 · Muslimah'],
            ['al-mufid', 'Syarah Kitab Tauhid', 'Al-Mufid ala Kitab At-Tauhid', 'المفيد على كتاب التوحيد', 'kajian', 'maghrib', 6, 'syadam', '#6B2E86'],
        ];
        foreach ($series as $i => $r) {
            KajianSeries::updateOrCreate(['code' => $r[0]], [
                'title' => $r[1], 'kitab' => $r[2], 'kitab_ar' => $r[3], 'category' => $r[4], 'slot' => $r[5],
                'day_of_week' => $r[6], 'speaker_id' => $r[7] ? $sp[$r[7]] : null, 'color' => $r[8],
                'weeks' => $r[9] ?? null, 'except_weeks' => $r[10] ?? null, 'badge' => $r[11] ?? null,
                'is_active' => true, 'is_tentative' => false, 'source' => $src, 'sort' => $i,
            ]);
        }
        foreach ([[5, 'jumat-subuh'], [6, 'sabtu-subuh']] as [$dow, $code]) {
            KajianSeries::updateOrCreate(['code' => $code], [
                'title' => 'Tentatif', 'category' => 'kajian', 'slot' => 'subuh', 'day_of_week' => $dow,
                'is_tentative' => true, 'is_active' => true, 'color' => '#9A8F86', 'source' => $src, 'sort' => 90 + $dow,
            ]);
        }

        $sid = fn ($code) => KajianSeries::where('code', $code)->value('id');
        foreach ([
            ['tafsir', '2026-10-12', 'Tafsir Surah At-Taghabun', null, 'Pembahasan Tafsir Juz 28. Rekaman: "Tafsir Surah At-Taghabun Ayat 9 dst".'],
            ['talbis-iblis', '2026-10-05', 'Inilah Pertemanan yang Dibangun di atas Kebaikan', null, 'Kajian Islam tematik 1448 H pada slot Senin ba\'da Maghrib.'],
            ['ushulul-iman', '2026-10-07', 'Hak-Hak Nabi Muhammad ﷺ atas Umatnya #6', null, null],
            ['mukhtashar-bukhari', '2026-10-09', 'Mencuci Darah', null, 'Pembahasan Kitab Wudhu.'],
            ['khutbah', '2026-10-09', '3 Golongan yang Akan Menyesal di Akhirat', 'husaini', "Mu'adzin: Sdr. Mustofa Lutfi. Waktu adzan 12.12 WITA (poster)."],
            ['al-mufid', '2026-10-10', 'Menyembelih Hewan untuk Selain Allah', null, 'Pembahasan Bab 10.'],
            ['muslimah-adab', '2026-10-10', 'Mendamaikan yang Bersengketa', null, 'Judul dari rekaman YouTube; tanggal disimpulkan dari jadwal pekan ke-2.'],
        ] as [$code, $date, $title, $speaker, $notes]) {
            // whereDate: kolom date tersimpan 'Y-m-d 00:00:00', jadi pencocokan string 'Y-m-d' biasa tidak menemukan baris lama.
            (KajianSession::where('kajian_series_id', $sid($code))->whereDate('date', $date)->first()
                ?? new KajianSession(['kajian_series_id' => $sid($code), 'date' => $date]))->fill([
                'title' => $title, 'speaker_id' => $speaker ? $sp[$speaker] : null, 'notes' => $notes, 'source' => 'poster', 'status' => 'terjadwal',
            ])->save();
        }

        foreach ([
            ['pengumuman', 'Pengumuman', 'Sudah punya niat berkurban tahun depan?', '2026-09-16 20:03', 'tabkurban', 'Tabungan Kurban 1448 H: menabung sedikit demi sedikit setiap bulan. Rekening khusus kurban BSI 700-7000-479 a.n. Imam Syafii. Informasi dan konfirmasi: 0811-517-4444.', 'https://www.mis.id/sudah-punya-niat-berkurban-tahun-depan/'],
            ['berita', 'Sosial', 'Penyerahan bantuan MIS-Peduli untuk korban kebakaran Jl. Setia, Pemurus Dalam', '2023-09-11 13:37', 'peduli', 'Bantuan disalurkan langsung oleh tim MIS-Peduli kepada warga terdampak.', 'https://www.mis.id/mis-peduli-01/'],
            ['berita', 'Kurban', 'Berkurban 32 sapi di hari Jumat, berkah dalam kebersamaan', '2023-06-30 15:00', 'sapi', "Penyembelihan hewan kurban Idul Adha 1444 H di Masjid Imam Syafi'i.", 'https://www.mis.id/berkurban-32-sapi-di-hari-jumat-berkah-dalam-kebersamaan/'],
            ['berita', 'Kegiatan', "Kunjungan perwakilan Kemenag Banjarmasin ke Masjid Imam Syafi'i", '2023-06-15 13:40', 'kemenag', 'Kunjungan Kementerian Agama Kota Banjarmasin, termasuk ke TPQ masjid.', 'https://www.mis.id/kunjungan-kemenag-tpq/'],
            ['berita', 'Sosial', 'Event sosial masjid pra-Ramadhan berlangsung meriah dan dihadiri warga sekitar', '2023-04-20 15:35', 'praramadhan', 'Rangkaian kegiatan sosial menyambut Ramadhan 1444 H.', 'https://www.mis.id/event-sosial-masjid-pra-ramadhan-berlangsung-meriah-dan-dihadiri-warga-sekitar/'],
            ['berita', 'Kajian', 'Safari dakwah Ustadz Ali Musri berjalan lancar', '2022-10-17 17:06', 'safari', "Kajian bersama Ustadz Ali Musri di Masjid Imam Syafi'i Banjarmasin.", 'https://www.mis.id/safari-dakwah-ustadz-ali-musri-di-masjid-imam-syafii-berjalan-lancar/'],
        ] as [$type, $cat, $title, $at, $img, $excerpt, $url]) {
            Post::withTrashed()->updateOrCreate(['source_url' => $url], [
                'type' => $type, 'category' => $cat, 'title' => $title, 'excerpt' => $excerpt,
                'body' => '<p>'.e($excerpt).'</p><p><em>Diimpor dari arsip mis.id. Isi lengkap tersedia di tautan sumber.</em></p>',
                'cover' => "posts/{$img}.jpg", 'status' => PostStatus::Terbit, 'published_at' => $at,
            ]);
        }

        $this->call(SiteContentSeeder::class);
        $this->call(ProgramSeeder::class);
    }
}
