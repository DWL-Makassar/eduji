<?php

declare(strict_types=1);

namespace App\Cms\Pages;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Support\Sanitizer;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Model;

class PageController extends CrudController
{
    protected function key(): string
    {
        return 'pages';
    }

    protected function modelClass(): string
    {
        return Page::class;
    }

    protected function requestClass(): string
    {
        return PageRequest::class;
    }

    protected function searchColumns(): array
    {
        return ['title', 'slug'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::text('slug'), Column::badge('status'), Column::datetime('published_at')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('slug', help: 'cms.slug_help'),
            Field::richtext('body'),
            Field::select('status', ['draft' => 'cms.status.draft', 'published' => 'cms.status.published'], required: true),
            Field::datetime('published_at'),
            Field::text('seo_title'),
            Field::textarea('seo_description', rows: 2),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Page $record */
        return [
            'title' => $record->title,
            'slug' => $record->slug,
            'status' => $record->status,
            'published_at' => $record->published_at?->toIso8601String(),
        ];
    }

    protected function values(?Model $record): array
    {
        /** @var Page|null $record */
        return [
            'title' => $record?->title ?? '',
            'slug' => $record?->slug ?? '',
            'body' => $record?->body ?? '',
            'status' => $record?->status ?? Page::DRAFT,
            'published_at' => $this->datetimeValue($record?->published_at),
            'seo_title' => $record?->seo['title'] ?? '',
            'seo_description' => $record?->seo['description'] ?? '',
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $data = Page::withPublishDate($data);

        $page = $record ?? new Page;
        $page->fill([
            'title' => $data['title'],
            'slug' => Slug::resolve(Page::class, $data['slug'] ?? null, $data['title'], $record),
            'body' => Sanitizer::html($data['body'] ?? null),
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'seo' => $this->seoFrom($data),
        ])->save();

        return $page;
    }
}
