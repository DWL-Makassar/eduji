<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Users\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Bentuk permission untuk UI kelola role: dikelompokkan per resource ("posts" → view/create/…),
 * dan dibatasi ke permission yang boleh diberikan oleh pengguna yang sedang login.
 */
final class PermissionCatalog
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    /**
     * Super admin boleh memberikan semuanya; pengguna lain hanya permission yang ia miliki sendiri,
     * agar pemegang `roles.manage` tidak bisa menaikkan hak aksesnya sendiri.
     *
     * @return list<string>
     */
    public function grantable(User $actor): array
    {
        $all = $this->registry->all();

        return $actor->isSuperAdmin()
            ? $all
            : array_values(array_filter($all, fn (string $name) => $actor->can($name)));
    }

    /**
     * @param  list<string>  $names
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>
     */
    public function matrix(array $names): array
    {
        $resources = [];

        foreach ($names as $name) {
            $resources[Str::before($name, '.')][] = [
                'name' => $name,
                'label' => $this->actionLabel(Str::after($name, '.')),
            ];
        }

        return collect($resources)
            ->map(fn (array $permissions, string $key) => [
                'key' => $key,
                'label' => $this->resourceLabel($key),
                'permissions' => $permissions,
            ])
            ->values()
            ->all();
    }

    private function resourceLabel(string $key): string
    {
        return Lang::has("roles.resources.{$key}") ? __("roles.resources.{$key}") : Str::headline($key);
    }

    private function actionLabel(string $action): string
    {
        return Lang::has("roles.actions.{$action}") ? __("roles.actions.{$action}") : Str::headline($action);
    }
}
