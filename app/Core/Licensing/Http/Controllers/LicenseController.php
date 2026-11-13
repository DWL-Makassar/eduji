<?php

declare(strict_types=1);

namespace App\Core\Licensing\Http\Controllers;

use App\Core\Licensing\LicenseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Admin → Lisensi: melihat status dan memasukkan kunci lisensi (edisi gratis). */
class LicenseController
{
    public function __construct(private readonly LicenseManager $license) {}

    public function edit(Request $request): Response
    {
        Gate::authorize('settings.manage');

        $current = $this->license->current();
        $key = $this->license->storedKey();

        return Inertia::render('admin/license/Edit', [
            'enforced' => $this->license->enforced(),
            'status' => $this->license->status($request->getHost(), $request->ip()),
            'host' => $request->getHost(),
            'license' => $current ? [
                'domain' => $current->domain,
                'school' => $current->school,
                'edition' => $current->edition,
                'expires_at' => $current->expiresAt?->toDateString(),
            ] : null,
            // Hanya ujung kunci, cukup untuk mengenali kunci mana yang terpasang.
            'key_hint' => $key ? '…'.substr($key, -12) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $data = $request->validate(['key' => ['required', 'string', 'max:2000']]);
        $status = $this->license->check($data['key'], $request->getHost(), $request->ip());

        if ($status !== LicenseManager::VALID) {
            return back()->withErrors(['key' => __('license.status.'.$status, ['host' => $request->getHost()])]);
        }

        $this->license->store($data['key']);

        return redirect()->route('admin.dashboard')->with('success', __('license.activated'));
    }
}
