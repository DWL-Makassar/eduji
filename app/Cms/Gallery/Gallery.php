<?php

declare(strict_types=1);

namespace App\Cms\Gallery;

use App\Cms\Support\Publishable;
use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use Auditable, Publishable, SoftDeletes;

    protected $fillable = ['title', 'slug', 'description', 'status'];

    /** Galeri tidak punya jadwal terbit (tabel tanpa `published_at`), jadi cukup status — menggantikan scope dari trait. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GalleryItem::class)->orderBy('order');
    }
}
