<?php

declare(strict_types=1);

namespace App\Core\Users;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update') && $this->canManage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->can('users.delete') && $actor->isNot($target) && $this->canManage($actor, $target);
    }

    /**
     * Pelaku hanya boleh mengubah/menghapus pengguna yang hak aksesnya tidak melebihi miliknya. Tanpa aturan ini,
     * pemegang `users.update` bisa mengganti email/password (lalu login sebagai) pengguna yang lebih berkuasa.
     * Super admin boleh mengelola semua; hanya super admin yang boleh mengelola super admin.
     */
    private function canManage(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        $held = $actor->getAllPermissions()->pluck('name')->all();

        return array_diff($target->getAllPermissions()->pluck('name')->all(), $held) === [];
    }
}
