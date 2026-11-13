<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Core\AuditLog\Auditable;
use App\Core\Media\MediaAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Basis data sekolah: gambar, urutan, status aktif. Turunan cukup menyebut tabel, kolom isian, dan bentuk kartu publiknya.
 */
abstract class SchoolRecord extends Model
{
    use Auditable;

    /**
     * Data untuk halaman publik dan section (hanya kolom yang tampil). Relasi `image.media` harus sudah di-eager-load.
     *
     * @return array<string, mixed>
     */
    abstract public function toCard(): array;

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Urutan tampil publik: kolom `order`, lalu yang terbaru. @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order')->latest('id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'order' => 'integer'];
    }
}
