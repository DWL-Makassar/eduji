<?php

declare(strict_types=1);

namespace App\Cms\Testimonials;

use App\Core\AuditLog\Auditable;
use App\Core\Media\MediaAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use Auditable;

    protected $fillable = ['photo_media_id', 'name', 'role', 'body', 'year', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('order');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_media_id');
    }
}
