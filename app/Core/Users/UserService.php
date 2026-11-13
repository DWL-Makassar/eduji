<?php

declare(strict_types=1);

namespace App\Core\Users;

use App\Core\AuditLog\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hanya berisi aturan yang melampaui CRUD: sinkron role (dicatat di audit log) dan penjagaan super admin terakhir.
 */
final class UserService
{
    /** @param array{name: string, email: string, password: string, locale?: ?string, roles?: list<string>} $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create($data);
            $this->syncRoles($user, $data['roles'] ?? []);

            return $user;
        });
    }

    /** @param array{name: string, email: string, password?: ?string, locale?: ?string, roles?: list<string>} $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update($data);

            if (array_key_exists('roles', $data)) {
                $this->guardLastSuperAdmin($user, $data['roles']);
                $this->syncRoles($user, $data['roles']);
            }

            return $user;
        });
    }

    public function delete(User $user): void
    {
        $this->guardLastSuperAdmin($user, []);
        $user->delete();
    }

    /**
     * Ganti role pengguna; perubahannya dicatat karena menentukan hak akses (Auditable hanya mencatat kolom, bukan pivot).
     *
     * @param  list<string>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $before = $user->getRoleNames()->sort()->values()->all();
        $user->syncRoles($roles);
        $after = $user->refresh()->getRoleNames()->sort()->values()->all();

        if ($before !== $after) {
            AuditLog::record('updated', $user, ['roles' => $before], ['roles' => $after]);
        }
    }

    /** @param list<string> $newRoles */
    private function guardLastSuperAdmin(User $user, array $newRoles): void
    {
        $role = config('platform.super_admin_role');

        if (! $user->hasRole($role) || in_array($role, $newRoles, true)) {
            return;
        }

        if (User::role($role)->count() <= 1) {
            throw ValidationException::withMessages(['user' => __('users.last_super_admin')]);
        }
    }
}
