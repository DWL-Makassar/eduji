<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Admin\AdminMenuRegistry;
use App\Core\Settings\Branding;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly Branding $branding,
        private readonly AdminMenuRegistry $menu,
    ) {}

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'name' => $this->branding->siteName(),
                'logo_url' => fn () => $this->branding->logoUrl(),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'locale' => $user->locale,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                ] : null,
            ],
            'admin_menu' => fn () => $user ? $this->menu->for($user) : [],
            'demo' => (bool) config('platform.demo.enabled'),
            'locale' => app()->getLocale(),
            'locales' => config('platform.locales'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
