<?php

declare(strict_types=1);

namespace App\Website\Seo;

use App\Cms\Events\Event;
use App\Cms\Gallery\Gallery;
use App\Cms\Pages\Page;
use App\Cms\Posts\Post;
use App\Website\Http\Presenter;

/** URL publik bawaan platform. Module mendaftarkan URL-nya sendiri lewat SitemapRegistry::register(). */
final class DefaultSitemap
{
    public static function register(SitemapRegistry $registry): void
    {
        $limit = (int) config('website.sitemap_limit');

        $registry->register(fn () => collect(['home', 'site.posts.index', 'site.announcements.index', 'site.events.index', 'site.galleries.index', 'site.downloads.index', 'site.contact'])
            ->map(fn (string $route) => ['loc' => route($route)]));

        $registry->register(fn () => Post::published()->notExpired()->select(['type', 'slug', 'updated_at'])->latest('published_at')->limit($limit)->cursor()
            ->map(fn (Post $post) => ['loc' => url(Presenter::postUrl($post)), 'lastmod' => $post->updated_at]));

        $registry->register(fn () => Page::published()->select(['slug', 'updated_at'])->limit($limit)->cursor()
            ->map(fn (Page $page) => ['loc' => route('site.pages.show', $page->slug), 'lastmod' => $page->updated_at]));

        $registry->register(fn () => Event::active()->select(['slug', 'updated_at'])->limit($limit)->cursor()
            ->map(fn (Event $event) => ['loc' => route('site.events.show', $event->slug), 'lastmod' => $event->updated_at]));

        $registry->register(fn () => Gallery::published()->select(['slug', 'updated_at'])->limit($limit)->cursor()
            ->map(fn (Gallery $gallery) => ['loc' => route('site.galleries.show', $gallery->slug), 'lastmod' => $gallery->updated_at]));
    }
}
