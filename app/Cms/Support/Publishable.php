<?php

declare(strict_types=1);

namespace App\Cms\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Untuk model dengan kolom status (draft|published) dan published_at.
 * Terbit = status published DAN published_at sudah lewat (mendukung jadwal terbit).
 */
trait Publishable
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)->where('published_at', '<=', now());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withPublishDate(array $data): array
    {
        if (($data['status'] ?? null) === self::PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
