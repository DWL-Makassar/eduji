<?php

declare(strict_types=1);

namespace App\Cms\Posts;

use App\Cms\Categories\Category;
use App\Cms\Support\CmsRequest;
use App\Cms\Tags\Tag;
use Illuminate\Validation\Rule;

/** Dipakai oleh berita (post) dan pengumuman (announcement); field yang tidak relevan diabaikan controller. */
class PostRequest extends CmsRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')->where('type', Category::TYPE_POST)],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists(Tag::class, 'id')],
            'status' => ['required', Rule::in([Post::DRAFT, Post::PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_pinned' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
