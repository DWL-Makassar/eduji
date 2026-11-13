<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Cms\Downloads\Download;
use App\Core\Support\Paginate;
use App\Website\Http\Presenter;
use App\Website\Seo\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DownloadController
{
    public function index(SeoBuilder $seo): Response
    {
        $downloads = Download::active()->with('file.media')->latest('id')->paginate(config('website.per_page'));

        return Inertia::render('site/downloads/Index', [
            'downloads' => Paginate::shape($downloads, Presenter::download(...)),
            'seo' => $seo->make(__('website.downloads'))->toArray(),
        ]);
    }

    /** Hitung unduhan lalu arahkan ke berkas (tidak ada halaman perantara). */
    public function file(Download $download): RedirectResponse
    {
        abort_unless($download->is_active, 404);

        $url = $download->file?->file()?->getUrl();
        abort_if($url === null, 404);

        $download->increment('download_count');

        return redirect()->away($url);
    }
}
