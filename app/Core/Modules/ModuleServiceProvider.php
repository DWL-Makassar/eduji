<?php

declare(strict_types=1);

namespace App\Core\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Basis provider modul. Modul cukup mengembalikan namanya; folder standar dimuat otomatis:
 *   Database/migrations, routes/web.php (publik), routes/admin.php (prefix admin, wajib login),
 *   resources/lang (namespace = nama modul huruf kecil).
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function moduleName(): string;

    public function boot(): void
    {
        $base = base_path("Modules/{$this->moduleName()}");

        $this->loadMigrationsFrom("{$base}/Database/migrations");
        $this->loadTranslationsFrom("{$base}/resources/lang", strtolower($this->moduleName()));

        if (is_file("{$base}/routes/web.php")) {
            Route::middleware('web')->group("{$base}/routes/web.php");
        }

        if (is_file("{$base}/routes/admin.php")) {
            Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group("{$base}/routes/admin.php");
        }
    }
}
