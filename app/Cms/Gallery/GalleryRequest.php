<?php

declare(strict_types=1);

namespace App\Cms\Gallery;

use App\Cms\Support\CmsRequest;
use Illuminate\Validation\Rule;

class GalleryRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([Gallery::DRAFT, Gallery::PUBLISHED])],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'items.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
