<?php

declare(strict_types=1);

namespace App\Core\Media;

use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Satu entri di pustaka media. File & konversinya dikelola spatie/laravel-medialibrary;
 * konten (post, halaman, slide) mereferensikan MediaAsset lewat id.
 */
class MediaAsset extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia;

    public const COLLECTION = 'library';

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'docx', 'xlsx'];

    public const MAX_KILOBYTES = 5120;

    protected $fillable = ['title', 'uploaded_by'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)->singleFile();
    }

    /** Konversi sinkron (tanpa queue worker) agar cocok di shared hosting. */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->format('webp')
            ->performOnCollections(self::COLLECTION)
            ->nonQueued();
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::COLLECTION);
    }
}
