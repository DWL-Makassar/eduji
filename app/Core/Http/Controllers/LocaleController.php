<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('platform.locales')))],
        ]);

        $request->session()->put('locale', $data['locale']);

        // Tersimpan sebagai preferensi akun; tidak dicatat audit log (bukan perubahan data bisnis).
        $request->user()?->forceFill(['locale' => $data['locale']])->saveQuietly();

        return back();
    }
}
