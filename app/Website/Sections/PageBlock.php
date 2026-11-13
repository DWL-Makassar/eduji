<?php

declare(strict_types=1);

namespace App\Website\Sections;

use App\Core\Settings\SettingsStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Satu section pada sebuah halaman (page_id null = beranda), disusun lewat Admin → Susun halaman. */
class PageBlock extends Model
{
    protected $fillable = ['page_id', 'type', 'variant', 'settings', 'position', 'is_visible'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_visible' => 'boolean'];
    }

    /** @param Builder<self> $query */
    public function scopeForPage(Builder $query, ?int $pageId): void
    {
        $query->where('page_id', $pageId)->orderBy('position');
    }

    /**
     * Susunan tersimpan satu halaman; null = belum pernah disusun (beranda lalu memakai section bawaan).
     *
     * @return list<array{key: string, variant: string, settings: array<string, mixed>, is_visible: bool}>|null
     */
    public static function layout(?int $pageId): ?array
    {
        $blocks = self::query()->forPage($pageId)->get();

        // Beranda tanpa blok tapi sudah pernah disimpan (penanda home_built) = sengaja dikosongkan.
        if ($blocks->isEmpty() && $pageId === null && ! app(SettingsStore::class)->get('website', 'home_built')) {
            return null;
        }

        return $blocks->map(fn (self $b) => [...$b->toBlock(), 'is_visible' => $b->is_visible])->all();
    }

    /** @return array{key: string, variant: string, settings: array<string, mixed>} */
    public function toBlock(): array
    {
        return ['key' => $this->type, 'variant' => $this->variant, 'settings' => $this->settings ?? []];
    }
}
