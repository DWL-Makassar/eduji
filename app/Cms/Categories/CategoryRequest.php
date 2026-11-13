<?php

declare(strict_types=1);

namespace App\Cms\Categories;

use App\Cms\Support\CmsRequest;

class CategoryRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
