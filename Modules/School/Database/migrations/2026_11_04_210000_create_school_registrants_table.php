<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pendaftar SPMB. Berisi data pribadi anak: berkasnya disimpan di disk privat (`local`), bukan `public`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_registrants', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->nullable()->unique(); // diisi setelah insert (berdasarkan id)
            $table->string('academic_year', 16)->nullable();
            $table->string('full_name');
            $table->string('nisn', 10)->nullable();
            $table->char('gender', 1); // L | P
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('previous_school');
            $table->text('address');
            $table->string('parent_name');
            $table->string('parent_phone', 32);
            $table->string('email')->nullable();
            $table->foreignId('major_id')->nullable()->constrained('school_majors')->nullOnDelete();
            $table->string('status', 16)->default('submitted');
            $table->text('note')->nullable(); // catatan panitia, terlihat oleh pendaftar saat cek status
            $table->json('documents')->nullable(); // jenis berkas => path di disk local
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_registrants');
    }
};
