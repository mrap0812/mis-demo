<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Program dari poster resmi masjid yang diterima 13 Oktober 2026.
 * Isi disalin apa adanya dari poster; tidak ada tambahan yang tidak tertulis di sana.
 * firstOrCreate: aman dijalankan ulang, tidak menimpa suntingan admin.
 */
class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        Program::firstOrCreate(['slug' => 'sedekah-sampah'], [
            'title' => 'Sedekah Sampah',
            'category' => 'sosial',
            'tagline' => 'Meraih banyak faedah dari pengelolaan sampah',
            'summary' => 'Program pengumpulan sampah yang masih memiliki nilai, untuk kemudian disalurkan menjadi manfaat bagi operasional masjid dan kegiatan kebaikan lainnya.',
            'schedule' => 'Setiap Ahad, 08.00 sampai 11.00 WITA',
            'days' => [0],
            'location' => 'Stand di halaman masjid, Jl. AMD XII, Pemurus Dalam, Banjarmasin Selatan',
            'audience' => 'Terbuka untuk umum',
            'is_free' => false,
            'items_title' => 'Sampah yang diterima',
            'items' => [
                ['title' => 'Kemasan plastik', 'text' => 'Kemasan, botol minuman, dan sejenisnya.'],
                ['title' => 'Kertas & kardus', 'text' => 'Kertas dan kardus yang masih layak didaur ulang.'],
                ['title' => 'Minyak bekas', 'text' => 'Minyak goreng atau minyak jelantah bekas.'],
            ],
            'notes_title' => 'Penting untuk diperhatikan',
            'notes' => [
                'Diutamakan sampah atau bahan daur ulang yang sudah dibersihkan.',
                'Tidak basah dan tidak berbau.',
                'Tidak terkontaminasi kotoran, najis, atau benda berbahaya lainnya.',
                'Bawa dengan kemasan yang baik dan rapi.',
            ],
            'steps' => [
                'Pilah dari rumah',
                'Bawa ke panitia di halaman masjid',
                'Setorkan sampah sebagai sedekah',
                'Sampah dijual ke pengepul atau bank sampah',
                'Hasilnya digunakan untuk operasional masjid dan manfaat lainnya',
            ],
            'closing' => "Niatkan setiap langkah kebaikan ini karena Allah Ta'ala. Semoga sedekah sampah ini menjadi amal yang bernilai pahala, sekaligus menguatkan ukhuwah serta menjaga lingkungan agar tetap bersih, sehat, dan lestari.",
            'contact_label' => 'Pusat informasi',
            'contact_number' => '0811-500-822',
            'poster' => 'programs/sedekah-sampah.jpg',
            'poster_alt' => "Poster Sedekah Sampah Masjid Imam Syafi'i: menerima kemasan plastik, kertas dan kardus, serta minyak bekas setiap Ahad pukul 08.00 sampai 11.00 WITA di halaman masjid. Informasi 0811 500 822.",
            'source' => 'Poster resmi Sedekah Sampah, diterima 13 Okt 2026',
            'is_published' => true,
            'sort' => 2,
        ]);

        Program::firstOrCreate(['slug' => 'bbq'], [
            'title' => "Belajar Baca Qur'an (BBQ)",
            'category' => 'pendidikan',
            'tagline' => 'Kelas Iqro dan Kelas Tahsin, gratis dan terbuka untuk umum',
            'summary' => "Program belajar membaca Al-Qur'an yang diselenggarakan oleh Masjid Imam Syafi'i Banjarmasin, terdiri dari Kelas Iqro dan Kelas Tahsin.",
            'schedule' => "Setiap Ahad ba'da Maghrib",
            'days' => [0],
            'location' => "Ruang induk Masjid Imam Syafi'i Banjarmasin",
            'audience' => 'Muslim laki-laki, usia minimal 14 tahun',
            'organizer' => "Masjid Imam Syafi'i Banjarmasin bersama YP Al Umm Banjarmasin",
            'organizer_url' => 'https://www.instagram.com/ypalumm/',
            'is_free' => true,
            'items_title' => 'Pilihan kelas',
            'items' => [
                ['title' => 'BBQ Iqro', 'text' => "Belajar membaca Al-Qur'an untuk pemula menggunakan buku Iqro."],
                ['title' => 'BBQ Tahsin', 'text' => "Belajar membaca Al-Qur'an untuk yang sudah mulai mengenal ilmu tajwid, dengan latihan membaca surah-surah pendek."],
            ],
            'notes_title' => 'Syarat & ketentuan',
            'notes' => [
                'Laki-laki, usia minimal 14 tahun.',
                'Berkomitmen dan sungguh-sungguh mengikuti kegiatan.',
                'Mengikuti tata tertib dan ketentuan yang ditetapkan oleh Panitia BBQ.',
            ],
            'contact_label' => 'Info BBQ',
            'contact_number' => '0811-501-145',
            'poster' => 'programs/bbq.jpg',
            'poster_alt' => "Poster BBQ (Belajar Baca Qur'an) Masjid Imam Syafi'i: Kelas Iqro dan Kelas Tahsin setiap Ahad ba'da Maghrib di ruang induk masjid, untuk Muslim laki-laki minimal 14 tahun, gratis. Info 0811-501-145.",
            'source' => 'Poster resmi BBQ, diterima 13 Okt 2026',
            'is_published' => true,
            'sort' => 1,
        ]);

        Program::firstOrCreate(['slug' => 'umkm-mis'], [
            'title' => 'Stand Jualan UMKM MIS',
            'category' => 'ekonomi',
            'tagline' => 'Hadir kembali setiap Sabtu: aneka jajanan, sebagian keuntungan untuk kegiatan sosial masjid',
            'summary' => "Stand jualan UMKM MIS bersama Kemuslimahan Masjid Imam Syafi'i yang menjual aneka jajanan. Sebagian keuntungan dimanfaatkan untuk kegiatan sosial masjid.",
            'body' => '<p>Bagi yang ingin bekerja sama dengan UMKM MIS, silakan hubungi WhatsApp Kemuslimahan lewat tombol di halaman ini.</p>',
            'schedule' => 'Setiap Sabtu, mulai pukul 10.00 pagi',
            'days' => [6],
            'organizer' => "Kemuslimahan Masjid Imam Syafi'i Banjarmasin",
            'is_free' => false,
            'items_title' => 'Menu tersedia',
            'items' => [
                ['title' => 'Jajanan gurih', 'text' => 'Tahu bakso topping, dimsum, sempol, cilok, pentol kuah taichan, risoles, keripik singkong, kerupuk makaroni, snack aneka varian.'],
                ['title' => 'Kue', 'text' => 'Kue lapis, kue mochi, donat.'],
                ['title' => 'Minuman & es', 'text' => 'Es jeruk, es jelly, es mambo, es kecebong, thai tea.'],
            ],
            'notes_title' => 'Catatan',
            'notes' => ['Menu dapat berganti; tersedia juga aneka jajanan lainnya.'],
            'closing' => 'Mari kunjungi standnya dan beli dagangannya. Sebagian keuntungan dimanfaatkan untuk kegiatan sosial masjid.',
            'contact_label' => 'WA Kemuslimahan',
            'contact_number' => '0811-515-6464',
            'poster' => 'programs/umkm-mis.jpg',
            'poster_alt' => "Poster Stand Jualan UMKM MIS dari Kemuslimahan Masjid Imam Syafi'i: setiap Sabtu mulai pukul 10.00 pagi, menjual aneka jajanan seperti tahu bakso, dimsum, cilok, kue, donat, dan aneka es. Sebagian keuntungan untuk kegiatan sosial masjid. Kerja sama: WhatsApp Kemuslimahan 0811 515 6464.",
            'source' => 'Poster resmi UMKM MIS, diterima 13 Okt 2026',
            'is_published' => true,
            'sort' => 3,
        ]);

        // Layanan "Belajar Tahsin" kini punya rincian resmi (BBQ). Diperbarui hanya bila belum pernah diisi pengurus.
        $tahsin = Service::where('name', 'Belajar Tahsin')->where('status', 'menunggu')->whereNull('details')->first();
        $tahsin?->update([
            'summary' => "Kelas Iqro dan Tahsin dalam program BBQ (Belajar Baca Qur'an).",
            'schedule' => "Setiap Ahad ba'da Maghrib",
            'details' => '<p>Untuk Muslim laki-laki, usia minimal 14 tahun. Gratis. <a href="/program/bbq">Lihat rincian program BBQ</a>.</p>',
            'contact_label' => 'Info BBQ',
            'contact_number' => '0811-501-145',
            'status' => 'tersedia',
        ]);
    }
}
