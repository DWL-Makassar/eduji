<?php

declare(strict_types=1);

namespace App\Core\Dashboard;

use App\Core\Users\User;
use Closure;

/**
 * Widget dashboard admin. Core dan Module mendaftar; resolver hanya dijalankan
 * bila pengguna punya permission-nya.
 */
final class WidgetRegistry
{
    /** @var array<string, array{resolver: Closure, permission: ?string, order: int}> */
    private array $widgets = [];

    public function register(string $key, Closure $resolver, ?string $permission = null, int $order = 100): void
    {
        $this->widgets[$key] = compact('resolver', 'permission', 'order');
    }

    /** @return array<string, mixed> */
    public function resolveFor(User $user): array
    {
        $widgets = collect($this->widgets)
            ->filter(fn (array $w) => $w['permission'] === null || $user->can($w['permission']))
            ->sortBy('order');

        return $widgets->map(fn (array $w) => ($w['resolver'])())->all();
    }
}
