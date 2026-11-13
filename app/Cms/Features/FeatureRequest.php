<?php

declare(strict_types=1);

namespace App\Cms\Features;

use App\Cms\Menus\MenuRequest;
use App\Cms\Support\CmsRequest;

class FeatureRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:'.MenuRequest::URL_PATTERN],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
