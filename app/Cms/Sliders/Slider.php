<?php

declare(strict_types=1);

namespace App\Cms\Sliders;

use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slider extends Model
{
    use Auditable;

    protected $fillable = ['title', 'key'];

    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('order');
    }
}
