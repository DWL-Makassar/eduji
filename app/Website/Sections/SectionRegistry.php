<?php

declare(strict_types=1);

namespace App\Website\Sections;

use App\Core\Media\MediaAsset;
use App\Website\Http\Presenter;
use Closure;

/**
 * Katalog section untuk page builder. Core-Website dan Module mendaftar (satu arah: Module → Website).
 *
 * - `resolver(array $settings)` mengembalikan data untuk komponen Vue section itu, atau null/[] bila tidak ada isi (dilewati).
 *   Section yang isinya hanya dari pengaturan cukup mengembalikan true bila pengaturannya terisi.
 * - `fields` = skema App\Cms\Support\Field yang diisi admin (judul, jumlah item, teks, gambar, …), disimpan per blok.
 *   Sebaiknya berupa closure: Field::* menerjemahkan label saat dipanggil, dan pendaftaran terjadi saat boot (sebelum bahasa
 *   admin diketahui). Closure dijalankan per permintaan sehingga label mengikuti bahasa aktif.
 * - `variants` = value => kunci terjemahan; tampilan alternatif yang dipilih admin. Kosong = satu varian "default".
 * - `automatic` = isinya otomatis dari data (berita, guru, SPMB…), bukan diketik di pengaturan blok. Section otomatis selalu
 *   masuk susunan bawaan, walau saat ini kosong, agar tampil sendiri begitu ada isinya (tidak "terbekukan" saat beranda pertama disimpan).
 *
 * Kunci section = nama komponen di resources/js/components/site/sections (lihat index.ts di sana).
 *
 * Module HARUS mendaftar di boot(), bukan register(): modul dimuat saat register() Core, sebelum registry ini
 * dijadikan singleton, sehingga pendaftaran di register() mengenai instance lain dan hilang begitu saja.
 */
final class SectionRegistry
{
    /** @var array<string, array{label: string, resolver: Closure, order: int, fields: list<array<string, mixed>>|Closure, variants: array<string, string>, icon: string, automatic: bool}> */
    private array $sections = [];

    /**
     * @param  list<array<string, mixed>>|Closure(): list<array<string, mixed>>  $fields
     * @param  array<string, string>  $variants
     */
    public function register(string $key, string $label, Closure $resolver, int $order = 100, array|Closure $fields = [], array $variants = [], string $icon = 'layers', bool $automatic = true): void
    {
        $variants = $variants ?: ['default' => 'website.variants.default'];
        $this->sections[$key] = compact('label', 'resolver', 'order', 'fields', 'variants', 'icon', 'automatic');
    }

    /** @return array{label: string, resolver: Closure, order: int, fields: list<array<string, mixed>>, variants: array<string, string>, icon: string, automatic: bool}|null */
    public function definition(string $key): ?array
    {
        $section = $this->sections[$key] ?? null;

        return $section === null ? null : ['fields' => value($section['fields'])] + $section;
    }

    /** @return array<string, string> kunci => label (terjemahan), urut bawaan */
    public function labels(): array
    {
        return collect($this->sections)->sortBy('order')->map(fn (array $s) => __($s['label']))->all();
    }

    /** @return list<array{key: string, label: string, icon: string, fields: list<array<string, mixed>>, variants: list<array{value: string, label: string}>}> */
    public function catalog(): array
    {
        return collect($this->sections)->sortBy('order')->map(fn (array $s, string $key) => [
            'key' => $key,
            'label' => __($s['label']),
            'icon' => $s['icon'],
            'fields' => value($s['fields']),
            'variants' => collect($s['variants'])->map(fn (string $label, string $value) => ['value' => $value, 'label' => __($label)])->values()->all(),
        ])->values()->all();
    }

    /**
     * Blok bawaan untuk beranda yang belum pernah disusun: semua section otomatis, urut bawaan, tanpa pengaturan.
     * Yang kosong tetap ikut (dilewati saat render) agar muncul sendiri ketika datanya ada.
     *
     * @return list<array{key: string, variant: string, settings: array<string, mixed>}>
     */
    public function defaultBlocks(): array
    {
        return collect($this->sections)->sortBy('order')
            ->filter(fn (array $section) => $section['automatic'])
            ->keys()
            ->map(fn (string $key) => ['key' => $key, 'variant' => 'default', 'settings' => []])
            ->values()
            ->all();
    }

    /**
     * Blok → data siap render. Blok tersembunyi (is_visible false), blok bertipe tak dikenal (mis. modulnya dimatikan) dan section tanpa isi dilewati;
     * varian tak dikenal jatuh ke "default". `settings` dikirim ke publik dengan id media sudah menjadi data gambar.
     *
     * @param  list<array{key: string, variant?: string, settings?: array<string, mixed>, is_visible?: bool}>  $blocks
     * @return list<array{key: string, variant: string, settings: array<string, mixed>, data: mixed}>
     */
    public function resolve(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn (array $block) => isset($this->sections[$block['key']]) && ($block['is_visible'] ?? true))
            ->map(function (array $block) {
                $section = $this->definition($block['key']);
                $settings = SectionSchema::map($section['fields'], $block['settings'] ?? [], fn (array $field, mixed $value) => $value);

                return [
                    'key' => $block['key'],
                    'variant' => isset($section['variants'][$block['variant'] ?? '']) ? $block['variant'] : 'default',
                    'settings' => self::publicSettings($section['fields'], $settings),
                    'data' => ($section['resolver'])($settings),
                ];
            })
            ->reject(fn (array $section) => in_array($section['data'], [null, [], false], true))
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function publicSettings(array $fields, array $settings): array
    {
        // shortcut: satu query per field media; cukup untuk beberapa gambar per halaman, kumpulkan id bila ada section bergambar banyak.
        return SectionSchema::map($fields, $settings, fn (array $field, mixed $value) => $field['type'] === 'media'
            ? ($value ? Presenter::media(MediaAsset::with('media')->find($value)) : null)
            : $value);
    }
}
