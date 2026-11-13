<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Core\Settings\Branding;
use App\Core\Settings\SettingsStore;
use App\Website\Sections\PageBlock;
use App\Website\Sections\SectionRegistry;
use App\Website\Seo\JsonLd;
use App\Website\Seo\SeoBuilder;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(SectionRegistry $sections, SettingsStore $settings, SeoBuilder $seo, Branding $branding): Response
    {
        $name = $branding->siteName();
        $home = url('/');

        // Belum pernah disusun: susunan bawaan (section otomatis; yang tanpa isi dilewati resolve()).
        $blocks = PageBlock::layout(null) ?? $sections->defaultBlocks();

        return Inertia::render('site/Home', [
            'sections' => $sections->resolve($blocks),
            'seo' => $seo->make(jsonLd: [
                JsonLd::organization(
                    $name,
                    $home,
                    $branding->logoUrl(),
                    $settings->get('general', 'email') ?: null,
                    $settings->get('general', 'phone') ?: null,
                    $settings->get('general', 'address') ?: null,
                ),
                JsonLd::website($name, $home),
            ])->toArray(),
        ]);
    }
}
