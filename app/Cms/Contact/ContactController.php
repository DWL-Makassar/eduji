<?php

declare(strict_types=1);

namespace App\Cms\Contact;

use Illuminate\Http\RedirectResponse;

/** Endpoint publik form kontak. Tampilan formulir dirender oleh layer Website. */
class ContactController
{
    public function store(ContactRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'subject', 'message']);

        ContactMessage::create($data + [
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
        ]);

        return back()->with('success', __('cms.contact.sent'));
    }
}
