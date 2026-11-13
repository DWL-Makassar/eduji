<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Website\Seo\SeoBuilder;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman formulir kontak. Pengirimannya ditangani App\Cms\Contact\ContactController (route `contact.store`). */
class ContactController
{
    public function __invoke(SeoBuilder $seo): Response
    {
        return Inertia::render('site/Contact', ['seo' => $seo->make(__('website.contact'))->toArray()]);
    }
}
