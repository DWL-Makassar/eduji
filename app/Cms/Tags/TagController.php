<?php

declare(strict_types=1);

namespace App\Cms\Tags;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TagController extends CrudController
{
    protected function key(): string
    {
        return 'tags';
    }

    protected function modelClass(): string
    {
        return Tag::class;
    }

    protected function requestClass(): string
    {
        return TagRequest::class;
    }

    protected function query(): Builder
    {
        return Tag::query()->orderBy('name');
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }

    protected function columns(): array
    {
        return [Column::text('name'), Column::text('slug')];
    }

    protected function fields(): array
    {
        return [Field::text('name', required: true), Field::text('slug', help: 'cms.slug_help')];
    }

    protected function row(Model $record): array
    {
        /** @var Tag $record */
        return ['name' => $record->name, 'slug' => $record->slug];
    }

    protected function values(?Model $record): array
    {
        /** @var Tag|null $record */
        return ['name' => $record?->name ?? '', 'slug' => $record?->slug ?? ''];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $tag = $record ?? new Tag;
        $tag->fill([
            'name' => $data['name'],
            'slug' => Slug::resolve(Tag::class, $data['slug'] ?? null, $data['name'], $record),
        ])->save();

        return $tag;
    }
}
