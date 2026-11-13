<?php

declare(strict_types=1);

namespace App\Website\Seo;

/** Metadata satu halaman publik. Dikirim ke Inertia sebagai prop `seo`, lalu dirender ke <head> oleh app.blade.php. */
final readonly class SeoData
{
    /** @param list<array<string, mixed>> $jsonLd objek schema.org */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $image,
        public string $canonical,
        public string $siteName,
        public string $locale,
        public string $type = 'website',
        public bool $noindex = false,
        public array $jsonLd = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
