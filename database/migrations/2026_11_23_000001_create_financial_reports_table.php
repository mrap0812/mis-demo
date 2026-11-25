<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Laporan keuangan bulanan masjid. Nominal disimpan dalam rupiah bulat (tanpa sen). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $t) {
            $t->id();
            $t->date('period')->unique();                    // tanggal 1 bulan laporan
            $t->bigInteger('opening_balance')->default(0);
            $t->json('incomes');                             // [{label, note, amount}]
            $t->json('expenses');
            $t->string('poster')->nullable();                // poster resmi sebagai bukti
            $t->string('source_note', 255)->nullable();
            $t->string('status', 12)->default('draf')->index(); // draf | diajukan | terbit
            $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });

        // Data awal: hanya angka dari poster resmi Oktober 2026 (diperiksa: saldo akhir = awal + masuk - keluar).
        DB::table('financial_reports')->insert([
            'period' => '2026-10-01',
            'opening_balance' => 41228410,
            'incomes' => json_encode([
                ['label' => 'Infak transfer', 'note' => null, 'amount' => 21069715],
                ['label' => 'Infak tunai', 'note' => null, 'amount' => 28219000],
            ]),
            'expenses' => json_encode([
                ['label' => 'Mukafaah ustadz', 'note' => 'Kajian Islam ilmiah, khatib, imam, dan muadzin', 'amount' => 16350000],
                ['label' => 'Mukafaah pengajar BBQ', 'note' => "Belajar Baca Qur'an ikhwan & akhwat", 'amount' => 1575000],
                ['label' => 'Media informasi & dakwah', 'note' => null, 'amount' => 2000000],
                ['label' => 'Telekomunikasi', 'note' => null, 'amount' => 757700],
                ['label' => 'Konsumsi rutin', 'note' => 'Air mineral, dll.', 'amount' => 3517500],
                ['label' => 'Biaya rumah tangga', 'note' => 'Perbaikan, perawatan, perlengkapan', 'amount' => 3507670],
                ['label' => 'Gaji/honor SDM', 'note' => 'Sekretariat, tatalaksana, security, dan tenaga kerja lainnya', 'amount' => 19505000],
            ]),
            'source_note' => 'Disalin dari poster resmi "Laporan Keuangan Masjid Imam Syafi\'i Banjarmasin bulan Oktober 2026".',
            'status' => 'terbit',                        // sudah diumumkan resmi lewat poster; tidak melalui persetujuan di sistem
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
