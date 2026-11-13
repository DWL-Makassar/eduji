<?php

declare(strict_types=1);

namespace App\Cms\Events;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventController extends CrudController
{
    protected function key(): string
    {
        return 'events';
    }

    protected function modelClass(): string
    {
        return Event::class;
    }

    protected function requestClass(): string
    {
        return EventRequest::class;
    }

    protected function query(): Builder
    {
        return Event::query()->orderByDesc('starts_at');
    }

    protected function searchColumns(): array
    {
        return ['title', 'location'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::datetime('starts_at'), Column::text('location'), Column::boolean('is_active')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('slug', help: 'cms.slug_help'),
            Field::textarea('description', rows: 4),
            Field::text('location'),
            Field::datetime('starts_at', required: true),
            Field::datetime('ends_at'),
            Field::checkbox('is_active'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Event $record */
        return [
            'title' => $record->title,
            'starts_at' => $record->starts_at->toIso8601String(),
            'location' => $record->location,
            'is_active' => $record->is_active,
        ];
    }

    protected function values(?Model $record): array
    {
        /** @var Event|null $record */
        return [
            'title' => $record?->title ?? '',
            'slug' => $record?->slug ?? '',
            'description' => $record?->description ?? '',
            'location' => $record?->location ?? '',
            'starts_at' => $this->datetimeValue($record?->starts_at),
            'ends_at' => $this->datetimeValue($record?->ends_at),
            'is_active' => $record?->is_active ?? true,
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $event = $record ?? new Event;
        $event->fill([
            'title' => $data['title'],
            'slug' => Slug::resolve(Event::class, $data['slug'] ?? null, $data['title'], $record),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        return $event;
    }
}
