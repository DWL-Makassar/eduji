<?php

declare(strict_types=1);

namespace App\Website\Seo;

use Carbon\CarbonInterface;

/** Pembuat objek schema.org. Nilai kosong dibuang agar keluarannya valid dan ringkas. */
final class JsonLd
{
    /** @return array<string, mixed> */
    public static function organization(string $name, string $url, ?string $logo, ?string $email, ?string $phone, ?string $address): array
    {
        return self::compact([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
            'logo' => $logo,
            'email' => $email,
            'telephone' => $phone,
            'address' => $address,
        ]);
    }

    /** @return array<string, mixed> */
    public static function website(string $name, string $url): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $name, 'url' => $url];
    }

    /** @return array<string, mixed> */
    public static function article(string $headline, string $description, string $url, ?string $image, ?CarbonInterface $published, ?CarbonInterface $modified, ?string $author, string $publisher): array
    {
        return self::compact([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $headline,
            'description' => $description,
            'mainEntityOfPage' => $url,
            'image' => $image,
            'datePublished' => $published?->toIso8601String(),
            'dateModified' => $modified?->toIso8601String(),
            'author' => $author ? ['@type' => 'Person', 'name' => $author] : null,
            'publisher' => ['@type' => 'Organization', 'name' => $publisher],
        ]);
    }

    /** @return array<string, mixed> */
    public static function event(string $name, string $description, string $url, ?CarbonInterface $starts, ?CarbonInterface $ends, ?string $location): array
    {
        return self::compact([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'startDate' => $starts?->toIso8601String(),
            'endDate' => $ends?->toIso8601String(),
            'location' => $location ? ['@type' => 'Place', 'name' => $location] : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function compact(array $data): array
    {
        return array_filter($data, fn (mixed $value) => $value !== null && $value !== '');
    }
}
