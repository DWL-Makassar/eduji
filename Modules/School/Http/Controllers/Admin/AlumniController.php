<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Cms\Support\Field;
use Modules\School\Models\Alumnus;

class AlumniController extends SchoolCrudController
{
    protected function key(): string
    {
        return 'school-alumni';
    }

    protected function modelClass(): string
    {
        return Alumnus::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'institution', 'email'];
    }

    protected function columns(): array
    {
        return [Column::text('name', label: 'school::messages.fields.name'), Column::text('graduation_year', label: 'school::messages.fields.graduation_year'), Column::text('activity', label: 'school::messages.fields.activity'), ...$this->tail()];
    }

    protected function fields(): array
    {
        return [
            Field::text('name', required: true, label: 'school::messages.fields.name'),
            Field::number('graduation_year', label: 'school::messages.fields.graduation_year'),
            Field::text('activity', label: 'school::messages.fields.activity'),
            Field::text('institution', label: 'school::messages.fields.institution'),
            Field::textarea('testimony', rows: 3, label: 'school::messages.fields.testimony'),
            Field::text('email', label: 'school::messages.fields.email', help: 'school::messages.fields.private_help'),
            Field::text('phone', label: 'school::messages.fields.phone', help: 'school::messages.fields.private_help'),
            ...$this->commonFields(),
        ];
    }

    public function rules(): array
    {
        return [...self::alumniRules(), ...$this->commonRules()];
    }

    /** Aturan bersama form admin dan formulir pendataan publik. @return array<string, list<string>> */
    public static function alumniRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'activity' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'testimony' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+()\-\s]{6,20}$/'],
        ];
    }
}
