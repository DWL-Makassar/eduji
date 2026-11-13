<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Requests;

use App\Core\Media\Rules\ImageAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            // Logo/favicon dipakai sebagai <img>, jadi harus gambar dari pustaka media.
            'logo_media_id' => ['nullable', 'integer', new ImageAsset],
            'favicon_media_id' => ['nullable', 'integer', new ImageAsset],
        ];
    }
}
