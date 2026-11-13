<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menyamakan tabel permissions dengan PermissionRegistry. Aman dijalankan berulang
 * (dipanggil saat seed dan saat deploy lewat `php artisan permissions:sync`).
 *
 * Permission yang belum ada dibuat dengan satu insert massal (bukan `findOrCreate` per nama: 2 query × jumlah permission),
 * karena dijalankan di setiap test dan setiap deploy. Tidak ada event model yang terlewat: Permission tidak memakainya,
 * dan cache permission Spatie dibuang di akhir.
 */
final class PermissionSynchronizer
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function sync(): int
    {
        $names = $this->registry->all();
        $missing = array_values(array_diff($names, Permission::query()->where('guard_name', 'web')->pluck('name')->all()));

        if ($missing !== []) {
            $now = now();

            Permission::query()->insert(array_map(
                fn (string $name) => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
                $missing,
            ));
        }

        $superAdmin = Role::findOrCreate(config('platform.super_admin_role'), 'web');
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return count($names);
    }
}
