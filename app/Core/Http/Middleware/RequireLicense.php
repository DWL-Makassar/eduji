<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Licensing\LicenseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edisi gratis tanpa lisensi yang valid: panel admin dialihkan ke halaman aktivasi lisensi. Situs publik TIDAK dikunci,
 * agar situs sekolah tidak pernah mati hanya karena urusan lisensi. Tidak melakukan apa pun pada edisi lengkap.
 */
final class RequireLicense
{
    /** Tetap bisa dibuka tanpa lisensi: halaman aktivasi itu sendiri dan logout. */
    private const ALLOWED = ['admin.license.*', 'logout'];

    public function __construct(private readonly LicenseManager $license) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->license->enforced()
            || ! $request->is('admin', 'admin/*')
            || $request->routeIs(...self::ALLOWED)
            || $this->license->status($request->getHost(), $request->ip()) === LicenseManager::VALID) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => __('license.required')], 403);
        }

        return redirect()->route('admin.license.edit');
    }
}
