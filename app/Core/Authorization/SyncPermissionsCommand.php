<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sinkronkan permission dari Core & modul aktif, dan berikan semuanya ke super admin';

    public function handle(PermissionSynchronizer $synchronizer): int
    {
        $this->info("{$synchronizer->sync()} permission tersinkron.");

        return self::SUCCESS;
    }
}
