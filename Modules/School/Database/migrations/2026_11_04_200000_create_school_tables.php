<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data sekolah. Semua tabel: gambar opsional (FK ke media_assets), urutan tampil, dan status aktif.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 16)->default('teacher'); // teacher | staff
            $table->string('position')->nullable();
            $table->string('subject')->nullable();
            $table->string('nip', 32)->nullable();
            $table->text('bio')->nullable();
            $this->common($table);
        });

        Schema::create('school_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('level', 16)->default('school');
            $table->string('winner')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $this->common($table);
        });

        Schema::create('school_extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('coach')->nullable();
            $table->string('schedule')->nullable();
            $table->text('description')->nullable();
            $this->common($table);
        });

        Schema::create('school_majors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 16)->nullable();
            $table->text('description')->nullable();
            $this->common($table);
        });

        Schema::create('school_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level', 16)->nullable();
            $table->string('npsn', 16)->nullable();
            $table->string('principal')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('url', 2048)->nullable();
            $this->common($table);
        });
    }

    public function down(): void
    {
        foreach (['school_units', 'school_majors', 'school_extracurriculars', 'school_achievements', 'school_staff'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function common(Blueprint $table): void
    {
        $table->foreignId('image_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
        $table->unsignedInteger('order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->index(['is_active', 'order']); // daftar publik: aktif, urut
    }
};
