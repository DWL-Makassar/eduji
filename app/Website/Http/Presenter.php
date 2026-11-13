<?php

declare(strict_types=1);

namespace App\Website\Http;

use App\Cms\Downloads\Download;
use App\Cms\Events\Event;
use App\Cms\Posts\Post;
use App\Core\Media\MediaAsset;
use App\Website\Seo\SeoBuilder;

/**
 * Bentuk data konten untuk halaman publik: hanya field yang dipakai tampilan, tanpa id internal atau kolom admin.
 * Relasi media harus sudah di-eager-load (`cover.media`, dst.) oleh pemanggil.
 */
final class Presenter
{
    /** @return array{url: ?string, thumb: ?string, alt: string}|null */
    public static function media(?MediaAsset $asset): ?array
    {
        $file = $asset?->file();

        if ($file === null) {
            return null;
        }

        return [
            'url' => $file->getUrl(),
            'thumb' => $file->hasGeneratedConversion('thumb') ? $file->getUrl('thumb') : $file->getUrl(),
            'alt' => (string) ($file->getCustomProperty('alt') ?: $asset->title),
        ];
    }

    /** Alamat publik sebuah berita/pengumuman menurut jenisnya. */
    public static function postUrl(Post $post): string
    {
        return route($post->type === Post::TYPE_ANNOUNCEMENT ? 'site.announcements.show' : 'site.posts.show', $post->slug, absolute: false);
    }

    /** @return array<string, mixed> */
    public static function postCard(Post $post): array
    {
        return [
            'title' => $post->title,
            'href' => self::postUrl($post),
            'excerpt' => $post->excerpt ?: SeoBuilder::plain($post->body),
            'cover' => self::media($post->cover),
            'published_at' => $post->published_at?->toIso8601String(),
            'category' => $post->category?->only(['name', 'slug']),
        ];
    }

    /** @return array<string, mixed> */
    public static function eventCard(Event $event): array
    {
        return [
            'title' => $event->title,
            'href' => route('site.events.show', $event->slug, absolute: false),
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> butuh `file.media` di-eager-load */
    public static function download(Download $download): array
    {
        $file = $download->file?->file();

        return [
            'title' => $download->title,
            'description' => $download->description,
            'href' => route('site.downloads.file', $download, absolute: false),
            'type' => $file ? strtoupper(pathinfo($file->file_name, PATHINFO_EXTENSION)) : null,
            'size' => $file?->size,
            'count' => $download->download_count,
        ];
    }
}
