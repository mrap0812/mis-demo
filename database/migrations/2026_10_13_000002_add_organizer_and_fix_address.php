<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Program bisa mencantumkan penyelenggara bersama (BBQ: bersama YP Al Umm Banjarmasin).
 * 2. Alamat masjid yang dikonfirmasi pengurus 13 Okt 2026: Jl. AMD XII, Pemurus Dalam.
 * Perubahan data hanya menimpa nilai lama yang persis sama, jadi suntingan admin tidak tertimpa.
 */
return new class extends Migration
{
    private const OLD_ADDRESS = 'Jl. Sepakat AMD Raya, Pemurus Dalam, Kec. Banjarmasin Selatan, Kota Banjarmasin, Kalimantan Selatan';

    private const NEW_ADDRESS = 'Jl. AMD XII, Pemurus Dalam, Kec. Banjarmasin Selatan, Kota Banjarmasin, Kalimantan Selatan';

    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('organizer', 160)->nullable()->after('audience');
            $table->string('organizer_url')->nullable()->after('organizer');
        });

        DB::table('programs')->where('slug', 'bbq')->whereNull('organizer')->update([
            'organizer' => "Masjid Imam Syafi'i Banjarmasin bersama YP Al Umm Banjarmasin",
            'organizer_url' => 'https://www.instagram.com/ypalumm/',
        ]);

        DB::table('settings')->where('key', 'address')->where('value', json_encode(self::OLD_ADDRESS))
            ->update(['value' => json_encode(self::NEW_ADDRESS)]);
        DB::table('cache')->where('key', 'like', '%site-settings')->delete();
    }

    public function down(): void
    {
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn(['organizer', 'organizer_url']));
    }
};
