<?php

declare(strict_types=1);

namespace App\Core\Media\Http\Requests;

use App\Core\Media\MediaAsset;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.manage');
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.MediaAsset::MAX_KILOBYTES,
                'mimes:'.implode(',', MediaAsset::ALLOWED_EXTENSIONS),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
