<?php

declare(strict_types=1);

namespace App\Cms\Tags;

use App\Cms\Support\CmsRequest;

class TagRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ];
    }
}
