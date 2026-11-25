<?php

use Database\Seeders\ProgramSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Menambahkan program dari poster resmi (UMKM MIS, dan program lain yang belum ada) ke situs yang sudah terpasang,
 * cukup lewat tombol "Jalankan migrasi". ProgramSeeder memakai firstOrCreate: program yang sudah ada tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ProgramSeeder)->run();
    }

    public function down(): void
    {
        // Data dibiarkan: menghapus program bisa dilakukan dari panel.
    }
};
