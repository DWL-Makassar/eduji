<?php

declare(strict_types=1);

namespace Modules\School\Database\Seeders;

use App\Cms\Events\Event;
use App\Cms\Features\Feature;
use App\Cms\Gallery\Gallery;
use App\Cms\Pages\Page;
use App\Cms\Posts\Post;
use App\Core\Media\MediaAsset;
use App\Core\Settings\SettingsStore;
use App\Core\Support\Sanitizer;
use App\Core\Users\User;
use App\Website\Sections\PageBlock;

/**
 * Isi awal untuk instalasi baru (dipanggil `migrate --seed` bila situs belum punya menu): menu sekolah, halaman profil
 * berisi teks pengganti, slider beranda, satu berita/pengumuman/agenda/galeri, dan beranda tersusun. Tanpa data orang fiktif,
 * jadi aman di production; sekolah tinggal mengganti teksnya dari panel admin. Menu & slider sama dengan situs demo.
 */
class StarterSeeder extends SchoolDemoSeeder
{
    private const FILL = '<p>Tulis isi halaman ini dari panel admin: menu <strong>Halaman</strong>, lalu klik Edit.</p>';

    public function run(SettingsStore $settings): void
    {
        $this->userId = User::query()->value('id');

        $this->pages();
        $this->menus();
        $this->slider();
        $this->starterContent();
        $this->starterHome();
        $settings->set('website', 'home_built', true);
    }

    protected function pages(): void
    {
        foreach ([
            ['Profil & Sejarah', 'tentang-kami'], ['Visi & Misi', 'visi-misi'], ['Struktur Organisasi', 'struktur-organisasi'],
            ['Sarana & Prasarana', 'sarana-prasarana'], ['Perpustakaan', 'perpustakaan'], ['Tata Tertib', 'tata-tertib'],
            ['Informasi Kurikulum', 'informasi-kurikulum'], ['Kebijakan Privasi', 'kebijakan-privasi'],
        ] as [$title, $slug]) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'body' => Sanitizer::html(self::FILL), 'status' => 'published', 'published_at' => now()]);
        }
    }

    private function starterContent(): void
    {
        $post = fn (string $type, string $slug, string $title, string $excerpt, ?int $cover = null) => Post::firstOrCreate(['type' => $type, 'slug' => $slug], [
            'title' => $title, 'author_id' => $this->userId, 'excerpt' => $excerpt, 'cover_media_id' => $cover,
            'body' => Sanitizer::html("<p>{$excerpt}</p><p>Ganti atau hapus tulisan ini dari panel admin.</p>"),
            'status' => 'published', 'published_at' => now(),
        ]);
        $post(Post::TYPE_POST, 'selamat-datang', 'Selamat datang di website sekolah kami', 'Website ini menjadi tempat kami berbagi kabar, kegiatan, dan prestasi sekolah.', $this->stockPhoto('Gedung sekolah', 'banner-gedung-sekolah.jpg')->id);
        $post(Post::TYPE_ANNOUNCEMENT, 'info-website-baru', 'Website sekolah resmi diluncurkan', 'Ikuti pengumuman terbaru sekolah melalui halaman ini.');

        $day = now()->addWeek()->setTime(8, 0);
        Event::firstOrCreate(['slug' => 'contoh-agenda-sekolah'], [
            'title' => 'Contoh agenda sekolah', 'description' => 'Ganti dengan agenda sekolah Anda dari menu Agenda.', 'location' => 'Sekolah',
            'starts_at' => $day, 'ends_at' => $day->copy()->setTime(12, 0), 'is_active' => true,
        ]);

        foreach ([['Gedung sekolah', 'banner-gedung-sekolah.jpg'], ['Perpustakaan', 'banner-perpustakaan.jpg'], ['Kegiatan belajar', 'banner-siswa-digital.jpg']] as $n => [$title, $file]) {
            $gallery = Gallery::firstOrCreate(['slug' => str($title)->slug()->value()], ['title' => $title, 'description' => 'Contoh album. Ganti dengan foto sekolah dari menu Galeri.', 'status' => 'published']);
            if (! $gallery->items()->exists()) {
                $gallery->items()->create(['media_asset_id' => $this->stockPhoto($title, $file)->id, 'caption' => $title, 'order' => 0]);
            }
        }

        foreach ([['Kurikulum Merdeka', 'Pembelajaran yang berpusat pada siswa.', 'star'], ['Guru berpengalaman', 'Tenaga pendidik yang kompeten dan peduli.', 'users'], ['Lingkungan nyaman', 'Sarana belajar yang aman dan bersih.', 'shield']] as $order => [$title, $description, $icon]) {
            Feature::firstOrCreate(['title' => $title], ['description' => $description, 'icon' => $icon, 'order' => $order, 'is_active' => true]);
        }
    }

    /** Foto stok dari folder demo/ (disalin dulu: pustaka media memindahkan berkas sumber); dipakai ulang per judul. */
    private function stockPhoto(string $title, string $file): MediaAsset
    {
        return $this->asset("Contoh: {$title}", function () use ($file): string {
            $path = tempnam(sys_get_temp_dir(), 'starter');
            copy(__DIR__."/demo/{$file}", $path);

            return $path;
        }, $file);
    }

    private function starterHome(): void
    {
        $blocks = [
            ['hero'],
            ['welcome', 'default', ['title' => 'Sambutan Kepala Sekolah', 'name' => 'Nama Kepala Sekolah', 'role' => 'Kepala Sekolah',
                'body' => '<p>Selamat datang di website resmi sekolah kami. Melalui website ini kami berbagi kabar, prestasi, dan informasi penting bagi siswa, orang tua, dan masyarakat.</p>']],
            ['stats', 'band', ['items' => [['value' => '650+', 'label' => 'Siswa aktif'], ['value' => '48', 'label' => 'Guru & tenaga kependidikan'], ['value' => '20+', 'label' => 'Ekstrakurikuler'], ['value' => 'A', 'label' => 'Akreditasi']]]],
            ['features', 'default', ['title' => 'Program Unggulan']],
            ['announcements'],
            ['posts', 'default', ['title' => 'Berita Terbaru', 'limit' => 5]],
            ['events', 'default', ['title' => 'Agenda Sekolah', 'limit' => 4]],
            ['galleries', 'default', ['title' => 'Galeri Kegiatan', 'limit' => 4]],
            ['cta', 'default', ['title' => 'Ingin tahu lebih banyak tentang sekolah kami?', 'text' => 'Hubungi kami atau kunjungi sekolah secara langsung.', 'button_label' => 'Hubungi kami', 'button_url' => '/kontak']],
            ['map', 'default', ['title' => 'Lokasi & kontak']],
        ];

        PageBlock::query()->whereNull('page_id')->delete();
        foreach ($blocks as $position => $block) {
            PageBlock::create(['type' => $block[0], 'variant' => $block[1] ?? 'default', 'settings' => $block[2] ?? [], 'position' => $position, 'is_visible' => true]);
        }
    }
}
