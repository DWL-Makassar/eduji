<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Modules\School\Models\Major;

class MajorController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-majors';
    }

    protected function modelClass(): string
    {
        return Major::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'code'];
    }

    protected function columns(): array
    {
        return [Column::text('name', label: 'school::messages.fields.name'), Column::text('code', label: 'school::messages.fields.code'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true, label: 'school::messages.fields.name'),
            Field::text('code', label: 'school::messages.fields.code'),
            Field::textarea('description', rows: 4, label: 'school::messages.fields.description'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:3000'],
            ...$this->commonRules(),
        ];
    }
}
