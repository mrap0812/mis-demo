<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use App\Models\DonationAccount;
use App\Models\DonationAccountChange;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Isi awal halaman Profil dan Layanan. Hanya memuat fakta yang tercantum di mis.id dan kanal resmi;
 * bagian yang belum ditetapkan pengurus sengaja dikosongkan, bukan diisi perkiraan.
 * firstOrCreate: menjalankan ulang seeder tidak menimpa suntingan admin.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        ContentPage::firstOrCreate(['slug' => 'profil'], [
            'title' => "Masjid Imam Syafi'i Banjarmasin",
            'lede' => 'Diresmikan 1 Muharram 1435 H / 5 November 2013. Dikelola Dewan Kemakmuran Masjid sebagai pusat kajian Islam ilmiah di Banjarmasin Selatan.',
            'body' => "<p>Masjid Imam Syafi'i Banjarmasin diresmikan pada 1 Muharram 1435 H, bertepatan dengan 5 November 2013, oleh Drs. H. Rudy Ariffin, M.B.A. Masjid ini dikelola oleh Dewan Kemakmuran Masjid (DKM).</p>"
                ."<p>Kajian rutin diadakan setiap pekan ba'da Subuh, Ashar, dan Maghrib, membahas kitab-kitab para ulama. Kajian gratis dan terbuka untuk umum, Muslim dan Muslimah. Rekaman kajian dan khutbah Jumat dapat disimak ulang di kanal YouTube masjid.</p>"
                .'<p>Selain majelis ilmu, masjid juga menyelenggarakan kegiatan sosial seperti penyaluran hewan kurban dan bantuan MIS-Peduli.</p>',
            'is_published' => true,
        ]);

        // Lima layanan yang tercantum di mis.id. Syarat, jam, dan petugas belum ditetapkan, jadi berstatus "menunggu".
        foreach ([
            ['Belajar Tahsin', 'book', "Perbaikan bacaan Al-Qur'an."],
            ['Ambulans', 'ambulance', 'Layanan ambulans masjid.'],
            ['Pengurusan Jenazah', 'shroud', 'Bantuan pengurusan jenazah.'],
            ['Bantu Membantu', 'heart', 'Program saling bantu jamaah.'],
            ['Donor Darah', 'drop', 'Kegiatan donor darah berkala.'],
        ] as $i => [$name, $icon, $summary]) {
            Service::firstOrCreate(['name' => $name], ['icon' => $icon, 'summary' => $summary, 'status' => 'menunggu', 'is_active' => true, 'sort' => $i + 1]);
        }

        // Rekening dari poster resmi diajukan sebagai PENGAJUAN, bukan langsung tayang: wajib diperiksa dan disetujui bendahara/admin.
        if (! DonationAccount::exists() && ! DonationAccountChange::exists()) {
            DonationAccountChange::propose(null, 'create', null, [
                'kind' => 'rekening', 'purpose' => 'Infak operasional masjid', 'bank_name' => 'BSI (Bank Syariah Indonesia)',
                'account_number' => '400 4000 949', 'account_holder' => "Imam Syafi'i", 'sort' => 1,
            ], 'Poster resmi @kajianmisbjm (perlu dicocokkan dengan buku rekening oleh bendahara)');
        }

        // Rekening kampanye dari poster 23 Nov 2026 (renovasi toilet ikhwan, dana Ramadhan): juga hanya diajukan.
        foreach ([
            ['purpose' => 'Renovasi toilet ikhwan', 'account_number' => '7700 6000 62', 'account_holder' => "Sosial Imam Syafi'i", 'sort' => 10],
            ['purpose' => 'Dana Ramadhan 1448 H', 'account_number' => '7300 7200 26', 'account_holder' => "Ramadhan Imam Syafi'i", 'sort' => 11],
        ] as $p) {
            if (! DonationAccount::where('account_number', $p['account_number'])->exists()
                && ! DonationAccountChange::where('payload', 'like', '%'.$p['account_number'].'%')->exists()) {
                DonationAccountChange::propose(null, 'create', null, $p + ['kind' => 'rekening', 'bank_name' => 'BSI (Bank Syariah Indonesia)'],
                    'Poster resmi (diterima 23 Nov 2026); cocokkan dengan buku rekening sebelum disetujui');
            }
        }
    }
}
