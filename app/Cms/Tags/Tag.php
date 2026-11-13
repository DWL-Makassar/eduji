<?php

declare(strict_types=1);

namespace App\Cms\Tags;

use App\Cms\Posts\Post;
use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use Auditable;

    protected $fillable = ['name', 'slug'];

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
