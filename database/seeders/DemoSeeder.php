<?php

namespace Database\Seeders;

use App\Cms\Categories\Category;
use App\Cms\Contact\ContactMessage;
use App\Cms\Downloads\Download;
use App\Cms\Events\Event;
use App\Cms\Features\Feature;
use App\Cms\Gallery\Gallery;
use App\Cms\Menus\Menu;
use App\Cms\Pages\Page;
use App\Cms\Posts\Post;
use App\Cms\Sliders\Slider;
use App\Cms\Tags\Tag;
use App\Cms\Testimonials\Testimonial;
use App\Core\Media\MediaAsset;
use App\Core\Settings\SettingsStore;
use App\Core\Support\Sanitizer;
use App\Core\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Example\Models\Item;

/**
 * Data contoh agar situs publik dan admin terlihat terisi (bukan bagian dari `migrate --seed`):
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Aman dijalankan berulang (mengisi ulang berdasarkan slug/kode/judul, tidak menggandakan). Gambar dibuat dengan GD
 * (gradasi warna), berkas unduhan berupa PDF kecil. Menolak berjalan di production kecuali DEMO_MODE=true. Data contoh bersifat netral
 * (bukan khusus satu domain). Item modul Example ikut dibuat bila tabelnya ada.
 */
class DemoSeeder extends Seeder
{
    protected ?int $userId = null;

    public function run(SettingsStore $settings): void
    {
        // Situs demo publik berjalan di production dengan DEMO_MODE=true; selain itu data contoh tidak boleh masuk production.
        if (app()->isProduction() && ! config('platform.demo.enabled')) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan di production (kecuali DEMO_MODE=true).');

            return;
        }

        $this->userId = User::query()->value('id');

        $this->settings($settings);
        $this->posts();
        $this->pages();
        $this->menus();
        $this->slider();
        $this->features();
        $this->testimonials();
        $this->events();
        $this->galleries();
        $this->downloads();
        $this->messages();
        $this->exampleItems();

