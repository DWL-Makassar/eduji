<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alumni. Email & telepon hanya untuk admin (tidak pernah tampil di publik). Kiriman dari formulir publik masuk nonaktif.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_alumni', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('activity')->nullable(); // mis. "Mahasiswa", "Guru", "Wirausaha"
            $table->string('institution')->nullable();
            $table->text('testimony')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'graduation_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_alumni');
    }
};
