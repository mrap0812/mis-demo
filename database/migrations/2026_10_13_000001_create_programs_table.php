<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Program masjid (Sedekah Sampah, BBQ, dan seterusnya): kegiatan rutin di luar jadwal kajian. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 80)->unique();
            $table->string('category', 16)->default('sosial');
            $table->string('tagline', 160)->nullable();
            $table->string('summary', 300);
            $table->longText('body')->nullable();
            $table->string('schedule', 120)->nullable();
            $table->string('location', 160)->nullable();
            $table->string('audience', 160)->nullable();
            $table->boolean('is_free')->default(true);
            $table->string('items_title', 80)->nullable();
            $table->json('items')->nullable();      // [{title, text}]
            $table->string('notes_title', 80)->nullable();
            $table->json('notes')->nullable();      // ["..."]
            $table->json('steps')->nullable();      // ["..."]
            $table->string('closing', 400)->nullable();
            $table->string('contact_label', 60)->nullable();
            $table->string('contact_number', 24)->nullable();
            $table->string('registration_url')->nullable();
            $table->string('poster')->nullable();
            $table->string('poster_alt', 300)->nullable();
            $table->string('source', 200)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
