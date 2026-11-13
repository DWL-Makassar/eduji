<?php

declare(strict_types=1);

namespace App\Cms\Gallery;

use App\Core\Media\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['gallery_id', 'media_asset_id', 'caption', 'order'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
