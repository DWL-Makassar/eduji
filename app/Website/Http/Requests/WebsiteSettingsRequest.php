<?php

declare(strict_types=1);

namespace App\Website\Http\Requests;

use App\Core\Media\Rules\ImageAsset;
use App\Website\Theme\ThemeManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('website.manage');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(app(ThemeManager::class)->keys())],
            'font' => ['required', Rule::in(app(ThemeManager::class)->keys('font'))],
            'radius' => ['required', Rule::in(app(ThemeManager::class)->keys('radius'))],
            // Opsional agar klien lama (tanpa pilihan desain) tetap bisa menyimpan; kosong = desain aktif tidak berubah.
            'design' => ['sometimes', 'required', Rule::in(app(ThemeManager::class)->keys('design'))],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'og_image_media_id' => ['nullable', 'integer', new ImageAsset],
            'allow_indexing' => ['required', 'boolean'],
        ];
    }
}
