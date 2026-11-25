<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | editor | peninjau  (lihat App\Enums\Role)
            $table->string('role', 20)->default('editor')->after('email');
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // nama lengkap dengan gelar, sesuai poster
            $table->string('short_name')->nullable(); // untuk tampilan ringkas
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kajian_series', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // slug stabil, dipakai di URL pertemuan
            $table->string('title');
            $table->string('kitab')->nullable();
            $table->string('kitab_ar')->nullable();  // judul kitab berhuruf Arab
            $table->string('category', 20);          // kajian | khutbah | muslimah | berkala
            $table->string('slot', 10);              // subuh | ashar | maghrib | jumat
            $table->unsignedTinyInteger('day_of_week'); // 0 = Ahad ... 6 = Sabtu
            $table->json('weeks')->nullable();          // pekan ke-n dalam bulan Masehi; null = setiap pekan
            $table->json('except_weeks')->nullable();   // pekan yang diganti kajian berkala
            $table->string('badge')->nullable();
            $table->string('color', 9)->default('#7A1218');
            $table->foreignId('speaker_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_tentative')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('playlist_url')->nullable();
            $table->string('source')->nullable();    // asal data, mis. "Poster jadwal Shafar 1448 H"
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('kajian_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kajian_series_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('title')->nullable();     // judul pertemuan; kosong = judul seri
            $table->foreignId('speaker_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 12)->default('terjadwal'); // terjadwal | ditunda | dibatalkan
            $table->string('status_reason')->nullable();
            $table->string('poster')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 12)->default('admin'); // poster | admin
            $table->timestamps();
            $table->unique(['kajian_series_id', 'date']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12);              // berita | pengumuman | artikel
            $table->string('category')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover')->nullable();
            $table->string('status', 14)->default('draf'); // lihat App\Enums\PostStatus
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('kajian_sessions');
        Schema::dropIfExists('kajian_series');
        Schema::dropIfExists('speakers');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
