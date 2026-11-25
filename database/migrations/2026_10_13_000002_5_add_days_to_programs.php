<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hari pelaksanaan program dalam bentuk data (0 = Ahad ... 6 = Sabtu), agar situs bisa menandai "Hari ini" / "Besok".
 * Teks jadwal tetap dipakai untuk tampilan; kolom ini hanya untuk penanda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', fn (Blueprint $t) => $t->json('days')->nullable()->after('schedule'));

        foreach (['bbq' => [0], 'sedekah-sampah' => [0], 'umkm-mis' => [6]] as $slug => $days) {
            DB::table('programs')->where('slug', $slug)->whereNull('days')->update(['days' => json_encode($days)]);
        }
    }

    public function down(): void
    {
        Schema::table('programs', fn (Blueprint $t) => $t->dropColumn('days'));
    }
};