        $this->command?->info('Data contoh siap: buka beranda, /berita, /agenda, /galeri, /unduhan, dan Admin.');
    }

    protected function settings(SettingsStore $settings): void
    {
        $settings->setMany('general', [
            'site_name' => 'Demo Master Source',
            'tagline' => 'Fondasi website untuk sistem apa saja',
            'email' => 'halo@example.com',
            'phone' => '+62 21 5550 0000',
            'address' => 'Jl. Contoh No. 123, Jakarta',
        ]);
        $settings->set('seo', 'description', 'Situs demo Master Source: berita, agenda, galeri, dan modul yang bisa dikembangkan ke sistem apa saja.');
    }

    protected function posts(): void
    {
        $categories = collect([['Kegiatan', 'kegiatan'], ['Panduan', 'panduan']])
            ->mapWithKeys(fn (array $c, int $i) => [$c[1] => Category::updateOrCreate(['type' => 'post', 'slug' => $c[1]], ['name' => $c[0], 'order' => $i])]);

        $tags = collect([['Rilis', 'rilis'], ['Tips', 'tips'], ['Komunitas', 'komunitas']])
            ->mapWithKeys(fn (array $t) => [$t[1] => Tag::updateOrCreate(['slug' => $t[1]], ['name' => $t[0]])]);

        // [judul, slug, kategori, tag, hari lalu terbit (null = draf), sematkan]
        $news = [
            ['Peluncuran Platform Master Source', 'peluncuran-platform', 'kegiatan', ['rilis'], 1, true],
            ['Tips Mengelola Konten dengan Cepat', 'tips-mengelola-konten', 'panduan', ['tips'], 3, false],
            ['Rangkuman Kegiatan Bulan Ini', 'rangkuman-kegiatan', 'kegiatan', ['komunitas'], 6, false],
            ['Kolaborasi dengan Mitra Baru', 'kolaborasi-mitra-baru', 'kegiatan', ['komunitas', 'rilis'], 9, false],
            ['Cara Menyusun Menu dan Beranda', 'menyusun-menu-beranda', 'panduan', ['tips'], 12, false],
            ['Draf yang Belum Terbit', 'draf-belum-terbit', 'panduan', [], null, false],
        ];

        foreach ($news as $i => [$title, $slug, $category, $tagKeys, $daysAgo, $pinned]) {
            $post = Post::updateOrCreate(['type' => 'post', 'slug' => $slug], [
                'title' => $title,
                'category_id' => $categories[$category]->id,
                'author_id' => $this->userId,
                'cover_media_id' => $this->image("Demo: {$title}", $slug)->id,
                'excerpt' => "Ringkasan singkat untuk “{$title}”.",
                'body' => Sanitizer::html("<p>Ini contoh isi berita <strong>{$title}</strong>. Teks ini dibuat oleh DemoSeeder untuk memperlihatkan tampilan artikel.</p><h2>Poin penting</h2><ul><li>Mudah dikelola dari admin</li><li>Rapi di layar kecil maupun besar</li><li>Siap untuk mesin pencari</li></ul><p>Anda dapat mengubah atau menghapus data contoh ini kapan saja.</p>"),
                'status' => $daysAgo === null ? 'draft' : 'published',
                'published_at' => $daysAgo === null ? null : now()->subDays($daysAgo),
                'is_pinned' => $pinned,
            ]);
            $post->tags()->sync(collect($tagKeys)->map(fn (string $key) => $tags[$key]->id)->all());
        }

        // Terjadwal: baru tayang di masa depan (tidak tampil di situs).
        Post::updateOrCreate(['type' => 'post', 'slug' => 'berita-terjadwal'], [
            'title' => 'Berita Terjadwal', 'author_id' => $this->userId, 'body' => '<p>Belum tayang.</p>',
            'status' => 'published', 'published_at' => now()->addWeek(),
        ]);

        // [judul, slug, kedaluwarsa]: yang terakhir sudah kedaluwarsa (tidak tampil).
        foreach ([
            ['Jadwal Pemeliharaan Sistem', 'jadwal-pemeliharaan', null],
            ['Pendaftaran Kegiatan Dibuka', 'pendaftaran-kegiatan', now()->addDays(30)],
            ['Pengumuman yang Sudah Berakhir', 'pengumuman-berakhir', now()->subDays(2)],
        ] as $i => [$title, $slug, $expires]) {
            Post::updateOrCreate(['type' => 'announcement', 'slug' => $slug], [
                'title' => $title, 'author_id' => $this->userId, 'body' => Sanitizer::html("<p>{$title}. Ini pengumuman contoh.</p>"),
                'status' => 'published', 'published_at' => now()->subDays($i + 1), 'expires_at' => $expires,
            ]);
        }
    }

    protected function pages(): void
    {
        foreach ([
            ['Tentang Kami', 'tentang-kami', 'published', '<p>Demo Master Source adalah contoh situs di atas fondasi <strong>Master Source</strong>: CMS, situs publik, SEO, tema, dan sistem modul.</p><h2>Prinsip</h2><p>Satu fondasi, banyak sistem: fitur khusus ditambahkan sebagai modul tanpa mengubah inti.</p>'],
            ['Visi dan Misi', 'visi-misi', 'published', '<h2>Visi</h2><p>Menjadi fondasi yang mudah dipakai ulang.</p><h2>Misi</h2><ol><li>Kode yang rapi dan terukur</li><li>Aman secara bawaan</li><li>Mudah dikembangkan</li></ol>'],
            ['Halaman Draf', 'halaman-draf', 'draft', '<p>Belum dipublikasikan.</p>'],
        ] as [$title, $slug, $status, $body]) {
            Page::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'body' => Sanitizer::html($body), 'status' => $status,
                'published_at' => $status === 'published' ? now()->subMonth() : null,
            ]);
        }
    }

    protected function menus(): void
    {
        $header = Menu::updateOrCreate(['location' => 'header'], ['name' => 'Menu Utama']);
        $header->items()->delete();

        $item = fn (Menu $menu, string $label, string $url, int $order, ?int $parent = null) => $menu->items()->create([
            'label' => $label, 'url' => $url, 'target' => '_self', 'order' => $order, 'parent_id' => $parent, 'is_active' => true,
        ]);

        $item($header, 'Beranda', '/', 0);
        $about = $item($header, 'Tentang', '/halaman/tentang-kami', 1);
        $item($header, 'Tentang Kami', '/halaman/tentang-kami', 0, $about->id);
        $item($header, 'Visi dan Misi', '/halaman/visi-misi', 1, $about->id);
        $item($header, 'Berita', '/berita', 2);
        $item($header, 'Agenda', '/agenda', 3);
        $item($header, 'Galeri', '/galeri', 4);
        $item($header, 'Kontak', '/kontak', 5);

        $footer = Menu::updateOrCreate(['location' => 'footer'], ['name' => 'Menu Footer']);
        $footer->items()->delete();
        $item($footer, 'Pengumuman', '/pengumuman', 0);
        $item($footer, 'Unduhan', '/unduhan', 1);
        $item($footer, 'Kontak', '/kontak', 2);
    }

    protected function slider(): void
    {
        $slider = Slider::updateOrCreate(['key' => config('website.hero_slider')], ['title' => 'Slider Beranda']);
        $slider->slides()->delete();

        foreach ([
            ['Selamat datang di Demo Master Source', 'Fondasi website untuk sistem apa saja', 'Baca berita', '/berita'],
            ['Kelola konten dengan mudah', 'Berita, halaman, galeri, dan agenda dari satu admin', 'Lihat galeri', '/galeri'],
            ['Kembangkan dengan modul', 'Tambahkan fitur khusus tanpa mengubah inti', 'Lihat contoh modul', '/contoh'],
        ] as $order => [$title, $subtitle, $cta, $url]) {
            $slider->slides()->create([
                'image_media_id' => $this->image("Demo: slide {$order}", "slide-{$order}")->id,
                'title' => $title, 'subtitle' => $subtitle, 'cta_label' => $cta, 'cta_url' => $url, 'order' => $order, 'is_active' => true,
            ]);
        }
    }

    protected function features(): void
    {
        foreach ([
            ['Mudah dikelola', 'Admin yang ringkas untuk semua jenis konten.', 'file'],
            ['Aman secara bawaan', 'Hak akses berbasis role dan konten disanitasi.', 'shield'],
            ['Ramah mesin pencari', 'Metadata, sitemap, dan data terstruktur otomatis.', 'star'],
            ['Bisa dikembangkan', 'Tambahkan modul sesuai kebutuhan sistem Anda.', 'layers'],
        ] as $order => [$title, $description, $icon]) {
            Feature::updateOrCreate(['title' => $title], ['description' => $description, 'icon' => $icon, 'order' => $order, 'is_active' => true]);
        }
    }

    protected function testimonials(): void
    {
        foreach ([
            ['Rina Pratama', 'Pengelola konten', 'Menambah berita jadi cepat dan tampilan situsnya langsung rapi.'],
            ['Budi Santoso', 'Pengembang', 'Menambah modul baru terasa jelas: tinggal ikuti pola yang sudah ada.'],
            ['Sari Wulandari', 'Pemilik proyek', 'Satu fondasi untuk beberapa sistem membuat pekerjaan kami jauh lebih ringan.'],
        ] as $order => [$name, $role, $body]) {
            Testimonial::updateOrCreate(['name' => $name], ['role' => $role, 'body' => $body, 'year' => '2026', 'order' => $order, 'is_active' => true]);
        }
    }

    protected function events(): void
    {
        foreach ([
            ['Lokakarya Pengelolaan Konten', 'lokakarya-konten', 5, 'Ruang Serbaguna'],
            ['Rapat Tahunan', 'rapat-tahunan', 14, 'Aula Utama'],
            ['Pelatihan Tim', 'pelatihan-tim', 30, 'Ruang Pelatihan'],
            ['Peluncuran Versi Awal', 'peluncuran-versi-awal', -10, 'Aula Utama'],
        ] as [$title, $slug, $inDays, $location]) {
            Event::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'description' => "Agenda contoh: {$title}.\nBaris kedua deskripsi ini menunjukkan teks biasa dengan baris baru.",
                'location' => $location,
                'starts_at' => now()->addDays($inDays)->setTime(9, 0),
                'ends_at' => now()->addDays($inDays)->setTime(12, 0),
                'is_active' => true,
            ]);
        }
    }

    protected function galleries(): void
    {
        foreach ([['Dokumentasi Kegiatan', 'dokumentasi-kegiatan', 6], ['Suasana Kantor', 'suasana-kantor', 3]] as [$title, $slug, $count]) {
            $gallery = Gallery::updateOrCreate(['slug' => $slug], ['title' => $title, 'description' => "Foto contoh: {$title}.", 'status' => 'published']);
            $gallery->items()->delete();

            foreach (range(1, $count) as $n) {
                $gallery->items()->create([
                    'media_asset_id' => $this->image("Demo: {$title} {$n}", "{$slug}-{$n}")->id,
                    'caption' => "{$title} — foto {$n}",
                    'order' => $n,
                ]);
            }
        }
    }

    protected function downloads(): void
    {
        foreach ([['Panduan Penggunaan', 'panduan-penggunaan'], ['Formulir Contoh', 'formulir-contoh']] as [$title, $slug]) {
            Download::updateOrCreate(['title' => $title], [
                'media_asset_id' => $this->pdf("Demo: {$title}", $slug)->id,
                'description' => "Berkas contoh: {$title}.",
                'is_active' => true,
            ]);
        }
    }

    private function messages(): void
    {
        foreach ([
            ['Andi', 'andi@example.com', 'Tanya fitur modul', 'Apakah modul bisa dibuat khusus untuk kebutuhan kami?', false],
            ['Dewi', 'dewi@example.com', 'Kerja sama', 'Kami tertarik berkolaborasi. Mohon informasinya.', false],
            ['Eko', 'eko@example.com', 'Terima kasih', 'Situsnya bagus dan cepat.', true],
        ] as [$name, $email, $subject, $message, $read]) {
            ContactMessage::updateOrCreate(['email' => $email, 'subject' => $subject], ['name' => $name, 'message' => $message, 'is_read' => $read]);
        }
    }

    /** Item modul Example (hanya bila tabelnya ada, yaitu modul pernah diaktifkan dan dimigrasi). */
    private function exampleItems(): void
    {
        if (! class_exists(Item::class) || ! Schema::hasTable('example_items')) {
            return;
        }

        foreach ([['Kursi Kayu Jati', 'kursi-kayu-jati', 1500000], ['Meja Lipat', 'meja-lipat', 450000], ['Lampu Belajar', 'lampu-belajar', 175000], ['Rak Buku Minimalis', 'rak-buku', 820000]] as [$title, $slug, $price]) {
            Item::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'description' => "Item contoh: {$title}.\nDibuat oleh DemoSeeder.", 'price' => $price,
                'image_media_id' => $this->image("Demo: {$title}", $slug)->id, 'is_active' => true,
            ]);
        }
    }

    // ----- media -----

    /** Entri pustaka media bergambar gradasi; dipakai ulang bila judulnya sudah ada. */
    protected function image(string $title, string $seed): MediaAsset
    {
        return $this->asset($title, fn () => $this->gradient($seed), "{$seed}.png");
    }

    protected function pdf(string $title, string $seed): MediaAsset
    {
        return $this->asset($title, function () use ($title): string {
            $stream = 'BT /F1 18 Tf 20 70 Td ('.addcslashes($title, '()\\').') Tj ET';
            $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
                ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 144]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
                .'4 0 obj<</Length '.strlen($stream)."\n>>stream\n{$stream}\nendstream endobj\n"
                ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R/Size 6>>\n%%EOF\n";

            $path = tempnam(sys_get_temp_dir(), 'demo');
            file_put_contents($path, $pdf);

            return $path;
        }, "{$seed}.pdf");
    }

    /** @param callable(): string $makeFile mengembalikan path berkas sementara (dipindahkan ke penyimpanan media) */
    protected function asset(string $title, callable $makeFile, string $fileName): MediaAsset
    {
        $existing = MediaAsset::query()->where('title', $title)->first();

        if ($existing?->file() !== null) {
            return $existing;
        }

        $asset = $existing ?? MediaAsset::create(['title' => $title, 'uploaded_by' => $this->userId]);
        $asset->addMedia($makeFile())
            ->usingFileName($fileName)
            ->withCustomProperties(['alt' => preg_replace('/^Demo: /', '', $title)])
            ->toMediaCollection(MediaAsset::COLLECTION);

        return $asset->refresh();
    }

    /** PNG gradasi vertikal dengan lingkaran transparan; warna ditentukan dari $seed (hasil selalu sama). */
    private function gradient(string $seed, int $width = 1000, int $height = 600): string
    {
        $hue = crc32($seed) % 360;
        [$r1, $g1, $b1] = $this->hslToRgb($hue, 0.65, 0.48);
        [$r2, $g2, $b2] = $this->hslToRgb(($hue + 45) % 360, 0.65, 0.30);

        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            imageline($image, 0, $y, $width, $y, imagecolorallocate(
                $image,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t),
            ));
        }

        imagealphablending($image, true);

        foreach (range(0, 4) as $i) {
            $n = crc32("{$seed}{$i}");
            imagefilledellipse(
                $image,
                $n % $width,
                intdiv($n, 7) % $height,
                120 + $n % 260,
                120 + intdiv($n, 3) % 260,
                imagecolorallocatealpha($image, 255, 255, 255, 105),
            );
        }

        $path = tempnam(sys_get_temp_dir(), 'demo');
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    /** @return array{int, int, int} */
    private function hslToRgb(int $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (intdiv($h, 60)) {
            0 => [$c, $x, 0], 1 => [$x, $c, 0], 2 => [0, $c, $x], 3 => [0, $x, $c], 4 => [$x, 0, $c], default => [$c, 0, $x],
        };

        return [(int) (($r + $m) * 255), (int) (($g + $m) * 255), (int) (($b + $m) * 255)];
    }
}
