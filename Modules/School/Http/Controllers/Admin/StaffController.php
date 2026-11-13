<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\School\Models\Staff;

class StaffController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-staff';
    }

    protected function modelClass(): string
    {
        return Staff::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'position', 'subject'];
    }

    protected function columns(): array
    {
        return [Column::text('name', label: 'school::messages.fields.name'), Column::badge('type', label: 'school::messages.fields.type'), Column::text('position', label: 'school::messages.fields.position'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true, label: 'school::messages.fields.name'),
            Field::select('type', array_combine(Staff::TYPES, array_map(fn (string $t) => "school::messages.types.{$t}", Staff::TYPES)), required: true, label: 'school::messages.fields.type'),
            Field::text('position', label: 'school::messages.fields.position'),
            Field::text('subject', label: 'school::messages.fields.subject'),
            Field::text('nip', label: 'school::messages.fields.nip'),
            Field::textarea('bio', rows: 3, label: 'school::messages.fields.bio'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Staff::TYPES)],
            'position' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:2000'],
            ...$this->commonRules(),
        ];
    }

    protected function display(Model $record, string $key): mixed
    {
        return $key === 'type' ? __("school::messages.types.{$record->type}") : $record->{$key};
    }
}
