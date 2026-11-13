<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers\Admin;

use App\Core\Media\Http\Resources\MediaAssetResource;
use App\Core\Media\MediaAsset;
use App\Core\Settings\SettingsStore;
use App\Website\Http\Requests\WebsiteSettingsRequest;
use App\Website\Theme\ThemeManager;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** Admin → Tampilan situs: tema (warna, font, sudut), desain beranda, dan SEO bawaan. Susunan section ada di PageBuilderController. */
class WebsiteSettingsController
{
    public function __construct(
        private readonly SettingsStore $settings,
        private readonly ThemeManager $themes,
    ) {}

    public function edit(): Response
    {
        Gate::authorize('website.manage');

        $ogImage = $this->settings->get('seo', 'og_image_media_id');
        $og = $ogImage ? MediaAsset::with('media')->find($ogImage) : null;

        return Inertia::render('admin/website/Edit', [
            'themes' => $this->themes->options(),
            'fonts' => $this->themes->choices('font'),
            'radii' => $this->themes->choices('radius'),
            'designs' => $this->themes->choices('design'),
            'values' => [
                'theme' => $this->themes->active(),
                'font' => $this->themes->active('font'),
                'radius' => $this->themes->active('radius'),
                'design' => $this->themes->active('design'),
                'seo_description' => $this->settings->get('seo', 'description', ''),
                'og_image' => $og ? MediaAssetResource::make($og)->resolve() : null,
                'allow_indexing' => (bool) $this->settings->get('seo', 'allow_indexing', true),
            ],
        ]);
    }

    public function update(WebsiteSettingsRequest $request): SymfonyResponse
    {
        $data = $request->validated();
        $look = ['theme' => $data['theme'], 'font' => $data['font'], 'radius' => $data['radius'], 'design' => $data['design'] ?? $this->themes->active('design')];
        $lookChanged = $look !== ['theme' => $this->themes->active(), 'font' => $this->themes->active('font'), 'radius' => $this->themes->active('radius'), 'design' => $this->themes->active('design')];

        $this->settings->setMany('website', $look);
        $this->settings->setMany('seo', [
            'description' => $data['seo_description'] ?? null,
            'og_image_media_id' => $data['og_image_media_id'] ?? null,
            'allow_indexing' => (bool) $data['allow_indexing'],
        ]);

        // CSS tema ada di <head> yang tidak dirender ulang oleh kunjungan Inertia: muat ulang penuh agar langsung terlihat.
        if ($lookChanged) {
            session()->flash('success', __('website.updated'));

            return Inertia::location(route('admin.website.edit'));
        }

        return back()->with('success', __('website.updated'));
    }
}
