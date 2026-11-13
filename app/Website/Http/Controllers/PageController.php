<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Cms\Pages\Page;
use App\Website\Sections\PageBlock;
use App\Website\Sections\SectionRegistry;
use App\Website\Seo\SeoBuilder;
use Inertia\Inertia;
use Inertia\Response;

class PageController
{
    public function show(string $slug, SeoBuilder $seo, SectionRegistry $sections): Response
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('site/pages/Show', [
            'page' => ['title' => $page->title, 'body' => $page->body],
            // Blok page builder tampil di bawah isi halaman.
            'sections' => $sections->resolve(PageBlock::layout($page->id) ?? []),
            'seo' => $seo->make($page->seo['title'] ?? $page->title, $page->seo['description'] ?? SeoBuilder::plain($page->body))->toArray(),
        ]);
    }
}
