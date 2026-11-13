<?php

use App\Core\Http\Middleware\DemoGuard;
use App\Core\Http\Middleware\HandleInertiaRequests;
use App\Core\Http\Middleware\RequireLicense;
use App\Core\Http\Middleware\SecurityHeaders;
use App\Core\Http\Middleware\SetLocale;
use App\Website\Http\Middleware\ShareSiteData;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/auth.php'));
            Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global (bukan hanya grup web) agar sitemap, robots, dan respons error pun ikut mendapat header keamanan.
        $middleware->append(SecurityHeaders::class);

        // Mengganti password (reset atau lewat admin) mengeluarkan semua sesi lain milik akun itu; sesi yang dicuri tidak bertahan.
        $middleware->authenticateSessions();

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            DemoGuard::class, // tidak melakukan apa pun kecuali DEMO_MODE=true
            RequireLicense::class, // tidak melakukan apa pun kecuali edisi gratis (App\Core\Licensing\Edition)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Halaman error ber-layout (pages/Error.vue) untuk permintaan browser. Permintaan JSON/API memakai respons bawaan,
        // dan 500/503 hanya dialihkan saat debug mati (saat debug, halaman debug Laravel lebih berguna).
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return $response;
            }

            // Sesi/token kedaluwarsa: kembali ke halaman sebelumnya dengan pesan, bukan halaman "Page Expired" polos.
            if ($status === 419) {
                return back()->with('error', __('errors.session_expired'));
            }

            $handled = in_array($status, [403, 404, 429], true) || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if (! $handled) {
                return $response;
            }

            // Menu situs untuk halaman publik. Saat error berat (mis. database mati) halaman tetap tampil tanpa menu.
            try {
                if (! $request->is('admin', 'admin/*')) {
                    app(ShareSiteData::class)->share();
                }
            } catch (Throwable) {
                //
            }

            return Inertia::render('Error', ['status' => $status])->toResponse($request)->setStatusCode($status);
        });
    })->create();
