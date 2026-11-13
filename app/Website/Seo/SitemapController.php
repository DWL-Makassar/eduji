<?php

declare(strict_types=1);

namespace App\Website\Seo;

use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(SeoBuilder $seo, SitemapRegistry $registry): Response
    {
        abort_unless($seo->indexable(), 404);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($registry->urls() as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod']->toIso8601String().'</lastmod>';
            }

            $xml .= "</url>\n";
        }

        return response($xml.'</urlset>', 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
