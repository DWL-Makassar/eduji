<?php

declare(strict_types=1);

namespace App\Website\Sections;

/**
 * Template awal beranda: tema (warna, font, sudut) + susunan blok berisi contoh teks.
 * Diterapkan sekali klik di Admin → Susun halaman, lalu bebas diubah. Contoh isi harus lolos validasi skema
 * section-nya (dijaga oleh test), dan hanya memakai section bawaan agar selalu tersedia.
 */
final class Templates
{
    /** @return array<string, array{theme: string, font: string, radius: string, blocks: list<array{key: string, variant?: string, settings?: array<string, mixed>}>}> */
    public static function all(): array
    {
        $welcome = fn (string $variant = 'default') => ['key' => 'welcome', 'variant' => $variant, 'settings' => [
            'title' => 'Sambutan Kepala Sekolah',
            'name' => 'Nama Kepala Sekolah',
            'role' => 'Kepala Sekolah',
            'body' => '<p>Selamat datang di website resmi sekolah kami. Melalui website ini kami berbagi kabar, prestasi, dan informasi penting bagi siswa, orang tua, dan masyarakat.</p>',
        ]];
        $stats = fn (string $variant) => ['key' => 'stats', 'variant' => $variant, 'settings' => ['items' => [
            ['value' => '650+', 'label' => 'Siswa aktif'],
            ['value' => '48', 'label' => 'Guru & tenaga kependidikan'],
            ['value' => '20+', 'label' => 'Ekstrakurikuler'],
            ['value' => 'A', 'label' => 'Akreditasi'],
        ]]];
        $cards = fn (string $title, array $items, string $variant = 'default') => ['key' => 'cards', 'variant' => $variant, 'settings' => [
            'title' => $title,
            'items' => array_map(fn (array $i) => ['icon' => $i[0], 'title' => $i[1], 'text' => $i[2], 'url' => null], $items),
        ]];
        $cta = fn (string $variant) => ['key' => 'cta', 'variant' => $variant, 'settings' => [
            'title' => 'Penerimaan Peserta Didik Baru',
            'text' => 'Pendaftaran tahun ajaran baru telah dibuka. Daftarkan putra-putri Anda sekarang.',
            'button_label' => 'Hubungi kami',
            'button_url' => '/kontak',
        ]];
        $faq = ['key' => 'faq', 'settings' => ['title' => 'Pertanyaan umum', 'items' => [
            ['question' => 'Kapan pendaftaran siswa baru dibuka?', 'answer' => 'Jadwal pendaftaran diumumkan di menu Pengumuman setiap awal tahun.'],
            ['question' => 'Apakah tersedia layanan konsultasi?', 'answer' => 'Ya, silakan hubungi kami lewat halaman Kontak pada jam kerja.'],
        ]]];
        $map = ['key' => 'map', 'settings' => ['title' => 'Lokasi & kontak']];

        return [
            'formal' => ['theme' => 'education', 'font' => 'lora', 'radius' => 'sharp', 'blocks' => [
                ['key' => 'hero'], $welcome(), $stats('band'), ['key' => 'announcements'], ['key' => 'posts', 'variant' => 'list'],
                ['key' => 'events', 'variant' => 'list'], ['key' => 'galleries'], $map,
            ]],
            'modern' => ['theme' => 'default', 'font' => 'jakarta', 'radius' => 'round', 'blocks' => [
                ['key' => 'hero'],
                $cards('Mengapa memilih kami', [['star', 'Pembelajaran aktif', 'Kelas interaktif berbasis proyek.'], ['shield', 'Lingkungan aman', 'Sekolah ramah anak dan bebas perundungan.'], ['users', 'Guru berpengalaman', 'Pendidik tersertifikasi dan terus berkembang.']]),
                $stats('default'), ['key' => 'posts'], ['key' => 'events'], ['key' => 'testimonials'], $cta('default'), $faq,
            ]],
            'islamic' => ['theme' => 'islamic', 'font' => 'poppins', 'radius' => 'default', 'blocks' => [
                ['key' => 'hero'], $welcome('right'),
                $cards('Program unggulan', [['star', 'Tahfidz Al-Qur\'an', 'Target hafalan bertahap dengan pendampingan.'], ['users', 'Pembiasaan ibadah', 'Salat berjamaah dan kajian rutin.'], ['file', 'Kurikulum terpadu', 'Ilmu umum dan agama dalam satu kurikulum.']]),
                ['key' => 'announcements'], ['key' => 'posts'], ['key' => 'galleries'], $cta('light'), $map,
            ]],
            'vocational' => ['theme' => 'vocational', 'font' => 'poppins', 'radius' => 'sharp', 'blocks' => [
                ['key' => 'hero'],
                ['key' => 'image_text', 'settings' => ['title' => 'Siap kerja, siap berwirausaha', 'body' => '<p>Pembelajaran berbasis industri dengan praktik kerja lapangan di perusahaan mitra.</p>', 'button_label' => 'Lihat berita', 'button_url' => '/berita']],
                $cards('Kompetensi keahlian', [['layers', 'Teknik Komputer & Jaringan', 'Jaringan, server, dan keamanan siber.'], ['image', 'Desain Komunikasi Visual', 'Desain grafis, foto, dan video.'], ['file', 'Akuntansi', 'Akuntansi dan keuangan lembaga.']], 'list'),
                $stats('band'), ['key' => 'posts'], ['key' => 'events', 'variant' => 'list'], $cta('default'), $map,
            ]],
            'playful' => ['theme' => 'playful', 'font' => 'nunito', 'radius' => 'round', 'blocks' => [
                ['key' => 'hero'],
                ['key' => 'text', 'variant' => 'center', 'settings' => ['title' => 'Belajar sambil bermain', 'body' => '<p>Tempat anak tumbuh ceria, mandiri, dan percaya diri bersama guru yang penuh kasih.</p>']],
                $cards('Kegiatan kami', [['star', 'Bermain peran', 'Melatih imajinasi dan kerja sama.'], ['image', 'Seni & kreativitas', 'Mewarnai, menggambar, dan prakarya.'], ['users', 'Outing class', 'Belajar langsung di luar kelas.']]),
                ['key' => 'galleries'], ['key' => 'testimonials'], $cta('light'), $map,
            ]],
        ];
    }

    /** @return list<array{key: string, label: string, description: string, theme: string}> */
    public static function options(): array
    {
        return collect(self::all())->map(fn (array $t, string $key) => [
            'key' => $key,
            'label' => __("website.templates.{$key}.label"),
            'description' => __("website.templates.{$key}.description"),
            'theme' => config("theme.themes.{$t['theme']}.600"),
        ])->values()->all();
    }
}
