<?php

declare(strict_types=1);

namespace App\Cms\Sliders;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SliderController extends CrudController
{
    protected function key(): string
    {
        return 'sliders';
    }

    protected function modelClass(): string
    {
        return Slider::class;
    }

    protected function requestClass(): string
    {
        return SliderRequest::class;
    }

    protected function query(): Builder
    {
        return Slider::query()->withCount('slides')->orderBy('title');
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::text('key'), Column::text('slides_count')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('key', required: true, help: 'cms.key_help'),
            Field::repeater('slides', [
                Field::media('image_media_id'),
                Field::text('title'),
                Field::text('subtitle'),
                Field::text('cta_label'),
                Field::text('cta_url'),
                Field::checkbox('is_active'),
            ], itemLabel: 'cms.slide'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Slider $record */
        return ['title' => $record->title, 'key' => $record->key, 'slides_count' => $record->slides_count];
    }

    protected function values(?Model $record): array
    {
        /** @var Slider|null $record */
        return [
            'title' => $record?->title ?? '',
            'key' => $record?->key ?? '',
            'slides' => $record ? $record->slides()->with('image.media')->get()->map(fn (Slide $slide) => [
                'image_media_id' => $this->mediaValue($slide->image),
                'title' => $slide->title ?? '',
                'subtitle' => $slide->subtitle ?? '',
                'cta_label' => $slide->cta_label ?? '',
                'cta_url' => $slide->cta_url ?? '',
                'is_active' => $slide->is_active,
            ])->all() : [],
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $slider = $record ?? new Slider;
        $slider->fill(['title' => $data['title'], 'key' => $data['key']])->save();

        $this->replaceChildren($slider->slides(), $data['slides'] ?? [], fn (array $row) => [
            'image_media_id' => $row['image_media_id'] ?? null,
            'title' => $row['title'] ?? null,
            'subtitle' => $row['subtitle'] ?? null,
            'cta_label' => $row['cta_label'] ?? null,
            'cta_url' => $row['cta_url'] ?? null,
            'is_active' => (bool) ($row['is_active'] ?? true),
        ]);

        return $slider;
    }
}
