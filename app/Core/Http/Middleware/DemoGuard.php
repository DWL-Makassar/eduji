<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode demo: menolak aksi yang bisa mengunci pengunjung lain atau menyalahgunakan server (kelola pengguna/role,
 * pengaturan situs, reset password). Konten, tampilan, dan data sekolah tetap bisa dicoba; semuanya direset tiap malam.
 */
final class DemoGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('platform.demo.enabled') || $request->isMethodSafe() || ! $request->routeIs(...config('platform.demo.blocked'))) {
            return $next($request);
        }

        $message = __('admin.demo_blocked');

        return $request->expectsJson() && ! $request->header('X-Inertia')
            ? response()->json(['message' => $message], 403)
            : back()->with('error', $message);
    }
}
