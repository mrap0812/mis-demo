<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rekening yang tercetak di poster resmi kampanye, ditampilkan apa adanya (atas keputusan pengurus, 25 Nov 2026).
 * Bila rekening tertaut (DonationAccount) sudah disetujui, yang tertaut itu yang dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $t) {
            $t->string('poster_bank', 60)->nullable()->after('donation_account_id');
            $t->string('poster_account_number', 40)->nullable()->after('poster_bank');
            $t->string('poster_account_holder', 100)->nullable()->after('poster_account_number');
        });

        DB::table('campaigns')->where('slug', 'renovasi-toilet-ikhwan')->update([
            'poster_bank' => 'BSI', 'poster_account_number' => '7700 6000 62', 'poster_account_holder' => "Sosial Imam Syafi'i",
        ]);
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn(['poster_bank', 'poster_account_number', 'poster_account_holder']));
    }
};
