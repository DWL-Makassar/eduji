<?php

declare(strict_types=1);

namespace App\Core\Support;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Bentuk respons paginasi yang sama dengan API Resource (data/meta/links) agar frontend seragam. */
final class Paginate
{
    /** @return array{data: list<mixed>, meta: array<string, int|null>, links: array<string, string|null>} */
    public static function shape(LengthAwarePaginator $paginator, Closure $map): array
    {
        return [
            'data' => collect($paginator->items())->map($map)->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
