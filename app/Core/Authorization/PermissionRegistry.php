<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/**
 * Core dan Module mendaftarkan permission-nya di sini (satu arah: Module → Core).
 * Format nama: "<resource>.<aksi>", mis. "users.view", "posts.publish".
 */
final class PermissionRegistry
{
    /** @var array<string, list<string>> group => permission names */
    private array $groups = [];

    /** @param list<string> $permissions */
    public function register(string $group, array $permissions): void
    {
        $this->groups[$group] = array_values(array_unique([...($this->groups[$group] ?? []), ...$permissions]));
    }

    /** @return array<string, list<string>> */
    public function groups(): array
    {
        return $this->groups;
    }

    /** @return list<string> */
    public function all(): array
    {
        return array_values(array_unique(array_merge(...array_values($this->groups) ?: [[]])));
    }
}
