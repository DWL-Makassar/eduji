<?php

declare(strict_types=1);

namespace App\Website\Sections;

use App\Cms\Events\Event;
use App\Cms\Features\Feature;
use App\Cms\Gallery\Gallery;
use App\Cms\Posts\Post;
use App\Cms\Sliders\Slide;
use App\Cms\Sliders\Slider;
use App\Cms\Support\Field;
use App\Cms\Testimonials\Testimonial;
use App\Website\Http\Presenter;

/** Section bawaan platform. Module menambah section-nya sendiri lewat SectionRegistry::register(). */
final class DefaultSections
{
    public static function register(SectionRegistry $sections): void
    {
        // Section daftar: judul bisa diganti (kosong = judul bawaan) dan jumlah item bisa diatur.
        // Field lewat closure (dijalankan per permintaan) agar labelnya mengikuti bahasa admin.
        $list = fn () => [
            Field::text('title', label: 'website.fields.title', help: 'website.fields.title_help'),
            Field::number('limit', label: 'website.fields.limit'),
        ];
        $limit = fn (array $s, int $default) => (int) ($s['limit'] ?? $default);

        $sections->register('hero', 'website.sections.hero', fn () => Slider::query()
            ->where('key', config('website.hero_slider'))
            ->first()
            ?->slides()->where('is_active', true)->with('image.media')->get()
            ->map(fn (Slide $slide) => [
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'cta_label' => $slide->cta_label,
                'cta_url' => $slide->cta_url,
                'image' => Presenter::media($slide->image),
            ])->all(), 10, icon: 'image');

        $sections->register('features', 'website.sections.features', fn (array $s) => Feature::active()->limit($limit($s, 8))->get()
            ->map(fn (Feature $feature) => $feature->only(['title', 'description', 'icon', 'url']))->all(), 20, $list, ['default' => 'website.variants.cards', 'plain' => 'website.variants.plain'], 'star');

        $sections->register('announcements', 'website.sections.announcements', fn (array $s) => Post::ofType(Post::TYPE_ANNOUNCEMENT)
            ->published()->notExpired()->orderByDesc('is_pinned')->latest('published_at')->limit($limit($s, 5))->get()
            ->map(fn (Post $post) => [
                'title' => $post->title,
                'href' => Presenter::postUrl($post),
                'published_at' => $post->published_at?->toIso8601String(),
            ])->all(), 30, $list, icon: 'megaphone');

        $sections->register('posts', 'website.sections.posts', fn (array $s) => Post::ofType(Post::TYPE_POST)
            ->published()->notExpired()->with(['cover.media', 'category:id,name,slug'])
            ->orderByDesc('is_pinned')->latest('published_at')->limit($limit($s, 6))->get()
            ->map(Presenter::postCard(...))->all(), 40, $list, ['default' => 'website.variants.grid', 'list' => 'website.variants.list'], 'newspaper');

        $sections->register('events', 'website.sections.events', fn (array $s) => Event::active()->upcoming()->limit($limit($s, 4))->get()
            ->map(Presenter::eventCard(...))->all(), 50, $list, ['default' => 'website.variants.cards', 'list' => 'website.variants.list'], 'calendar');

        $sections->register('galleries', 'website.sections.galleries', fn (array $s) => Gallery::published()
            ->with('items.media.media')->latest('id')->limit($limit($s, 4))->get()
            ->map(fn (Gallery $gallery) => [
                'title' => $gallery->title,
                'href' => route('site.galleries.show', $gallery->slug, absolute: false),
                'cover' => Presenter::media($gallery->items->first()?->media),
            ])->all(), 60, $list, icon: 'image');

        $sections->register('testimonials', 'website.sections.testimonials', fn (array $s) => Testimonial::active()->with('photo.media')->limit($limit($s, 6))->get()
            ->map(fn (Testimonial $testimonial) => [
                'name' => $testimonial->name,
                'role' => $testimonial->role,
                'body' => $testimonial->body,
                'year' => $testimonial->year,
                'photo' => Presenter::media($testimonial->photo),
            ])->all(), 70, $list, icon: 'quote');

        // Section isi bebas: seluruh isinya dari pengaturan blok.
        $sections->register('text', 'website.sections.text', fn (array $s) => filled($s['title']) || filled($s['body']), 80, fn () => [
            Field::text('title', label: 'website.fields.title'),
            Field::richtext('body', label: 'website.fields.body'),
        ], ['default' => 'website.variants.default', 'center' => 'website.variants.center'], 'file', automatic: false);

        $sections->register('cta', 'website.sections.cta', fn (array $s) => filled($s['title']), 90, fn () => [
            Field::text('title', required: true, label: 'website.fields.title'),
            Field::textarea('text', rows: 3, label: 'website.fields.text'),
            Field::text('button_label', label: 'website.fields.button_label'),
            Field::url('button_url', label: 'website.fields.button_url'),
            Field::media('image', label: 'website.fields.image'),
        ], ['default' => 'website.variants.solid', 'light' => 'website.variants.light'], 'megaphone', automatic: false);
    }
}
