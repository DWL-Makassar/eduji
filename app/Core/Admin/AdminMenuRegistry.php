<?php

declare(strict_types=1);

namespace App\Core\Admin;

use App\Core\Users\User;

/**
 * Menu sidebar admin. Label berupa kunci terjemahan (mis. "admin.menu.users").
 *
 * `group` (mis. "content", "system") hanya mengelompokkan tampilan; labelnya dari "admin.groups.<group>"
 * (modul memakai kunci namespaced sebagai grup, mis. "example::messages.group", yang sekaligus menjadi labelnya).
 * Urutan tetap ditentukan `order`, jadi item satu grup harus memakai rentang `order` yang berdekatan.
 * Item tanpa grup (mis. dasbor) tampil tanpa judul.
 */
final class AdminMenuRegistry
{
    /** @var array<string, array{label: string, route: string, permission: ?string, icon: string, order: int, group: ?string}> */
    private array $items = [];

    public function register(string $key, string $label, string $route, ?string $permission = null, string $icon = 'circle', int $order = 100, ?string $group = null): void
    {
        $this->items[$key] = compact('label', 'route', 'permission', 'icon', 'order', 'group');
    }

    /** @return list<array{key: string, label: string, href: string, icon: string, group: ?string, group_label: ?string}> */
    public function for(User $user): array
    {
        return collect($this->items)
            ->filter(fn (array $i) => $i['permission'] === null || $user->can($i['permission']))
            ->sortBy('order')
            ->map(fn (array $i, string $key) => [
                'key' => $key,
                'label' => __($i['label']),
                'href' => route($i['route'], absolute: false),
                'icon' => $i['icon'],
                'group' => $i['group'],
                'group_label' => $i['group'] === null ? null : $this->groupLabel($i['group']),
            ])
            ->values()
            ->all();
    }

    /** Grup bawaan ("content") → "admin.groups.content"; grup modul berupa kunci terjemahan namespaced ("example::messages.group"). */
    private function groupLabel(string $group): string
    {
        return __(str_contains($group, '::') ? $group : "admin.groups.{$group}");
    }
}
