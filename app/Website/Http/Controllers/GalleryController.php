<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Cms\Gallery\Gallery;
use App\Cms\Gallery\GalleryItem;
use App\Core\Support\Paginate;
use App\Website\Http\Presenter;
use App\Website\Seo\SeoBuilder;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController
{
    public function __construct(private readonly SeoBuilder $seo) {}

    public function index(): Response
    {
        $galleries = Gallery::published()
            ->with('items.media.media')
            ->withCount('items')
            ->latest('id')
            ->paginate(config('website.per_page'));

        return Inertia::render('site/galleries/Index', [
            'galleries' => Paginate::shape($galleries, fn (Gallery $gallery) => [
                'title' => $gallery->title,
                'href' => route('site.galleries.show', $gallery->slug, absolute: false),
                'count' => $gallery->items_count,
                'cover' => Presenter::media($gallery->items->first()?->media),
            ]),
            'seo' => $this->seo->make(__('website.galleries'))->toArray(),
        ]);
    }

    public function show(string $slug): Response
    {
        $gallery = Gallery::published()->with('items.media.media')->where('slug', $slug)->firstOrFail();

        $photos = $gallery->items->map(fn (GalleryItem $item) => ['caption' => $item->caption] + (Presenter::media($item->media) ?? []))
            ->filter(fn (array $photo) => isset($photo['url']))
            ->values();

        return Inertia::render('site/galleries/Show', [
            'gallery' => ['title' => $gallery->title, 'description' => $gallery->description, 'photos' => $photos->all()],
            'seo' => $this->seo->make($gallery->title, $gallery->description, $photos->first()['url'] ?? null)->toArray(),
        ]);
    }
}
