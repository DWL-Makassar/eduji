<?php

use App\Cms\Categories\CategoryController;
use App\Cms\Contact\ContactController;
use App\Cms\Contact\ContactMessageController;
use App\Cms\Downloads\DownloadController;
use App\Cms\Events\EventController;
use App\Cms\Features\FeatureController;
use App\Cms\Gallery\GalleryController;
use App\Cms\Menus\MenuController;
use App\Cms\Pages\PageController;
use App\Cms\Posts\AnnouncementController;
use App\Cms\Posts\PostController;
use App\Cms\Sliders\SliderController;
use App\Cms\Tags\TagController;
use App\Cms\Testimonials\TestimonialController;
use Illuminate\Support\Facades\Route;

// Endpoint publik: kirim pesan kontak (tampilan formulir ada di layer Website).
Route::post('/contact', [ContactController::class, 'store'])->middleware(['web', 'throttle:5,1,contact'])->name('contact.store');

// Admin: CRUD seragam (nama route: admin.<resource>.*).
Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    foreach ([
        'posts' => PostController::class,
        'announcements' => AnnouncementController::class,
        'pages' => PageController::class,
        'categories' => CategoryController::class,
        'tags' => TagController::class,
        'menus' => MenuController::class,
        'sliders' => SliderController::class,
        'galleries' => GalleryController::class,
        'events' => EventController::class,
        'downloads' => DownloadController::class,
        'testimonials' => TestimonialController::class,
        'features' => FeatureController::class,
    ] as $name => $controller) {
        Route::resource($name, $controller)->except('show');
    }

    Route::resource('contact', ContactMessageController::class)->only(['index', 'show', 'destroy']);
});
