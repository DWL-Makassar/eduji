<?php

declare(strict_types=1);

namespace App\Website\Providers;

use App\Cms\Menus\Menu;
use App\Cms\Menus\MenuItem;
use App\Core\Admin\AdminMenuRegistry;
use App\Core\Authorization\PermissionRegistry;
use App\Website\Http\Middleware\ShareSiteData;
use App\Website\Sections\ContentSections;
use App\Website\Sections\DefaultSections;
use App\Website\Sections\SectionRegistry;
use App\Website\Seo\DefaultSitemap;
use App\Website\Seo\SitemapRegistry;
use App\Website\Theme\ThemeManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WebsiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SectionRegistry::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(ThemeManager::class);

        $this->app->make(PermissionRegistry::class)->register('website', ['website.manage']);
        // order 225: di antara galeri (220) dan agenda (230) agar tetap satu grup "Tampilan".
        $this->app->make(AdminMenuRegistry::class)->register('website', 'website.menu', 'admin.website.edit', 'website.manage', 'palette', 225, 'display');
        $this->app->make(AdminMenuRegistry::class)->register('builder', 'website.builder.menu', 'admin.website.builder', 'website.manage', 'layout', 226, 'display');
    }

    public function boot(): void
    {
        DefaultSections::register($this->app->make(SectionRegistry::class));
        ContentSections::register($this->app->make(SectionRegistry::class));
        DefaultSitemap::register($this->app->make(SitemapRegistry::class));

        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Menu situs di-cache (ShareSiteData). MenuController mengganti seluruh item dengan penghapusan massal (tanpa event
        // model), jadi pembuangan cache ditunda sampai transaksi selesai dan dipicu juga oleh penyimpanan Menu itu sendiri.
        foreach ([Menu::class, MenuItem::class] as $model) {
            $model::saved(fn () => DB::afterCommit(ShareSiteData::flushMenus(...)));
            $model::deleted(fn () => DB::afterCommit(ShareSiteData::flushMenus(...)));
        }

        View::composer('app', function ($view): void {
            // Warna tema berlaku di seluruh aplikasi (publik dan admin) lewat CSS variable di <head>.
            $view->with('themeCss', $this->app->make(ThemeManager::class)->css());
            $view->with('fontUrl', $this->app->make(ThemeManager::class)->fontUrl());

            // Halaman publik (punya prop `seo`) memakai group Ziggy "public": route non-admin saja. ±100 nama route admin tidak
            // perlu dikirim ke pengunjung dan menyumbang ±25% ukuran HTML. Halaman admin/auth memakai daftar penuh (group null).
            // Memakai group (bukan `ziggy.only`) karena config global yang diubah per permintaan bocor ke permintaan berikutnya
            // di server berumur panjang (Octane); isi group ini selalu sama sehingga aman.
            $isPublic = isset(($view->getData()['page'] ?? [])['props']['seo']);

            if ($isPublic) {
                config(['ziggy.groups.public' => $this->publicRouteNames()]);
            }

            $view->with('ziggyGroup', $isPublic ? 'public' : null);
        });
    }

    /**
     * Semua route bernama kecuali admin.*; `admin.dashboard` tetap ada karena footer situs menautkan ke sana.
     *
     * @return list<string>
     */
    private function publicRouteNames(): array
    {
        return collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->reject(fn (string $name) => str_starts_with($name, 'admin.') && $name !== 'admin.dashboard')
            ->values()
            ->all();
    }
}
