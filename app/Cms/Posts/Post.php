<?php

declare(strict_types=1);

namespace App\Cms\Posts;

use App\Cms\Categories\Category;
use App\Cms\Support\Publishable;
use App\Cms\Tags\Tag;
use App\Core\AuditLog\Auditable;
use App\Core\Media\MediaAsset;
use App\Core\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Berita/artikel (type=post) dan pengumuman (type=announcement) berbagi satu tabel. */
class Post extends Model
{
    use Auditable, Publishable, SoftDeletes;

    public const TYPE_POST = 'post';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    /** Isi artikel bisa besar; audit log cukup mencatat metadata (judul, status, dll). */
    protected array $auditExclude = ['body'];

    protected $fillable = [
        'type', 'category_id', 'author_id', 'cover_media_id', 'title', 'slug', 'excerpt', 'body',
        'status', 'published_at', 'expires_at', 'is_pinned', 'seo',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
            'seo' => 'array',
        ];
    }

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** Belum kedaluwarsa: tanpa expires_at (tidak pernah kedaluwarsa) atau masih di masa depan. */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
