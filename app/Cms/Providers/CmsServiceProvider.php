<?php

declare(strict_types=1);

namespace App\Cms\Providers;

use App\Cms\Contact\ContactMessage;
use App\Cms\Events\Event;
use App\Cms\Posts\Post;
use App\Core\Admin\AdminMenuRegistry;
use App\Core\Authorization\PermissionRegistry;
use App\Core\Dashboard\WidgetRegistry;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Resource CRUD: kunci => [ikon, urutan menu, grup menu].
     * Kunci = prefix permission & nama route. Satu grup memakai rentang urutan yang berdekatan.
     */
    private const RESOURCES = [
        'posts' => ['newspaper', 100, 'content'],
        'announcements' => ['megaphone', 110, 'content'],
        'pages' => ['file', 120, 'content'],
        'categories' => ['folder', 130, 'content'],
        'tags' => ['tag', 140, 'content'],
        'menus' => ['list', 200, 'display'],
        'sliders' => ['layers', 210, 'display'],
        'galleries' => ['image', 220, 'display'],
        'events' => ['calendar', 230, 'information'],
        'downloads' => ['download', 240, 'information'],
        'testimonials' => ['quote', 250, 'information'],
        'features' => ['star', 260, 'information'],
    ];

    public function register(): void
    {
        $permissions = $this->app->make(PermissionRegistry::class);
        $menu = $this->app->make(AdminMenuRegistry::class);

        foreach (self::RESOURCES as $key => [$icon, $order, $group]) {
            $permissions->register('cms', ["{$key}.view", "{$key}.create", "{$key}.update", "{$key}.delete"]);
            $menu->register($key, "cms.resources.{$key}", "admin.{$key}.index", "{$key}.view", $icon, $order, $group);
        }

        $permissions->register('cms', ['contact.view', 'contact.delete']);
        $menu->register('contact', 'cms.resources.contact', 'admin.contact.index', 'contact.view', 'mail', 300, 'information');

        $this->registerWidgets();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }

    /** Widget dasbor CMS; resolver hanya dijalankan untuk pengguna yang punya permission `*.view`-nya. */
    private function registerWidgets(): void
    {
        $widgets = $this->app->make(WidgetRegistry::class);

        $widgets->register('posts', fn () => [
            'published' => Post::ofType(Post::TYPE_POST)->published()->count(),
            'draft' => Post::ofType(Post::TYPE_POST)->where('status', Post::DRAFT)->count(),
            'latest' => Post::ofType(Post::TYPE_POST)->latest('id')->limit(5)->get(['id', 'title', 'status'])
                ->map(fn (Post $post) => ['id' => $post->id, 'title' => $post->title, 'status' => $post->status])
                ->all(),
        ], 'posts.view', 30);

        $widgets->register('contact', fn () => [
            'unread' => ContactMessage::where('is_read', false)->count(),
            'latest' => ContactMessage::latest('id')->limit(5)->get(['id', 'name', 'subject', 'is_read', 'created_at'])
                ->map(fn (ContactMessage $message) => [
                    'id' => $message->id,
                    'name' => $message->name,
                    'subject' => $message->subject,
                    'is_read' => $message->is_read,
                    'at' => $message->created_at?->toIso8601String(),
                ])->all(),
        ], 'contact.view', 40);

        $widgets->register('events', fn () => Event::active()->upcoming()->limit(5)->get(['id', 'title', 'starts_at'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at?->toIso8601String(),
            ])->all(), 'events.view', 50);
    }
}
