<?php

declare(strict_types=1);

namespace App\Cms\Features;

use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Program unggulan / layanan / keunggulan — nama netral agar dipakai lintas domain. */
class Feature extends Model
{
    use Auditable;

    protected $fillable = ['title', 'description', 'icon', 'url', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('order');
    }
}
