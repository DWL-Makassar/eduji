<?php

declare(strict_types=1);

namespace App\Website\Seo;

use Carbon\CarbonInterface;
use Closure;

/**
 * Sumber URL sitemap.xml. Module mendaftar sendiri; penyedia mengembalikan iterable berisi ['loc' => url absolut, 'lastmod' => ?Carbon].
 * Module mendaftar di boot(), bukan register() (alasannya: lihat SectionRegistry).
 */
final class SitemapRegistry
{
    /** @var list<Closure(): iterable<array{loc: string, lastmod?: ?CarbonInterface}>> */
    private array $providers = [];

    /** @param Closure(): iterable<array{loc: string, lastmod?: ?CarbonInterface}> $provider */
    public function register(Closure $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return \Generator<array{loc: string, lastmod?: ?CarbonInterface}> */
    public function urls(): \Generator
    {
        foreach ($this->providers as $provider) {
            yield from $provider();
        }
    }
}
