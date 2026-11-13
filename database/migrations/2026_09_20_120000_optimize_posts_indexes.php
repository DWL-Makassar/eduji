<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Hasil pengukuran (MySQL 8.0, 30.000 berita):
|
| - Daftar publik (beranda, /berita, /pengumuman) memakai WHERE type, status + ORDER BY is_pinned DESC, published_at DESC
|   LIMIT n. Indeks (type, status, published_at) tidak bisa melayani urutan itu (is_pinned di depan) sehingga MySQL memilih
|   unique(type, slug) lalu filesort: ±140 ms per query. Indeks di bawah ini membuatnya ±0,6 ms (Backward index scan).
|   Ubah urutan query dan indeks ini BERSAMAAN (PostController::index, DefaultSections).
| - FULLTEXT(title, excerpt) dari migrasi awal tidak pernah dipakai: pencarian memakai LIKE (85–215 ms pada 30.000 baris,
|   cukup) dan FULLTEXT tidak jelas lebih cepat serta mengubah makna pencarian ("dua kata" → AND per kata, kata pendek
|   lebih lambat). Indeks yang tak terpakai hanya membebani tulis dan ruang, jadi dibuang.
*/
return new class extends Migration
{
    private const FULLTEXT = 'posts_title_excerpt_fulltext';

    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['type', 'status', 'is_pinned', 'published_at'], 'posts_public_list_index');
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasIndex('posts', self::FULLTEXT)) {
            Schema::table('posts', fn (Blueprint $table) => $table->dropFullText(self::FULLTEXT));
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_public_list_index');
        });

        if (DB::getDriverName() === 'mysql' && ! Schema::hasIndex('posts', self::FULLTEXT)) {
            Schema::table('posts', fn (Blueprint $table) => $table->fullText(['title', 'excerpt'], self::FULLTEXT));
        }
    }
};
