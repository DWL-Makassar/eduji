<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\CrudController;
use App\Cms\Support\Field;
use App\Core\Media\Rules\ImageAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\School\Http\Requests\SchoolRequest;
use Modules\School\Models\SchoolRecord;

/**
 * CRUD data sekolah. Kolom tabel = nama field, jadi nilai form dan penyimpanan diturunkan dari fields();
 * turunan cukup menyebut key, model, kolom daftar, field form, dan aturan validasi (rules()).
 */
abstract class SchoolCrudController extends CrudController
{
    /** @return array<string, list<mixed>> aturan validasi, dipakai SchoolRequest */
    abstract public function rules(): array;

    protected function title(): string
    {
        return __("school::messages.resources.{$this->key()}.title");
    }

    protected function singular(): string
    {
        return __("school::messages.resources.{$this->key()}.singular");
    }

    protected function requestClass(): string
    {
        return SchoolRequest::class;
    }

    protected function query(): Builder
    {
        return $this->modelClass()::query()->ordered();
    }

    /** Kolom bersama di akhir setiap daftar. @return list<array{key: string, label: string, type: string}> */
    protected function tail(): array
    {
        return [Column::text('order', label: 'school::messages.fields.order'), Column::boolean('is_active')];
    }

    /** Field bersama di akhir setiap form. @return list<array<string, mixed>> */
    protected function commonFields(): array
    {
        return [
            Field::media('image_media_id', label: 'school::messages.fields.image'),
            Field::number('order', label: 'school::messages.fields.order'),
            Field::checkbox('is_active'),
        ];
    }

    /** @return array<string, list<mixed>> aturan field bersama */
    protected function commonRules(): array
    {
        return [
            'image_media_id' => ['nullable', 'integer', new ImageAsset],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }

    protected function row(Model $record): array
    {
        return collect($this->columns())->mapWithKeys(fn (array $column) => [$column['key'] => $this->display($record, $column['key'])])->all();
    }

    protected function values(?Model $record): array
    {
        /** @var SchoolRecord|null $record */
        return collect($this->fields())->mapWithKeys(fn (array $field) => [$field['name'] => match ($field['type']) {
            'media' => $this->mediaValue($record?->image),
            'checkbox' => $record?->{$field['name']} ?? true,
            // Select wajib harus langsung berisi pilihan yang valid (pilihan pertama; "—" untuk select opsional).
            'select' => $record?->{$field['name']} ?? $field['options'][0]['value'],
            default => $record?->{$field['name']} ?? '',
        }])->all();
    }

    protected function persist(?Model $record, array $data): Model
    {
        $model = $record ?? new ($this->modelClass());

        foreach ($this->fields() as $field) {
            $value = $data[$field['name']] ?? null;
            $model->{$field['name']} = match ($field['type']) {
                'checkbox' => (bool) $value,
                'number' => $value === null || $value === '' ? ($field['name'] === 'order' ? 0 : null) : (int) $value,
                default => $value === '' ? null : $value,
            };
        }

        $model->save();

        return $model;
    }

    /** Nilai kolom di daftar admin; turunan menimpa untuk kolom terjemahan (mis. jenjang). */
    protected function display(Model $record, string $key): mixed
    {
        return $record->{$key};
    }
}
