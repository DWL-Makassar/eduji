<?php

declare(strict_types=1);

namespace App\Core\Modules;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

/**
 * Memuat modul yang tercantum di config('modules.enabled').
 * Setiap modul punya Modules/<Name>/module.json dan Modules\<Name>\Providers\<Name>ServiceProvider.
 * Arah dependensi: Module → Website → CMS → Core, tidak pernah sebaliknya.
 */
final class ModuleManager
{
    /** Nama "requires" yang selalu tersedia (bagian dari Master Source). */
    private const BUILT_IN = ['core', 'cms', 'website'];

    public function __construct(private readonly Application $app) {}

    /** @return list<string> */
    public function enabled(): array
    {
        return array_values(array_unique(config('modules.enabled', [])));
    }

    public function isEnabled(string $name): bool
    {
        return in_array($name, $this->enabled(), true);
    }

    public function registerEnabled(): void
    {
        foreach ($this->enabled() as $name) {
            $this->assertRequirements($name);

            $provider = "Modules\\{$name}\\Providers\\{$name}ServiceProvider";

            if (! class_exists($provider)) {
                throw new RuntimeException("Module [{$name}]: provider class {$provider} tidak ditemukan.");
            }

            $this->app->register($provider);
        }
    }

    /** @return array{name: string, version?: string, requires?: list<string>} */
    public function manifest(string $name): array
    {
        $path = base_path("Modules/{$name}/module.json");

        if (! is_file($path)) {
            throw new RuntimeException("Module [{$name}]: module.json tidak ditemukan di {$path}.");
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertRequirements(string $name): void
    {
        foreach ($this->manifest($name)['requires'] ?? [] as $required) {
            $key = strtolower($required);

            if (in_array($key, self::BUILT_IN, true) || $this->isEnabled($required)) {
                continue;
            }

            throw new RuntimeException("Module [{$name}] membutuhkan [{$required}] yang tidak aktif di config/modules.php.");
        }
    }
}
