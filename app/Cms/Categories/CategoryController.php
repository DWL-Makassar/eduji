<?php

declare(strict_types=1);

namespace App\Cms\Categories;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CategoryController extends CrudController
{
    protected function key(): string
    {
        return 'categories';
    }

    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function requestClass(): string
    {
        return CategoryRequest::class;
    }

    protected function query(): Builder
    {
        return Category::query()->where('type', Category::TYPE_POST)->orderBy('order')->orderBy('name');
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }

    protected function columns(): array
    {
        return [Column::text('name'), Column::text('slug'), Column::text('order')];
    }

    protected function fields(): array
    {
        return [Field::text('name', required: true), Field::text('slug', help: 'cms.slug_help'), Field::number('order')];
    }

    protected function row(Model $record): array
    {
        /** @var Category $record */
        return ['name' => $record->name, 'slug' => $record->slug, 'order' => $record->order];
    }

    protected function values(?Model $record): array
    {
        /** @var Category|null $record */
        return ['name' => $record?->name ?? '', 'slug' => $record?->slug ?? '', 'order' => $record?->order ?? 0];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $category = $record ?? new Category(['type' => Category::TYPE_POST]);
        $category->fill([
            'name' => $data['name'],
            'slug' => Slug::resolve(Category::class, $data['slug'] ?? null, $data['name'], $record, ['type' => Category::TYPE_POST]),
            'order' => $data['order'] ?? 0,
        ])->save();

        return $category;
    }
}
