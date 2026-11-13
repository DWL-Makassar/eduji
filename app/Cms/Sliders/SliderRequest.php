<?php

declare(strict_types=1);

namespace App\Cms\Sliders;

use App\Cms\Menus\MenuRequest;
use App\Cms\Support\CmsRequest;
use Illuminate\Validation\Rule;

class SliderRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique(Slider::class, 'key')->ignore($this->recordId())],
            'slides' => ['nullable', 'array', 'max:30'],
            'slides.*.image_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'slides.*.title' => ['nullable', 'string', 'max:255'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:255'],
            'slides.*.cta_label' => ['nullable', 'string', 'max:100'],
            'slides.*.cta_url' => ['nullable', 'string', 'max:2048', 'regex:'.MenuRequest::URL_PATTERN],
            'slides.*.is_active' => ['boolean'],
        ];
    }
}
