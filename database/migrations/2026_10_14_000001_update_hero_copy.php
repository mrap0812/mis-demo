<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Teks pembuka beranda diganti (arahan pengurus 14 Okt 2026): menonjolkan keilmiahan, keterbukaan, dan gratis.
 * Hanya menimpa nilai yang masih sama persis dengan teks lama, jadi suntingan admin tidak tertimpa.
 */
return new class extends Migration
{
    private const CHANGES = [
        'hero_title' => ["Menuntut ilmu syar'i, dekat dari rumah.", "Menuntut ilmu syar'i secara ilmiah dan terbuka untuk kaum muslimin."],
        'hero_sub' => [
            'Majelis ilmu setiap pekan bersama para asatidzah, membahas kitab-kitab para ulama. Gratis dan terbuka untuk umum, Muslim dan Muslimah.',
            'Kajian kitab-kitab para ulama setiap pekan bersama para asatidzah. Gratis, terbuka untuk Muslim dan Muslimah, disiarkan langsung dan diarsipkan sehingga bisa diikuti siapa saja.',
        ],
    ];

    public function up(): void
    {
        foreach (self::CHANGES as $key => [$old, $new]) {
            DB::table('settings')->where('key', $key)->where('value', json_encode($old))->update(['value' => json_encode($new)]);
        }
        DB::table('cache')->where('key', 'like', '%site-settings')->delete();
    }

    public function down(): void
    {
    }
};
