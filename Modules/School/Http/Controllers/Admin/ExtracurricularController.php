<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Modules\School\Models\Extracurricular;

class ExtracurricularController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-extracurriculars';
    }

    protected function modelClass(): string
    {
        return Extracurricular::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'coach'];
    }

    protected function columns(): array
    {
        return [Column::text('name', label: 'school::messages.fields.name'), Column::text('coach', label: 'school::messages.fields.coach'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true, label: 'school::messages.fields.name'),
            Field::text('coach', label: 'school::messages.fields.coach'),
            Field::text('schedule', label: 'school::messages.fields.schedule'),
            Field::textarea('description', rows: 3, label: 'school::messages.fields.description'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'coach' => ['nullable', 'string', 'max:255'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            ...$this->commonRules(),
        ];
    }
}
