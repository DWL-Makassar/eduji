<?php

declare(strict_types=1);

namespace App\Cms\Testimonials;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TestimonialController extends CrudController
{
    protected function key(): string
    {
        return 'testimonials';
    }

    protected function modelClass(): string
    {
        return Testimonial::class;
    }

    protected function requestClass(): string
    {
        return TestimonialRequest::class;
    }

    protected function query(): Builder
    {
        return Testimonial::query()->orderBy('order')->orderByDesc('id');
    }

    protected function searchColumns(): array
    {
        return ['name', 'role'];
    }

    protected function columns(): array
    {
        return [Column::text('name'), Column::text('role'), Column::boolean('is_active')];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true),
            Field::text('role'),
            Field::textarea('body', required: true),
            Field::text('year'),
            Field::media('photo_media_id'),
            Field::number('order'),
            Field::checkbox('is_active'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Testimonial $record */
        return ['name' => $record->name, 'role' => $record->role, 'is_active' => $record->is_active];
    }

    protected function values(?Model $record): array
    {
        /** @var Testimonial|null $record */
        return [
            'name' => $record?->name ?? '',
            'role' => $record?->role ?? '',
            'body' => $record?->body ?? '',
            'year' => $record?->year ?? '',
            'photo_media_id' => $this->mediaValue($record?->photo),
            'order' => $record?->order ?? 0,
            'is_active' => $record?->is_active ?? true,
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $testimonial = $record ?? new Testimonial;
        $testimonial->fill([
            'name' => $data['name'],
            'role' => $data['role'] ?? null,
            'body' => $data['body'],
            'year' => $data['year'] ?? null,
            'photo_media_id' => $data['photo_media_id'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        return $testimonial;
    }
}
