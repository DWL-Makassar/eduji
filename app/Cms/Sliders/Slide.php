<?php

declare(strict_types=1);

namespace App\Cms\Sliders;

use App\Core\Media\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slide extends Model
{
    protected $fillable = ['slider_id', 'image_media_id', 'title', 'subtitle', 'cta_label', 'cta_url', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_id');
    }
}
