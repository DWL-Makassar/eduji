<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Users\User;
use Spatie\Permission\Models\Role;

/**
 * Role yang boleh diberikan `$actor` kepada pengguna lain. Super admin: semua. Selain itu hanya role yang izinnya
 * seluruhnya dimiliki pelaku (tidak melebihi hak aksesnya sendiri) dan bukan role super admin — sehingga pemegang
 * `users.update` tidak bisa menaikkan hak aksesnya atau orang lain di atas dirinya.
 */
final class RoleAssignment
{
    /** @return list<string> nama role, urut abjad */
    public function assignable(User $actor): array
    {
        $roles = Role::query()->with('permissions:id,name')->orderBy('name')->get();

        if ($actor->isSuperAdmin()) {
            return $roles->pluck('name')->all();
        }

        $held = $actor->getAllPermissions()->pluck('name')->all();

        return $roles
            ->reject(fn (Role $role) => $role->name === config('platform.super_admin_role'))
            ->filter(fn (Role $role) => array_diff($role->permissions->pluck('name')->all(), $held) === [])
            ->pluck('name')
            ->values()
            ->all();
    }
}
