<?php

declare(strict_types=1);

namespace App\Website\Seo;

use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(SeoBuilder $seo): Response
    {
        $lines = $seo->indexable()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /login', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
