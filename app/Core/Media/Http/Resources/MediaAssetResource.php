<?php

declare(strict_types=1);

namespace App\Core\Media\Http\Resources;

use App\Core\Media\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MediaAsset */
class MediaAssetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $file = $this->file();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'alt' => $file?->getCustomProperty('alt'),
            'url' => $file?->getUrl(),
            'thumb_url' => $file?->hasGeneratedConversion('thumb') ? $file->getUrl('thumb') : null,
            'mime' => $file?->mime_type,
            'size' => $file?->size,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
