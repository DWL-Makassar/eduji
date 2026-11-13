<?php

use App\Website\Http\Controllers\Admin\PageBuilderController;
use App\Website\Http\Controllers\Admin\WebsiteSettingsController;
use App\Website\Http\Controllers\ContactController;
use App\Website\Http\Controllers\DownloadController;
use App\Website\Http\Controllers\EventController;
use App\Website\Http\Controllers\GalleryController;
use App\Website\Http\Controllers\HomeController;
use App\Website\Http\Controllers\PageController;
use App\Website\Http\Controllers\PostController;
use App\Website\Http\Middleware\ShareSiteData;
use App\Website\Seo\RobotsController;
use App\Website\Seo\SitemapController;
use Illuminate\Support\Facades\Route;

// Tanpa middleware `web` (tanpa sesi/cookie) agar aman di-cache CDN/proxy.
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');

// Sisi publik. Alamat memakai bahasa Indonesia (target utama produk ini).
Route::middleware(['web', ShareSiteData::class])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('berita', [PostController::class, 'index'])->defaults('type', 'post')->name('site.posts.index');
    Route::get('berita/{slug}', [PostController::class, 'show'])->defaults('type', 'post')->name('site.posts.show');
    Route::get('pengumuman', [PostController::class, 'index'])->defaults('type', 'announcement')->name('site.announcements.index');
    Route::get('pengumuman/{slug}', [PostController::class, 'show'])->defaults('type', 'announcement')->name('site.announcements.show');

    Route::get('halaman/{slug}', [PageController::class, 'show'])->name('site.pages.show');

    Route::get('agenda', [EventController::class, 'index'])->name('site.events.index');
    Route::get('agenda/{slug}', [EventController::class, 'show'])->name('site.events.show');

    Route::get('galeri', [GalleryController::class, 'index'])->name('site.galleries.index');
    Route::get('galeri/{slug}', [GalleryController::class, 'show'])->name('site.galleries.show');

    Route::get('unduhan', [DownloadController::class, 'index'])->name('site.downloads.index');
    Route::get('unduhan/{download}', [DownloadController::class, 'file'])->whereNumber('download')->middleware('throttle:60,1,download')->name('site.downloads.file');

    Route::get('kontak', ContactController::class)->name('site.contact');

    // URL yang tidak cocok dengan route mana pun: dijalankan di grup `web` agar sesi, bahasa, dan data bersama Inertia ada
    // (tanpa ini, 404 murni terjadi sebelum middleware `web` sehingga halaman error tidak punya data apa pun).
    Route::fallback(fn () => abort(404));
});

// Admin → Tampilan situs & Susun halaman (tanpa {page} = beranda).
Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('website', [WebsiteSettingsController::class, 'edit'])->name('website.edit');
    Route::put('website', [WebsiteSettingsController::class, 'update'])->name('website.update');

    Route::get('builder/{page?}', [PageBuilderController::class, 'edit'])->whereNumber('page')->name('website.builder');
    Route::put('builder/{page?}', [PageBuilderController::class, 'update'])->whereNumber('page')->name('website.builder.update');
    Route::post('builder/template', [PageBuilderController::class, 'applyTemplate'])->name('website.builder.template');
    Route::post('builder-preview', [PageBuilderController::class, 'preview'])->middleware('throttle:120,1,builder-preview')->name('website.builder.preview');
});
