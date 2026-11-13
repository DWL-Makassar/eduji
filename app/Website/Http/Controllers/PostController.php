<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Cms\Categories\Category;
use App\Cms\Posts\Post;
use App\Core\Settings\Branding;
use App\Core\Support\Paginate;
use App\Website\Http\Presenter;
use App\Website\Seo\JsonLd;
use App\Website\Seo\SeoBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Berita dan pengumuman berbagi controller; jenisnya dari default route (`type`). */
class PostController
{
    public function __construct(private readonly SeoBuilder $seo) {}

    public function index(Request $request, string $type): Response
    {
        $search = trim((string) $request->query('q'));
        $category = (string) $request->query('category');
        $tag = (string) $request->query('tag');

        $posts = $this->published($type)
            ->with(['cover.media', 'category:id,name,slug'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->whereRaw("title like ? escape '!'", [$like = $this->likePattern($search)])
                ->orWhereRaw("excerpt like ? escape '!'", [$like])))
            ->when($category !== '', fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $category)))
            ->when($tag !== '', fn (Builder $q) => $q->whereHas('tags', fn (Builder $t) => $t->where('slug', $tag)))
            // Urutan ini dilayani indeks posts_public_list_index (type, status, is_pinned, published_at): ubah bersamaan.
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(config('website.per_page'))
            ->withQueryString();

        return Inertia::render('site/posts/Index', [
            'kind' => $type,
            'posts' => Paginate::shape($posts, Presenter::postCard(...)),
            'filters' => ['q' => $search, 'category' => $category, 'tag' => $tag],
            'categories' => $type === Post::TYPE_POST
                ? Category::query()->where('type', Category::TYPE_POST)->orderBy('order')->orderBy('name')->get(['name', 'slug'])
                : [],
            'seo' => $this->seo->make(__($type === Post::TYPE_POST ? 'website.news' : 'website.announcements'))->toArray(),
        ]);
    }

    /** Urutan argumen mengikuti parameter route: {slug} dari URI dulu, baru default `type` (Laravel memetakan berdasarkan urutan). */
    public function show(string $slug, string $type, Branding $branding): Response
    {
        $post = $this->published($type)
            ->with(['cover.media', 'category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->where('slug', $slug)
            ->firstOrFail();

        $card = Presenter::postCard($post);
        $description = $post->seo['description'] ?? $card['excerpt'];
        $image = $card['cover']['url'] ?? null;
        $url = url(Presenter::postUrl($post));

        return Inertia::render('site/posts/Show', [
            'kind' => $type,
            'post' => $card + [
                'body' => $post->body,
                'author' => $post->author?->name,
                'tags' => $post->tags->map->only(['name', 'slug'])->all(),
            ],
            'related' => $this->published($type)
                ->with(['cover.media', 'category:id,name,slug'])
                ->whereKeyNot($post->getKey())
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map(Presenter::postCard(...))
                ->all(),
            'seo' => $this->seo->make(
                $post->seo['title'] ?? $post->title,
                $description,
                $image,
                'article',
                [JsonLd::article($post->title, $description, $url, $image, $post->published_at, $post->updated_at, $post->author?->name, $branding->siteName())],
            )->toArray(),
        ]);
    }

    /** Pola LIKE "mengandung"; `%` dan `_` dari pengguna dianggap huruf biasa. `!` sebagai escape agar sama di MySQL dan SQLite. */
    private function likePattern(string $search): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
    }

    /** Terbit, sudah waktunya tayang, dan belum kedaluwarsa. @return Builder<Post> */
    private function published(string $type): Builder
    {
        return Post::ofType($type)->published()->notExpired();
    }
}
