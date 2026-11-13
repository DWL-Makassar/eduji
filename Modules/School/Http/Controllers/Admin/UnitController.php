<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Menus\MenuRequest;
use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\School\Models\Unit;

class UnitController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-units';
    }

    protected function modelClass(): string
    {
        return Unit::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'npsn', 'principal'];
    }

    protected function columns(): array
    {
        return [Column::text('name', label: 'school::messages.fields.name'), Column::badge('level', label: 'school::messages.fields.unit_level'), Column::text('npsn', label: 'school::messages.fields.npsn'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true, label: 'school::messages.fields.name'),
            Field::select('level', array_combine(Unit::LEVELS, array_map(fn (string $l) => "school::messages.unit_levels.{$l}", Unit::LEVELS)), blank: true, label: 'school::messages.fields.unit_level'),
            Field::text('npsn', label: 'school::messages.fields.npsn'),
            Field::text('principal', label: 'school::messages.fields.principal'),
            Field::text('phone', label: 'school::messages.fields.phone'),
            Field::textarea('address', rows: 2, label: 'school::messages.fields.address'),
            Field::url('url', label: 'school::messages.fields.url'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', Rule::in(Unit::LEVELS)],
            'npsn' => ['nullable', 'string', 'max:16'],
            'principal' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:'.MenuRequest::URL_PATTERN],
            ...$this->commonRules(),
        ];
    }

    protected function display(Model $record, string $key): mixed
    {
        return $key === 'level' && $record->level ? __("school::messages.unit_levels.{$record->level}") : $record->{$key};
    }
}
