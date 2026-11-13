<?php

use App\Core\Authorization\Http\Controllers\RoleController;
use App\Core\Dashboard\Http\Controllers\DashboardController;
use App\Core\Licensing\Http\Controllers\LicenseController;
use App\Core\Media\Http\Controllers\MediaController;
use App\Core\Settings\Http\Controllers\SettingsController;
use App\Core\Users\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Dimuat dengan prefix "admin", nama "admin.", middleware web + auth (lihat bootstrap/app.php).
Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('users', UserController::class)->except('show');
Route::resource('roles', RoleController::class)->except('show');

Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

Route::get('media', [MediaController::class, 'index'])->name('media.index');
Route::post('media', [MediaController::class, 'store'])->middleware('throttle:60,1,media')->name('media.store');
Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

Route::get('license', [LicenseController::class, 'edit'])->name('license.edit');
Route::put('license', [LicenseController::class, 'update'])->middleware('throttle:10,1,license')->name('license.update');
