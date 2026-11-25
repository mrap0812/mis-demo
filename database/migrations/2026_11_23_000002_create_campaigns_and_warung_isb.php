<?php

use App\Models\Program;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kampanye donasi (target dana atau hitung mundur Ramadhan) dan program Warung ISB.
 * Isi diambil apa adanya dari poster resmi yang diterima 23 November 2026.
 * Rekening kampanye TIDAK langsung tayang: diajukan dan wajib disetujui bendahara/admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('title', 120);
            $t->string('slug', 80)->unique();
            $t->string('kind', 12)->default('target');          // target | ramadhan
            $t->text('summary')->nullable();
            $t->bigInteger('target_amount')->nullable();
            $t->bigInteger('collected_amount')->default(0);
            $t->date('data_date')->nullable();                   // angka terkumpul per tanggal ini
            $t->unsignedSmallInteger('hijri_year')->nullable();  // untuk hitung mundur Ramadhan
            $t->string('quote', 300)->nullable();
            $t->string('quote_ref', 80)->nullable();
            $t->foreignId('donation_account_id')->nullable()->constrained()->nullOnDelete();
            $t->string('poster')->nullable();
            $t->string('poster_alt', 300)->nullable();
            $t->string('source', 160)->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        $now = now();
        $rows = [
            [
                'title' => 'Renovasi Toilet Ikhwan', 'slug' => 'renovasi-toilet-ikhwan', 'kind' => 'target',
                'summary' => 'Pondasi dan bangunan baru, 10 pintu toilet, pantry dan optimalisasi dak atas, serta 1 pintu toilet ramah disabilitas. Bersama Yayasan Al Umm Banjarmasin.',
                'target_amount' => 156000000, 'collected_amount' => 10071001, 'data_date' => '2026-11-20',
                'hijri_year' => null, 'quote' => null, 'quote_ref' => null,
                'poster' => 'kampanye/renovasi-toilet-ikhwan.jpg',
                'poster_alt' => "Poster update donasi Renovasi Toilet Ikhwan Masjid Imam Syafi'i per 20 November 2026: donasi diterima Rp 10.071.001, masih diperlukan Rp 145.928.999.",
                'source' => 'Sumber: poster resmi update donasi.', 'sort' => 1,
            ],
            [
                'title' => 'Menuju Ramadhan 1448 H', 'slug' => 'ramadhan-1448', 'kind' => 'ramadhan',
                'summary' => 'Salurkan donasi terbaik untuk kegiatan Ramadhan di Masjid Imam Syafi\'i dari sekarang.',
                'target_amount' => null, 'collected_amount' => 0, 'data_date' => null,
                'hijri_year' => 1448,
                'quote' => 'Hai orang-orang yang beriman, belanjakanlah (di jalan Allah) sebagian dari rezki yang telah Kami berikan kepadamu.',
                'quote_ref' => 'QS. Al-Baqarah: 254',
                'poster' => 'kampanye/menuju-ramadhan-1448.jpg',
                'poster_alt' => "Poster Panitia Ramadhan Masjid Imam Syafi'i: hitung mundur menuju Ramadhan 1448 H dan ajakan berdonasi.",
                'source' => 'Sumber: poster resmi Panitia Ramadhan 1448 H.', 'sort' => 2,
            ],
        ];
        DB::table('campaigns')->insert(array_map(fn ($r) => $r + ['is_active' => true, 'donation_account_id' => null, 'created_at' => $now, 'updated_at' => $now], $rows));

        // Rekening kampanye diajukan lewat SiteContentSeeder (setelah rekening operasional), bukan di sini.

        if (! Program::where('slug', 'warung-isb')->exists()) {
            Program::create([
                'slug' => 'warung-isb', 'title' => 'Warung ISB', 'category' => 'ekonomi',
                'summary' => "Warung jajanan dan minuman yang dikelola Masjid Imam Syafi'i Banjarmasin.",
                'schedule' => 'Buka mulai Senin, 23 November 2026, pukul 07.00 WITA sampai selesai',
                'location' => "Depan Rumah Dakwah Imam Syafi'i Banjarmasin",
                'is_free' => false,
                'contact_label' => 'Iqbal', 'contact_number' => '0877-8788-8122',
                'poster' => 'programs/warung-isb.jpg',
                'poster_alt' => "Poster Warung ISB: jajanan dan minuman dingin, buka mulai Senin 23 November 2026 pukul 07.00 WITA di depan Rumah Dakwah Imam Syafi'i Banjarmasin. Hubungi Iqbal 0877-8788-8122.",
                'source' => 'Poster resmi Warung ISB, diterima 23 Nov 2026',
                'is_published' => true, 'sort' => 4,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
        Program::where('slug', 'warung-isb')->delete();
    }
};
