<?php

declare(strict_types=1);

namespace Modules\School\Database\Seeders;

use App\Cms\Categories\Category;
use App\Cms\Downloads\Download;
use App\Cms\Events\Event;
use App\Cms\Features\Feature;
use App\Cms\Gallery\Gallery;
use App\Cms\Menus\Menu;
use App\Cms\Pages\Page;
use App\Cms\Posts\Post;
use App\Cms\Sliders\Slider;
use App\Cms\Testimonials\Testimonial;
use App\Core\Media\MediaAsset;
use App\Core\Settings\SettingsStore;
use App\Core\Support\Sanitizer;
use App\Website\Sections\PageBlock;
use Database\Seeders\DemoSeeder;
use Modules\School\Models\Achievement;
use Modules\School\Models\Alumnus;
use Modules\School\Models\Extracurricular;
use Modules\School\Models\Registrant;
use Modules\School\Models\Staff;

/**
 * Situs demo sekolah untuk calon pembeli, bergaya template Formal: identitas sekolah, menu, data sekolah, SPMB dibuka,
 * dan beranda tersusun. Idempoten. Dipakai `demo:reset` lewat DEMO_SEEDER:
 *
 *     php artisan db:seed --class="Modules\School\Database\Seeders\SchoolDemoSeeder"
 *
 * Isi (berita, halaman, agenda, program, ekskul, prestasi, testimoni) diambil dari situs sekolah sungguhan lalu
 * disamarkan: nama sekolah, alamat, dan nama orang diganti fiktif. Sumbernya demo/content.json; foto siswa tidak ikut
 * (diganti gambar gradasi), hanya tiga banner stok di demo/*.jpg.
 */
class SchoolDemoSeeder extends DemoSeeder
{
    private const SCHOOL = 'SMP Negeri Nusantara';

    /** @var array<string, mixed>|null */
    private ?array $content = null;

    public function run(SettingsStore $settings): void
    {
        parent::run($settings);

        if (app()->isProduction() && ! config('platform.demo.enabled')) {
            return; // ditolak parent (pesan sudah ditampilkan)
        }

        $settings->setMany('website', ['theme' => 'islamic', 'font' => 'jakarta', 'radius' => 'default', 'home_built' => true]);
        $settings->setMany('school', [
            'spmb_open' => true,
            'spmb_year' => '2026/2027',
            'spmb_info' => "Pendaftaran online: 1 Juni – 30 Juni 2026.\nSyarat: kartu keluarga, akta kelahiran, pas foto, dan ijazah/SKL.\nInformasi: panitia SPMB (0411) 555 024.",
        ]);

        $this->staff();
        $this->achievements();
        $this->extracurriculars();
        $this->alumni();
        $this->registrants();
        $this->home();

        $this->command?->info('Data demo sekolah siap.');
    }

    /** Isi demo/content.json (seluruhnya, atau satu bagian bila $key diisi). */
    private function content(?string $key = null): mixed
    {
        $this->content ??= json_decode((string) file_get_contents(__DIR__.'/demo/content.json'), true, flags: JSON_THROW_ON_ERROR);

        return $key === null ? $this->content : $this->content[$key];
    }

    /** Gambar dari folder demo/ (disalin dulu: pustaka media memindahkan berkas sumber). */
    private function photo(string $title, string $file): MediaAsset
    {
        return $this->asset($title, function () use ($file): string {
            $path = tempnam(sys_get_temp_dir(), 'demo');
            copy(__DIR__."/demo/{$file}", $path);

            return $path;
        }, $file);
    }

    protected function settings(SettingsStore $settings): void
    {
        $settings->setMany('general', [
            'site_name' => self::SCHOOL,
            'tagline' => $this->content('settings')['tagline'],
            'email' => 'info@smpnusantara.sch.id',
            'phone' => '(0411) 555 024',
            'address' => 'Jl. Pendidikan No. 24, Makassar, Sulawesi Selatan',
        ]);
        $settings->set('seo', 'description', $this->content('settings')['seo_description']);
    }

