<?php

declare(strict_types=1);

namespace App\Core\Settings;

use App\Core\AuditLog\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Key/value settings per group (mis. "general", "seo", atau nama modul seperti "example").
 * Pengganti tabel settings satu-baris-65-kolom: Core tidak perlu tahu kolom milik modul.
 *
 * Seluruh tabel di-cache satu kunci dan dibuang saat ada penulisan.
 */
final class SettingsStore
{
    private const CACHE_KEY = 'platform.settings';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $row = $this->rows()[$group][$key] ?? null;

        return $row === null ? $default : $this->decode($row);
    }

    /** @return array<string, mixed> */
    public function group(string $group): array
    {
        return array_map($this->decode(...), $this->rows()[$group] ?? []);
    }

    public function set(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $setting = Setting::firstOrNew(['group' => $group, 'key' => $key]);
        $existed = $setting->exists;
        $old = $existed ? $this->decode(['v' => $setting->value, 'e' => (bool) $setting->is_encrypted]) : null;

        $setting->fill(['value' => $encrypted ? Crypt::encryptString($json) : $json, 'is_encrypted' => $encrypted])->save();

        $this->flush();

        // Hanya perubahan nyata yang dicatat (form pengaturan menyimpan semua kolom sekaligus).
        if (! $existed || $old !== $value) {
            $mask = fn (mixed $v) => $encrypted ? '•••' : $v; // nilai rahasia tidak pernah masuk log
            AuditLog::record(
                $existed ? 'updated' : 'created',
                $setting,
                $existed ? ['setting' => "{$group}.{$key}", 'value' => $mask($old)] : [],
                ['setting' => "{$group}.{$key}", 'value' => $mask($value)],
            );
        }
    }

    /** @param array<string, mixed> $values */
    public function setMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($group, $key, $value, $this->isEncrypted($group, $key));
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Branding::flush(); // URL logo/favicon diturunkan dari pengaturan
    }

    private function isEncrypted(string $group, string $key): bool
    {
        return (bool) ($this->rows()[$group][$key]['e'] ?? false);
    }

    /** @return array<string, array<string, array{v: ?string, e: bool}>> */
    private function rows(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $rows = [];
            foreach (Setting::query()->get(['group', 'key', 'value', 'is_encrypted']) as $setting) {
                $rows[$setting->group][$setting->key] = ['v' => $setting->value, 'e' => $setting->is_encrypted];
            }

            return $rows;
        });
    }

    /** @param array{v: ?string, e: bool} $row */
    private function decode(array $row): mixed
    {
        if ($row['v'] === null) {
            return null;
        }

        $json = $row['e'] ? Crypt::decryptString($row['v']) : $row['v'];

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
