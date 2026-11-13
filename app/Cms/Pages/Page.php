<?php

declare(strict_types=1);

namespace App\Cms\Pages;

use App\Cms\Support\Publishable;
use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use Auditable, Publishable, SoftDeletes;

    /** Isi artikel bisa besar; audit log cukup mencatat metadata (judul, status, dll). */
    protected array $auditExclude = ['body'];

    protected $fillable = ['title', 'slug', 'body', 'template', 'status', 'published_at', 'seo'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'seo' => 'array'];
    }
}
