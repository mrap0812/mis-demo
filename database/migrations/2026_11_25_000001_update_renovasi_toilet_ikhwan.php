<?php

use App\Models\Campaign;
use Illuminate\Database\Migrations\Migration;

/**
 * Kampanye toilet diganti dengan poster "Renovasi Total Toilet Ikhwan" (diterima 25 November 2026).
 * Poster baru tidak memuat angka target atau dana terkumpul, jadi angka lama (per 20 Nov) dikosongkan
 * agar tidak tampil sebagai data terkini. Pengurus bisa mengisinya lagi di admin bila ada pembaruan resmi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Campaign::where('slug', 'renovasi-toilet-ikhwan')->first()?->update([
            'title' => 'Renovasi Total Toilet Ikhwan',
            'summary' => 'Upgrade renovasi: pondasi dan bangunan baru, 10 pintu toilet, pantry dan optimalisasi dak atas, serta toilet ramah disabilitas (1 pintu).',
            'target_amount' => null, 'collected_amount' => 0, 'data_date' => null,
            'quote' => 'Karena setiap kemudahan untuk berwudhu dan bersuci, akan menjadi amal jariyah yang terus mengalir.',
            'quote_ref' => null,
            'poster' => 'kampanye/renovasi-total-toilet-ikhwan.jpg',
            'poster_alt' => "Poster Renovasi Total Toilet Ikhwan Masjid Imam Syafi'i Banjarmasin: raih pahala jariyah dengan memfasilitasi tempat bersuci. Upgrade renovasi: pondasi dan bangunan baru, 10 pintu toilet, pantry dan optimalisasi dak atas, toilet ramah disabilitas 1 pintu. Donasi melalui QRIS atau rekening BSI yang tertera di poster.",
            'source' => 'Sumber: poster resmi Renovasi Total Toilet Ikhwan.',
        ]);
    }

    public function down(): void {}
};
