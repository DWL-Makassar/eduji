<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\School\Models\Achievement;

class AchievementController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-achievements';
    }

    protected function modelClass(): string
    {
        return Achievement::class;
    }

    protected function searchColumns(): array
    {
        return ['title', 'winner'];
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::badge('level', label: 'school::messages.fields.level'), Column::text('year', label: 'school::messages.fields.year'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::select('level', array_combine(Achievement::LEVELS, array_map(fn (string $l) => "school::messages.levels.{$l}", Achievement::LEVELS)), required: true, label: 'school::messages.fields.level'),
            Field::text('winner', label: 'school::messages.fields.winner'),
            Field::number('year', label: 'school::messages.fields.year'),
            Field::textarea('description', rows: 3, label: 'school::messages.fields.description'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::in(Achievement::LEVELS)],
            'winner' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'description' => ['nullable', 'string', 'max:2000'],
            ...$this->commonRules(),
        ];
    }

    protected function display(Model $record, string $key): mixed
    {
        return $key === 'level' ? __("school::messages.levels.{$record->level}") : $record->{$key};
    }
}
