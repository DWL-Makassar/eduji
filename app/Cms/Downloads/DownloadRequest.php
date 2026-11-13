<?php

declare(strict_types=1);

namespace App\Cms\Downloads;

use App\Cms\Support\CmsRequest;

class DownloadRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'is_active' => ['boolean'],
        ];
    }
}
