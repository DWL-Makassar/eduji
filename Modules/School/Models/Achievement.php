<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;
use Illuminate\Database\Eloquent\Builder;

class Achievement extends SchoolRecord
{
    public const LEVELS = ['school', 'district', 'city', 'province', 'national', 'international'];

    protected $table = 'school_achievements';

    protected $fillable = ['title', 'level', 'winner', 'year', 'description', 'image_media_id', 'order', 'is_active'];

    /** Prestasi terbaru dulu (tahun), lalu urutan manual. @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('year')->orderBy('order')->latest('id');
    }

    public function toCard(): array
    {
        return [
            'title' => $this->title,
            'level' => __("school::messages.levels.{$this->level}"),
            'winner' => $this->winner,
            'year' => $this->year,
            'description' => $this->description,
            'photo' => Presenter::media($this->image),
        ];
    }
}
