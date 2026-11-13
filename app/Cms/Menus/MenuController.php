<?php

declare(strict_types=1);

namespace App\Cms\Menus;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MenuController extends CrudController
{
    protected function key(): string
    {
        return 'menus';
    }

    protected function modelClass(): string
    {
        return Menu::class;
    }

    protected function requestClass(): string
    {
        return MenuRequest::class;
    }

    protected function query(): Builder
    {
        return Menu::query()->withCount('items')->orderBy('name');
    }

    protected function columns(): array
    {
        return [Column::text('name'), Column::text('location'), Column::text('items_count')];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true),
            Field::select('location', config('cms.menu_locations'), required: true),
            Field::repeater('items', [
                Field::text('label', required: true),
                Field::text('url', required: true),
                Field::select('target', ['_self' => 'cms.target.same', '_blank' => 'cms.target.new']),
                Field::select('level', ['0' => 'cms.level.main', '1' => 'cms.level.sub']),
                Field::checkbox('is_active'),
            ], itemLabel: 'cms.menu_item'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Menu $record */
        return [
            'name' => $record->name,
            'location' => __(config("cms.menu_locations.{$record->location}", $record->location)),
            'items_count' => $record->items_count,
        ];
    }

    protected function values(?Model $record): array
    {
        /** @var Menu|null $record */
        return [
            'name' => $record?->name ?? '',
            'location' => $record?->location ?? 'header',
            'items' => $record ? $record->items->map(fn (MenuItem $item) => [
                'label' => $item->label,
                'url' => $item->url,
                'target' => $item->target,
                'level' => $item->parent_id ? '1' : '0',
                'is_active' => $item->is_active,
            ])->all() : [],
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $menu = $record ?? new Menu;
        $menu->fill(['name' => $data['name'], 'location' => $data['location']])->save();

        $menu->items()->delete();

        $parentId = null;

        // Level 1 = sub-menu dari item level 0 terdekat di atasnya (maks. 2 level).
        foreach (array_values($data['items'] ?? []) as $index => $row) {
            $isChild = (int) $row['level'] === 1 && $parentId !== null;

            $item = $menu->items()->create([
                'parent_id' => $isChild ? $parentId : null,
                'label' => $row['label'],
                'url' => $row['url'],
                'target' => $row['target'],
                'is_active' => (bool) ($row['is_active'] ?? true),
                'order' => $index,
            ]);

            if (! $isChild) {
                $parentId = $item->id;
            }
        }

        return $menu;
    }
}
