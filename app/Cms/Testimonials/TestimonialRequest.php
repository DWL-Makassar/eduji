<?php

declare(strict_types=1);

namespace App\Cms\Testimonials;

use App\Cms\Support\CmsRequest;

class TestimonialRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'year' => ['nullable', 'string', 'max:16'],
            'photo_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
