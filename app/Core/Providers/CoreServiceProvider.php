<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Admin\AdminMenuRegistry;
use App\Core\AuditLog\AuditLog;
use App\Core\Authorization\PermissionRegistry;
use App\Core\Authorization\SyncPermissionsCommand;
use App\Core\Console\DemoResetCommand;
use App\Core\Dashboard\WidgetRegistry;
use App\Core\Media\MediaAsset;
use App\Core\Modules\ModuleManager;
use App\Core\Settings\Branding;
use App\Core\Settings\SettingsStore;
use App\Core\Users\User;
use App\Core\Users\UserPolicy;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Core\Licensing\Edition;
use App\Core\Licensing\LicenseManager;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(AdminMenuRegistry::class);
        $this->app->singleton(WidgetRegistry::class);
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(LicenseManager::class, fn ($app) => new LicenseManager(
            $app->make(SettingsStore::class),
            enforced: Edition::requiresLicense(),
            publicKey: Edition::PUBLIC_KEY,
        ));

        $this->registerCorePermissions();
        $this->registerCoreAdminMenu();
        $this->registerCoreWidgets();

        // Modul dimuat setelah Core siap agar mereka bisa mendaftar ke registry di atas.
        $this->app->make(ModuleManager::class)->registerEnabled();
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        // Super admin melewati semua pengecekan permission.
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        // URL logo/favicon di-cache (Branding); berkas media yang berubah atau dihapus (termasuk diganti) membuangnya.
        // Ditunda sampai transaksi selesai agar permintaan lain tidak menyimpan ulang data setengah jadi.
        $flushBranding = fn () => DB::afterCommit(Branding::flush(...));
        Media::saved($flushBranding);
        Media::deleted($flushBranding);
        MediaAsset::deleted($flushBranding);

        // Judul & favicon di <head> mengikuti pengaturan situs.
        View::composer('app', function ($view): void {
            $branding = $this->app->make(Branding::class);

            $view->with(['siteName' => $branding->siteName(), 'faviconUrl' => $branding->faviconUrl()]);
        });

        $this->configureTrustedProxies();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class, DemoResetCommand::class]);
        }
    }

    /** Membaca config('platform.trusted_proxies') untuk middleware TrustProxies bawaan Laravel (lihat config/platform.php). */
    public function configureTrustedProxies(): void
    {
        $proxies = config('platform.trusted_proxies');

        if (is_string($proxies) && trim($proxies) !== '') {
            TrustProxies::at(trim($proxies) === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }

    private function registerCorePermissions(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $registry->register('users', ['users.view', 'users.create', 'users.update', 'users.delete']);
        $registry->register('roles', ['roles.manage']);
        $registry->register('settings', ['settings.manage']);
        $registry->register('media', ['media.manage']);
        $registry->register('audit', ['audit.view']);
    }

    private function registerCoreAdminMenu(): void
    {
        $menu = $this->app->make(AdminMenuRegistry::class);

        $menu->register('dashboard', 'admin.menu.dashboard', 'admin.dashboard', null, 'home', 0);
        $menu->register('users', 'admin.menu.users', 'admin.users.index', 'users.view', 'users', 900, 'system');
        $menu->register('roles', 'admin.menu.roles', 'admin.roles.index', 'roles.manage', 'shield', 910, 'system');
        $menu->register('settings', 'admin.menu.settings', 'admin.settings.edit', 'settings.manage', 'settings', 920, 'system');

        if (Edition::requiresLicense()) {
            $menu->register('license', 'admin.menu.license', 'admin.license.edit', 'settings.manage', 'shield', 930, 'system');
        }
    }

    private function registerCoreWidgets(): void
    {
        $widgets = $this->app->make(WidgetRegistry::class);

        $widgets->register('users', fn () => ['total' => User::count()], 'users.view', 10);

        $widgets->register('recent_activity', fn () => AuditLog::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'subject' => class_basename($log->auditable_type),
                'user' => $log->user?->name,
                'at' => $log->created_at?->toIso8601String(),
            ])->all(), 'audit.view', 20);
    }
}
