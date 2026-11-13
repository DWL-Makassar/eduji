<?php

declare(strict_types=1);

namespace App\Cms\Gallery;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryController extends CrudController
{
    protected function key(): string
    {
        return 'galleries';
    }

    protected function modelClass(): string
    {
        return Gallery::class;
    }

    protected function requestClass(): string
    {
        return GalleryRequest::class;
    }

    protected function query(): Builder
    {
        return Gallery::query()->withCount('items')->latest('id');
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::badge('status'), Column::text('items_count')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('slug', help: 'cms.slug_help'),
            Field::textarea('description', rows: 3),
            Field::select('status', ['draft' => 'cms.status.draft', 'published' => 'cms.status.published'], required: true),
            Field::repeater('items', [Field::media('media_asset_id', required: true), Field::text('caption')], itemLabel: 'cms.photo'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Gallery $record */
        return ['title' => $record->title, 'status' => $record->status, 'items_count' => $record->items_count];
    }

    protected function values(?Model $record): array
    {
        /** @var Gallery|null $record */
        return [
            'title' => $record?->title ?? '',
            'slug' => $record?->slug ?? '',
            'description' => $record?->description ?? '',
            'status' => $record?->status ?? Gallery::DRAFT,
            'items' => $record ? $record->items()->with('media.media')->get()->map(fn (GalleryItem $item) => [
                'media_asset_id' => $this->mediaValue($item->media),
                'caption' => $item->caption ?? '',
            ])->all() : [],
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $gallery = $record ?? new Gallery;
        $gallery->fill([
            'title' => $data['title'],
            'slug' => Slug::resolve(Gallery::class, $data['slug'] ?? null, $data['title'], $record),
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
        ])->save();

        $this->replaceChildren($gallery->items(), $data['items'] ?? [], fn (array $row) => [
            'media_asset_id' => $row['media_asset_id'],
            'caption' => $row['caption'] ?? null,
        ]);

        return $gallery;
    }
}
