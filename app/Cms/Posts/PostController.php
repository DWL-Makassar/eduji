<?php

declare(strict_types=1);

namespace App\Cms\Posts;

use App\Cms\Categories\Category;
use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Cms\Tags\Tag;
use App\Core\Support\Sanitizer;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PostController extends CrudController
{
    protected function key(): string
    {
        return 'posts';
    }

    protected function type(): string
    {
        return Post::TYPE_POST;
    }

    protected function modelClass(): string
    {
        return Post::class;
    }

    protected function requestClass(): string
    {
        return PostRequest::class;
    }

    protected function query(): Builder
    {
        return Post::query()->ofType($this->type())->with('category')->latest('id');
    }

    protected function find(string $id): Model
    {
        return Post::query()->ofType($this->type())->findOrFail($id);
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::text('category'), Column::badge('status'), Column::datetime('published_at')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('slug', help: 'cms.slug_help'),
            Field::textarea('excerpt', rows: 3),
            Field::richtext('body'),
            Field::media('cover_media_id'),
            Field::select('category_id', Category::query()->where('type', Category::TYPE_POST)->orderBy('name')->pluck('name', 'id')->all(), blank: true, translate: false),
            Field::multiselect('tag_ids', Tag::query()->orderBy('name')->pluck('name', 'id')->all()),
            Field::select('status', ['draft' => 'cms.status.draft', 'published' => 'cms.status.published'], required: true),
            Field::datetime('published_at'),
            Field::checkbox('is_pinned'),
            Field::text('seo_title'),
            Field::textarea('seo_description', rows: 2),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Post $record */
        return [
            'title' => $record->title,
            'category' => $record->category?->name,
            'status' => $record->status,
            'published_at' => $record->published_at?->toIso8601String(),
            'expires_at' => $record->expires_at?->toIso8601String(),
        ];
    }

    protected function values(?Model $record): array
    {
        /** @var Post|null $record */
        return [
            'title' => $record?->title ?? '',
            'slug' => $record?->slug ?? '',
            'excerpt' => $record?->excerpt ?? '',
            'body' => $record?->body ?? '',
            'cover_media_id' => $this->mediaValue($record?->cover),
            'category_id' => $record?->category_id ? (string) $record->category_id : '',
            'tag_ids' => $record ? $record->tags()->pluck('tags.id')->map(fn ($id) => (string) $id)->all() : [],
            'status' => $record?->status ?? Post::DRAFT,
            'published_at' => $this->datetimeValue($record?->published_at),
            'expires_at' => $this->datetimeValue($record?->expires_at),
            'is_pinned' => $record?->is_pinned ?? false,
            'seo_title' => $record?->seo['title'] ?? '',
            'seo_description' => $record?->seo['description'] ?? '',
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $data = Post::withPublishDate($data);

        $post = $record ?? new Post(['type' => $this->type(), 'author_id' => auth()->id()]);
        $post->fill([
            'title' => $data['title'],
            'slug' => Slug::resolve(Post::class, $data['slug'] ?? null, $data['title'], $record, ['type' => $this->type()]),
            'excerpt' => isset($data['excerpt']) ? Str::of(strip_tags($data['excerpt']))->trim()->toString() : null,
            'body' => Sanitizer::html($data['body'] ?? null),
            'cover_media_id' => $data['cover_media_id'] ?? null,
            'category_id' => $this->type() === Post::TYPE_POST ? ($data['category_id'] ?? null) : null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'seo' => $this->seoFrom($data),
        ])->save();

        if ($this->type() === Post::TYPE_POST) {
            $post->tags()->sync(Arr::wrap($data['tag_ids'] ?? []));
        }

        return $post;
    }
}
