<?php

namespace Database\Seeders;

use App\Cms\Menus\Menu;
use App\Core\Authorization\PermissionSynchronizer;
use App\Core\Users\User;
use Illuminate\Database\Seeder;
use Modules\School\Database\Seeders\StarterSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(PermissionSynchronizer $permissions): void
    {
        $permissions->sync();

        $admin = config('platform.admin');
        $password = $admin['password'] ?: (app()->isProduction() ? null : 'password');

        if ($password === null) {
            $this->command?->warn('ADMIN_PASSWORD kosong di production: akun admin tidak dibuat.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $password],
        );

        $user->assignRole(config('platform.super_admin_role'));

        // Instalasi baru (belum ada menu): isi awal agar situs langsung tampil lengkap.
        if (class_exists(StarterSeeder::class) && ! Menu::query()->exists()) {
            $this->call(StarterSeeder::class);
        }
    }
}
