<?php

declare(strict_types=1);

namespace App\Cms\Posts;

use App\Cms\Support\Column;
use App\Cms\Support\Field;

/** Pengumuman: post bertipe "announcement" dengan masa berlaku (expires_at), tanpa kategori/tag. */
class AnnouncementController extends PostController
{
    protected function key(): string
    {
        return 'announcements';
    }

    protected function type(): string
    {
        return Post::TYPE_ANNOUNCEMENT;
    }

    protected function columns(): array
    {
        return [Column::text('title'), Column::badge('status'), Column::datetime('published_at'), Column::datetime('expires_at')];
    }

    protected function fields(): array
    {
        return [
            Field::text('title', required: true),
            Field::text('slug', help: 'cms.slug_help'),
            Field::richtext('body'),
            Field::select('status', ['draft' => 'cms.status.draft', 'published' => 'cms.status.published'], required: true),
            Field::datetime('published_at'),
            Field::datetime('expires_at'),
            Field::checkbox('is_pinned'),
        ];
    }
}
