<?php

use App\Core\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// Route sisi publik (beranda, berita, dst.) ada di app/Website/routes.php.

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