    protected function posts(): void
    {
        $categories = collect(['Kegiatan Siswa', 'Prestasi', 'Pengumuman'])->mapWithKeys(fn (string $name, int $i) => [
            $name => Category::updateOrCreate(['type' => 'post', 'slug' => str($name)->slug()->value()], ['name' => $name, 'order' => $i]),
        ]);

        foreach ($this->content('posts') as $i => $post) {
            Post::updateOrCreate(['type' => 'post', 'slug' => $post['slug']], [
                'title' => $post['title'],
                'category_id' => ($categories[$post['category']] ?? $categories['Kegiatan Siswa'])->id,
                'author_id' => $this->userId,
                'cover_media_id' => $this->image("Berita: {$post['title']}", "news-{$i}")->id,
                'excerpt' => $post['excerpt'],
                'body' => Sanitizer::html($post['body']),
                'status' => 'published',
                'published_at' => now()->subDays($i * 3 + 1),
                'is_pinned' => $i === 0,
            ]);
        }

        foreach ($this->content('announcements') as $i => $item) {
            Post::updateOrCreate(['type' => 'announcement', 'slug' => str($item['title'])->slug()->value()], [
                'title' => $item['title'], 'body' => Sanitizer::html($item['body']),
                'status' => 'published', 'published_at' => now()->subDays($i + 1), 'expires_at' => now()->addDays(30 * ($i + 1)),
            ]);
        }
    }

