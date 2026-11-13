<?php

declare(strict_types=1);

namespace App\Cms\Pages;

use App\Cms\Support\CmsRequest;
use Illuminate\Validation\Rule;

class PageRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body' => ['nullable', 'string'],
            'status' => ['required', Rule::in([Page::DRAFT, Page::PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
