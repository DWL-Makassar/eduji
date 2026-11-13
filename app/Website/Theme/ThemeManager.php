<?php

declare(strict_types=1);

namespace App\Website\Theme;

use App\Core\Settings\SettingsStore;

/**
 * Tampilan situs dari setting `website.{theme,font,radius,design}` dengan preset di config/theme.php,
 * ditulis sebagai CSS variable di <head> (menimpa token @theme di resources/css/app.css).
 */
final class ThemeManager
{
    /** Jenis pengaturan → kunci daftar preset di config/theme.php. */
    private const PRESETS = ['theme' => 'themes', 'font' => 'fonts', 'radius' => 'radii', 'design' => 'designs'];

    public function __construct(private readonly SettingsStore $settings) {}

    /** @return list<string> */
    public function keys(string $kind = 'theme'): array
    {
        return array_keys(config('theme.'.self::PRESETS[$kind]));
    }

    public function active(string $kind = 'theme'): string
    {
        $key = $this->settings->get('website', $kind);

        if (is_string($key) && in_array($key, $this->keys($kind), true)) {
            return $key;
        }

        if ($kind === 'theme') {
            return config('theme.default');
        }

        return in_array('default', $this->keys($kind), true) ? 'default' : $this->keys($kind)[0];
    }

    /** @return list<array{key: string, label: string, color: string}> untuk pilihan di admin (color = contoh warna utama) */
    public function options(): array
    {
        return array_map(fn (string $key) => [
            'key' => $key,
            'label' => __("website.themes.{$key}"),
            'color' => config("theme.themes.{$key}.600"),
        ], $this->keys());
    }

    /** @return list<array{key: string, label: string}> pilihan font atau sudut di admin */
    public function choices(string $kind): array
    {
        return array_map(fn (string $key) => ['key' => $key, 'label' => __('website.'.self::PRESETS[$kind].".{$key}")], $this->keys($kind));
    }

    /** URL stylesheet Google Fonts untuk font aktif; null = font sistem. */
    public function fontUrl(): ?string
    {
        $family = config('theme.fonts.'.$this->active('font').'.google');

        return $family ? "https://fonts.googleapis.com/css2?family={$family}&display=swap" : null;
    }

    /** Isi blok <style>: nilai hanya berasal dari config/theme.php (bukan input pengguna). */
    public function css(): string
    {
        $radius = config('theme.radii.'.$this->active('radius'));

        $variables = collect(config('theme.themes.'.$this->active()))
            ->map(fn (string $hex, int|string $shade) => "--color-primary-{$shade}:{$hex}") // kunci "50" jadi int di PHP
            ->push('--font-sans:'.config('theme.fonts.'.$this->active('font').'.stack'))
            ->push("--radius-control:{$radius['control']}", "--radius-card:{$radius['card']}")
            ->implode(';');

        return ":root{{$variables}}";
    }
}