    protected function pages(): void
    {
        $settings = $this->content('settings');
        $mission = collect(preg_split('/\R/', $settings['mission']))->map(fn (string $line) => '<li>'.e(preg_replace('/^\d+\.\s*/', '', $line)).'</li>')->implode('');
        $pages = [...$this->content('pages'), [
            'title' => 'Visi & Misi', 'slug' => 'visi-misi',
            'body' => '<h2>Visi</h2><p>'.e($settings['vision']).'</p><h2>Misi</h2><ol>'.$mission.'</ol>',
        ]];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], [
                'title' => $page['title'], 'body' => Sanitizer::html($page['body']), 'status' => 'published', 'published_at' => now()->subMonth(),
            ]);
        }
    }

    protected function menus(): void
    {
        $item = fn (Menu $menu, string $label, string $url, int $order, ?int $parent = null) => $menu->items()->create([
            'label' => $label, 'url' => $url, 'target' => '_self', 'order' => $order, 'parent_id' => $parent, 'is_active' => true,
        ]);
        $group = function (Menu $menu, string $label, int $order, array $children) use ($item): void {
            $parent = $item($menu, $label, $children[0][1], $order);
            foreach ($children as $i => [$childLabel, $url]) {
                $item($menu, $childLabel, $url, $i, $parent->id);
            }
        };

        // Susunan menu template Formal: Profil, Kesiswaan, dan Informasi sebagai menu bertingkat.
        $header = Menu::updateOrCreate(['location' => 'header'], ['name' => 'Menu Utama']);
        $header->items()->delete();
        $item($header, 'Beranda', '/', 0);
        $group($header, 'Profil Sekolah', 1, [
            ['Profil & Sejarah', '/halaman/tentang-kami'], ['Visi & Misi', '/halaman/visi-misi'], ['Struktur Organisasi', '/halaman/struktur-organisasi'],
            ['Guru & Staf', '/guru-staf'], ['Sarana & Prasarana', '/halaman/sarana-prasarana'],
        ]);
        $group($header, 'Kesiswaan', 2, [
            ['Ekstrakurikuler', '/ekstrakurikuler'], ['Prestasi Siswa', '/prestasi'], ['Perpustakaan', '/halaman/perpustakaan'], ['Tata Tertib', '/halaman/tata-tertib'], ['Alumni', '/alumni'],
        ]);
        $group($header, 'Informasi', 3, [
            ['Berita', '/berita'], ['Pengumuman', '/pengumuman'], ['Agenda Sekolah', '/agenda'], ['Download Center', '/unduhan'], ['Kurikulum', '/halaman/informasi-kurikulum'],
        ]);
        $item($header, 'Galeri', '/galeri', 4);
        $item($header, 'SPMB', '/spmb', 5);
        $item($header, 'Kontak', '/kontak', 6);

        $footer = Menu::updateOrCreate(['location' => 'footer'], ['name' => 'Menu Footer']);
        $footer->items()->delete();
        foreach ([['Profil Sekolah', '/halaman/tentang-kami'], ['Guru & Staf', '/guru-staf'], ['Galeri', '/galeri'], ['Cek Status SPMB', '/spmb/status'], ['Kebijakan Privasi', '/halaman/kebijakan-privasi'], ['Hubungi Kami', '/kontak']] as $i => [$label, $url]) {
            $item($footer, $label, $url, $i);
        }
    }

    protected function slider(): void
    {
        $slider = Slider::updateOrCreate(['key' => config('website.hero_slider')], ['title' => 'Slider Beranda']);
        $slider->slides()->delete();

        foreach ($this->content('slides') as $order => $slide) {
            $slider->slides()->create([
                'image_media_id' => $this->photo("Banner: {$slide['title']}", $slide['image'])->id,
                'title' => $slide['title'], 'subtitle' => $slide['subtitle'], 'cta_label' => $slide['cta_label'], 'cta_url' => $slide['cta_url'],
                'order' => $order, 'is_active' => true,
            ]);
        }
    }

    protected function features(): void
    {
        foreach ($this->content('features') as $order => $feature) {
            Feature::updateOrCreate(['title' => $feature['title']], [...$feature, 'order' => $order, 'is_active' => true]);
        }
    }

    protected function testimonials(): void
    {
        foreach ($this->content('testimonials') as $order => $testimonial) {
            Testimonial::updateOrCreate(['name' => $testimonial['name']], [...$testimonial, 'order' => $order, 'is_active' => true]);
        }
    }

    protected function events(): void
    {
        foreach ($this->content('events') as $i => $event) {
            $day = now()->addDays([3, 6, 11, 16, 26][$i] ?? 30 + $i);
            Event::updateOrCreate(['slug' => str($event['title'])->slug()->limit(80, '')->value()], [
                'title' => $event['title'], 'description' => $event['description'], 'location' => $event['location'],
                'starts_at' => $day->copy()->setTime(8, 0), 'ends_at' => $day->copy()->setTime(12, 0), 'is_active' => true,
            ]);
        }
    }

    protected function galleries(): void
    {
        foreach ([['Upacara & Peringatan Hari Besar', 'upacara-peringatan'], ['Kegiatan Pramuka', 'kegiatan-pramuka'], ['Pentas Seni Siswa', 'pentas-seni'], ['Pameran Karya P5', 'pameran-karya-p5']] as [$title, $slug]) {
            $gallery = Gallery::updateOrCreate(['slug' => $slug], ['title' => $title, 'description' => "Dokumentasi {$title}.", 'status' => 'published']);
            $gallery->items()->delete();

            foreach (range(1, 4) as $n) {
                $gallery->items()->create(['media_asset_id' => $this->image("Galeri: {$title} {$n}", "{$slug}-{$n}")->id, 'caption' => "{$title} — foto {$n}", 'order' => $n]);
            }
        }
    }

    protected function downloads(): void
    {
        foreach ($this->content('downloads') as $i => $download) {
            Download::updateOrCreate(['title' => $download['title']], [
                'media_asset_id' => $this->pdf("Unduhan: {$download['title']}", 'unduhan-'.($i + 1))->id,
                'description' => $download['description'] ?: null, 'is_active' => true,
            ]);
        }
    }

    private function staff(): void
    {
        foreach ([
            ['Drs. H. Ahmad Yani, M.Pd', 'teacher', 'Kepala Sekolah', null],
            ['Siti Rahmawati, S.Pd', 'teacher', 'Wakil Kepala Kurikulum', 'Matematika'],
            ['Budi Santoso, S.Pd', 'teacher', 'Guru', 'Bahasa Indonesia'],
            ['Andi Pratama, S.Kom', 'teacher', 'Guru', 'Informatika'],
            ['Nur Aisyah, S.Pd', 'teacher', 'Wali Kelas IX-A', 'IPA'],
            ['Muh. Ridwan, S.Pd', 'teacher', 'Pembina OSIS', 'PJOK'],
            ['Hasnah, S.E', 'staff', 'Kepala Tata Usaha', null],
            ['Rahmat Hidayat', 'staff', 'Operator Sekolah', null],
        ] as $order => [$name, $type, $position, $subject]) {
            Staff::updateOrCreate(['name' => $name], ['type' => $type, 'position' => $position, 'subject' => $subject, 'order' => $order, 'is_active' => true]);
        }
    }

    private function achievements(): void
    {
        foreach ($this->content('achievements') as $i => $achievement) {
            Achievement::updateOrCreate(['title' => $achievement['title']], [
                'level' => $achievement['level'], 'winner' => $achievement['winner'], 'year' => $achievement['year'],
                'description' => $achievement['description'] ?: null, 'is_active' => true,
                'image_media_id' => $this->image("Prestasi: {$achievement['title']}", "achievement-{$i}")->id,
            ]);
        }
    }

    private function extracurriculars(): void
    {
        foreach ($this->content('extracurriculars') as $order => $ekskul) {
            Extracurricular::updateOrCreate(['name' => $ekskul['name']], [
                'description' => $ekskul['description'] ?: null, 'order' => $order, 'is_active' => true,
                'image_media_id' => $this->image("Ekstrakurikuler: {$ekskul['name']}", "ekskul-{$order}")->id,
            ]);
        }
    }

    private function alumni(): void
    {
        foreach ([
            ['dr. Dewi Lestari', 2012, 'Dokter', 'RSUP Wahidin Sudirohusodo', 'Guru-gurunya sabar dan menginspirasi. Terima kasih SMP tercinta!'],
            ['Arif Rahman', 2020, 'Mahasiswa', 'Universitas Hasanuddin', 'Ekskul robotik membuka jalan saya ke dunia teknik.'],
            ['Nadia Putri', 2016, 'Guru', 'SMA Negeri 5 Makassar', 'Sekarang saya ikut mendidik, seperti guru-guru saya dulu.'],
        ] as [$name, $year, $activity, $institution, $testimony]) {
            Alumnus::updateOrCreate(['name' => $name], ['graduation_year' => $year, 'activity' => $activity, 'institution' => $institution, 'testimony' => $testimony, 'is_active' => true]);
        }
    }

    /** Pendaftar contoh tanpa berkas (data fiktif) agar menu Pendaftar SPMB tidak kosong. */
    private function registrants(): void
    {
        foreach ([['Aulia Rahma', 'P', 'submitted'], ['Fajar Nugraha', 'L', 'verified'], ['Citra Ayu', 'P', 'accepted']] as $i => [$name, $gender, $status]) {
            $registrant = Registrant::updateOrCreate(['full_name' => $name], [
                'academic_year' => '2026/2027', 'gender' => $gender, 'birth_place' => 'Makassar', 'birth_date' => '2014-0'.($i + 3).'-1'.$i,
                'previous_school' => 'SD Negeri '.($i + 1).' Makassar', 'address' => 'Jl. Contoh No. '.($i + 10).', Makassar',
                'parent_name' => 'Orang Tua '.$name, 'parent_phone' => '0812000000'.$i, 'status' => $status, 'documents' => [],
            ]);
            $registrant->forceFill(['number' => sprintf('SPMB-2026-%04d', $registrant->id), 'access_code' => $registrant->access_code ?? Registrant::generateAccessCode()])->save();
        }
    }

    /** Beranda dengan urutan seksi template Formal. */
    private function home(): void
    {
        $settings = $this->content('settings');
        $blocks = [
            ['hero'],
            ['welcome', 'default', [
                'title' => $settings['principal_motto'], 'name' => 'Drs. H. Ahmad Yani, M.Pd', 'role' => 'Kepala Sekolah',
                'body' => '<p>'.e($settings['principal_message']).'</p>',
            ]],
            ['posts', 'default', ['title' => 'Berita Terbaru', 'limit' => 5]],
            ['events', 'default', ['title' => 'Agenda Sekolah', 'limit' => 4]],
            ['features', 'default', ['title' => 'Program Unggulan']],
            ['stats', 'band', ['items' => [['value' => '720+', 'label' => 'Total Siswa'], ['value' => '52+', 'label' => 'Tenaga Pendidik'], ['value' => '28 Th', 'label' => 'Tahun Berdiri'], ['value' => '24', 'label' => 'Rombongan Belajar']]]],
            ['school_achievements', 'default', ['title' => 'Prestasi Membanggakan', 'limit' => 4]],
            ['school_extracurriculars', 'default', ['limit' => 12]],
            ['galleries', 'default', ['title' => 'Galeri Kegiatan', 'limit' => 4]],
            ['cta', 'default', [
                'title' => 'Bergabunglah dengan Keluarga Besar Kami',
                'text' => 'Daftarkan putra-putri Anda sekarang dan berikan mereka pendidikan terbaik untuk masa depan yang gemilang.',
                'button_label' => 'Daftar SPMB Online', 'button_url' => '/spmb',
            ]],
            ['testimonials', 'default', ['title' => 'Apa Kata Mereka?']],
        ];

        PageBlock::query()->whereNull('page_id')->delete();
        foreach ($blocks as $position => $block) {
            PageBlock::create(['type' => $block[0], 'variant' => $block[1] ?? 'default', 'settings' => $block[2] ?? [], 'position' => $position, 'is_visible' => true]);
        }
    }
}
