<?php

declare(strict_types=1);

namespace App\Website\Http\Middleware;

use App\Cms\Menus\Menu;
use App\Cms\Menus\MenuItem;
use App\Core\Settings\SettingsStore;
use App\Website\Theme\ThemeManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** Data untuk kerangka halaman publik (menu header/footer, kontak, desain beranda): prop bersama `site`, hanya di route publik. */
class ShareSiteData
{
    private const MENUS_CACHE_KEY = 'website.menus';

    public function __construct(private readonly SettingsStore $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->share();

        return $next($request);
    }

    /** Dipanggil juga oleh penanganan error (bootstrap/app.php) agar halaman 404/500 publik tetap punya menu. */
    public function share(): void
    {
        Inertia::share('site', fn () => [
            'menus' => $this->menus(),
            'tagline' => $this->settings->get('general', 'tagline'),
            'design' => app(ThemeManager::class)->active('design'),
            'contact' => [
                'email' => $this->settings->get('general', 'email'),
                'phone' => $this->settings->get('general', 'phone'),
                'address' => $this->settings->get('general', 'address'),
            ],
        ]);
    }

    /** Membuang cache menu; dipanggil saat Menu/MenuItem berubah (WebsiteServiceProvider). */
    public static function flushMenus(): void
    {
        Cache::forget(self::MENUS_CACHE_KEY);
    }

    /**
     * Menu dibaca di SETIAP halaman publik namun jarang berubah, jadi di-cache. Batas 1 jam hanya jaring pengaman bila
     * data diubah di luar aplikasi; perubahan lewat aplikasi membuangnya seketika.
     *
     * @return array<string, list<array{label: string, url: string, target: string, children: list<array{label: string, url: string, target: string}>}>> lokasi => pohon 2 level
     */
    private function menus(): array
    {
        return Cache::remember(self::MENUS_CACHE_KEY, 3600, fn () => $this->buildMenus());
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function buildMenus(): array
    {
        $menus = Menu::query()
            ->whereIn('location', array_keys(config('cms.menu_locations')))
            ->with(['items' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('id')
            ->get()
            ->unique('location');

        return $menus->mapWithKeys(function (Menu $menu): array {
            $items = $menu->items;
            $node = fn (MenuItem $item) => ['label' => $item->label, 'url' => $item->url, 'target' => $item->target];

            return [$menu->location => $items->whereNull('parent_id')->map(fn (MenuItem $item) => $node($item) + [
                'children' => $items->where('parent_id', $item->id)->map($node)->values()->all(),
            ])->values()->all()];
        })->all();
    }
}
