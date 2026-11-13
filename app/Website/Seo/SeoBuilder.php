<?php

declare(strict_types=1);

namespace App\Website\Seo;

use App\Core\Media\MediaAsset;
use App\Core\Settings\Branding;
use App\Core\Settings\SettingsStore;
use Illuminate\Support\Str;

/**
 * Menyusun SeoData untuk satu halaman. Bila judul/deskripsi/gambar halaman kosong, dipakai bawaan situs:
 * setting `seo.description` (lalu slogan) dan `seo.og_image_media_id` (lalu logo).
 */
final class SeoBuilder
{
    private const DESCRIPTION_LIMIT = 160;

    public function __construct(
        private readonly SettingsStore $settings,
        private readonly Branding $branding,
    ) {}

    /** @param list<array<string, mixed>> $jsonLd */
    public function make(?string $title = null, ?string $description = null, ?string $image = null, string $type = 'website', array $jsonLd = []): SeoData
    {
        $site = $this->branding->siteName();

        return new SeoData(
            title: $title ? "{$title} — {$site}" : $site,
            description: self::plain($description) ?: self::plain((string) ($this->settings->get('seo', 'description') ?: $this->settings->get('general', 'tagline', ''))),
            image: $image ?? $this->defaultImage(),
            canonical: url()->current(),
            siteName: $site,
            locale: match (app()->getLocale()) {
                'id' => 'id_ID',
                'en' => 'en_US',
                default => app()->getLocale(),
            },
            type: $type,
            noindex: ! $this->indexable(),
            jsonLd: $jsonLd,
        );
    }

    /** Situs boleh diindeks mesin pencari (dimatikan di staging lewat Admin → Tampilan situs). */
    public function indexable(): bool
    {
        return (bool) $this->settings->get('seo', 'allow_indexing', true);
    }

    /** Teks polos ringkas dari HTML/teks (untuk meta description dan ringkasan); panjang total (termasuk "…") ≤ $limit. */
    public static function plain(?string $text, int $limit = self::DESCRIPTION_LIMIT): string
    {
        return (string) Str::of(html_entity_decode(strip_tags((string) $text)))->squish()->limit($limit - 1, '…');
    }

    private function defaultImage(): ?string
    {
        $id = $this->settings->get('seo', 'og_image_media_id');

        return ($id ? MediaAsset::with('media')->find($id)?->file()?->getUrl() : null) ?? $this->branding->logoUrl();
    }
}
