<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Media\Http\Resources\MediaAssetResource;
use App\Core\Media\MediaAsset;
use App\Core\Settings\Http\Requests\UpdateSettingsRequest;
use App\Core\Settings\SettingsStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Pengaturan umum situs (grup "general") dan identitas visual (grup "branding"). */
class SettingsController
{
    private const GENERAL = ['site_name', 'tagline', 'email', 'phone', 'address'];

    private const BRANDING = ['logo_media_id', 'favicon_media_id'];

    public function __construct(private readonly SettingsStore $settings) {}

    public function edit(): Response
    {
        Gate::authorize('settings.manage');

        return Inertia::render('admin/settings/Edit', [
            'values' => [
                'site_name' => $this->settings->get('general', 'site_name', config('app.name')),
                'tagline' => $this->settings->get('general', 'tagline', ''),
                'email' => $this->settings->get('general', 'email', ''),
                'phone' => $this->settings->get('general', 'phone', ''),
                'address' => $this->settings->get('general', 'address', ''),
                'logo' => $this->media($this->settings->get('branding', 'logo_media_id')),
                'favicon' => $this->media($this->settings->get('branding', 'favicon_media_id')),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settings->setMany('general', array_intersect_key($data, array_flip(self::GENERAL)));
        $this->settings->setMany('branding', array_intersect_key($data, array_flip(self::BRANDING)));

        return back()->with('success', __('settings.updated'));
    }

    /** @return array<string, mixed>|null */
    private function media(mixed $id): ?array
    {
        $asset = $id ? MediaAsset::with('media')->find($id) : null;

        return $asset ? MediaAssetResource::make($asset)->resolve() : null;
    }
}
