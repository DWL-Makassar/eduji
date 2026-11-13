<?php

declare(strict_types=1);

namespace App\Cms\Downloads;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DownloadController extends CrudController
{
    protected function key(): string
    {
        return 'downloads';
    }

    protected function modelClass(): string
    {
        return Download::class;
    }

    protected function requestClass(): string
    {
        return DownloadRequest::class;
    }

    protected function query(): Builder
    {
        return Download::query()->latest('id');
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::text('download_count'), Column::boolean('is_active')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::textarea('description', rows: 3),
            Field::media('media_asset_id', required: true),
            Field::checkbox('is_active'),
        ];
    }

    protected function row(Model $record): array
    {
        /** @var Download $record */
        return ['title' => $record->title, 'download_count' => $record->download_count, 'is_active' => $record->is_active];
    }

    protected function values(?Model $record): array
    {
        /** @var Download|null $record */
        return [
            'title' => $record?->title ?? '',
            'description' => $record?->description ?? '',
            'media_asset_id' => $this->mediaValue($record?->file),
            'is_active' => $record?->is_active ?? true,
        ];
    }

    protected function persist(?Model $record, array $data): Model
    {
        $download = $record ?? new Download;
        $download->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'media_asset_id' => $data['media_asset_id'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        return $download;
    }
}
