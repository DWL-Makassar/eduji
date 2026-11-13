<?php

declare(strict_types=1);

namespace App\Cms\Features;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeatureController extends CrudController
{
    protected function key(): string
    {
        return 'features';
    }

    protected function modelClass(): string
    {
        return Feature::class;
    }

    protected function requestClass(): string
    {
        return FeatureRequest::class;
    }

    protected function query(): Builder
    {
        return Feature::query()->orderBy('order')->orderByDesc('id');
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::text('order'), Column::boolean('is_active')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::textarea('description', rows: 3),
            Field::text('icon', help: 'cms.icon_help'),
            Field::text('url'),
            Field::number('order'),
            Field::checkbox('is_active'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Feature $record */
        return ['title' => $record->title, 'order' => $record->order, 'is_active' => $record->is_active];
    }

    protected function values(?Model $record): array
    {
        /** @var Feature|null $record */
        return [
            'title' => $record?->title ?? '',
            'description' => $record?->description ?? '',
            'icon' => $record?->icon ?? '',
            'url' => $record?->url ?? '',
            'order' => $record?->order ?? 0,
            'is_active' => $record?->is_active ?? true,
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $feature = $record ?? new Feature;
        $feature->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'url' => $data['url'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        return $feature;
    }
}
