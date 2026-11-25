<?php

use App\Models\KajianSeries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Warna kartu kajian lama (hijau, ungu, biru, dll.) dipetakan ke palet situs: merah, merah tua, emas. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('kajian_series')->select('id', 'color')->get() as $row) {
            $tone = KajianSeries::tone($row->color);
            if ($tone !== $row->color) {
                DB::table('kajian_series')->where('id', $row->id)->update(['color' => $tone]);
            }
        }
    }

    public function down(): void {}
};
