<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers;

use App\Cms\Events\Event;
use App\Core\Support\Paginate;
use App\Website\Http\Presenter;
use App\Website\Seo\JsonLd;
use App\Website\Seo\SeoBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController
{
    public function __construct(private readonly SeoBuilder $seo) {}

    /** Bawaan: agenda mendatang (terdekat dulu). `?past=1`: agenda yang sudah lewat (terbaru dulu). */
    public function index(Request $request): Response
    {
        $past = $request->boolean('past');

        $events = Event::active()
            ->when($past, fn ($q) => $q->where('starts_at', '<', now())->orderByDesc('starts_at'), fn ($q) => $q->upcoming())
            ->paginate(config('website.per_page'))
            ->withQueryString();

        return Inertia::render('site/events/Index', [
            'events' => Paginate::shape($events, Presenter::eventCard(...)),
            'past' => $past,
            'seo' => $this->seo->make(__('website.events'))->toArray(),
        ]);
    }

    public function show(string $slug): Response
    {
        $event = Event::active()->where('slug', $slug)->firstOrFail();

        $description = SeoBuilder::plain($event->description);

        return Inertia::render('site/events/Show', [
            'event' => Presenter::eventCard($event) + ['description' => $event->description],
            'seo' => $this->seo->make($event->title, $description, type: 'article', jsonLd: [
                JsonLd::event($event->title, $description, url(route('site.events.show', $event->slug, absolute: false)), $event->starts_at, $event->ends_at, $event->location),
            ])->toArray(),
        ]);
    }
}
