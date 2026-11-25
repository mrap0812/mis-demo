<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Judul beranda versi akhir (arahan pengurus 14 Okt 2026). Hanya menimpa teks lama yang belum diubah admin. */
return new class extends Migration
{
    public function up(): void
    {
        $new = 'Menuntut ilmu secara ilmiah dan terbuka untuk kaum muslimin.';
        foreach (["Menuntut ilmu syar'i, dekat dari rumah.", "Menuntut ilmu syar'i secara ilmiah dan terbuka untuk kaum muslimin."] as $old) {
            DB::table('settings')->where('key', 'hero_title')->where('value', json_encode($old))->update(['value' => json_encode($new)]);
        }
        DB::table('cache')->where('key', 'like', '%site-settings')->delete();
    }

    public function down(): void
    {
    }
};
