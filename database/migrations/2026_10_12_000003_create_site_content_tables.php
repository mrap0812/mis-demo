<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan situs: satu baris per kunci. Kunci yang belum pernah disimpan memakai nilai bawaan di App\Models\Setting.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Halaman statis: Profil dan halaman tambahan (sejarah, fasilitas, dan sebagainya).
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title');
            $table->text('lede')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Layanan jamaah.
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->string('icon', 24)->default('heart');
            $table->string('summary', 240);
            $table->longText('details')->nullable();
            $table->string('schedule')->nullable();
            $table->string('contact_label')->nullable();
            $table->string('contact_number', 24)->nullable();
            $table->string('registration_url')->nullable();
            $table->string('status', 12)->default('menunggu'); // tersedia | menunggu
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
    }
};
