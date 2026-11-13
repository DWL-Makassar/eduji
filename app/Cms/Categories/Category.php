<?php

declare(strict_types=1);

namespace App\Cms\Categories;

use App\Cms\Posts\Post;
use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Auditable;

    public const TYPE_POST = 'post';

    protected $fillable = ['type', 'name', 'slug', 'order'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
