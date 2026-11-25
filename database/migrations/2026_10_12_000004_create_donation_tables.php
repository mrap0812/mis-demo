<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rekening dan QRIS yang tampil ke publik. Baris di sini hanya berubah lewat pengajuan yang disetujui orang kedua.
        Schema::create('donation_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10);                    // rekening | qris
            $table->string('purpose', 120);                // peruntukan: operasional, pembangunan, kurban, ...
            $table->string('bank_name', 80)->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('account_holder', 120);
            $table->string('qris_image')->nullable();
            $table->string('qris_merchant', 120)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('donation_account_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 10);                  // create | update | retire
            $table->json('payload')->nullable();
            $table->string('source')->nullable();          // dasar perubahan: surat bendahara, poster resmi, ...
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('menunggu'); // menunggu | disetujui | ditolak | dibatalkan
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'donation_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_account_changes');
        Schema::dropIfExists('donation_accounts');
    }
};
