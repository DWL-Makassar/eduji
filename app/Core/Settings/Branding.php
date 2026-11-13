<?php

declare(strict_types=1);

namespace App\Core\Settings;

use App\Core\Media\MediaAsset;
use Illuminate\Support\Facades\Cache;

/**
 * Identitas situs dari pengaturan: nama, logo, favicon. URL media dihitung dari data lalu di-cache (tanpa cache, setiap
 * halaman penuh menjalankan hingga 4 query untuk logo + favicon). Cache dibuang saat pengaturan atau entri media berubah
 * (SettingsStore::flush, MediaAsset), dan kuncinya memuat URL dasar/disk sehingga pindah domain tidak menyajikan URL lama.
 */
final class Branding
{
    private const CACHE_KEY = 'platform.branding';

    public function __construct(private readonly SettingsStore $settings) {}

    public function siteName(): string
    {
        return (string) ($this->settings->get('general', 'site_name') ?: config('app.name'));
    }

    public function logoUrl(): ?string
    {
        return $this->urls()['logo'];
    }

    public function faviconUrl(): ?string
    {
        return $this->urls()['favicon'];
    }

    public static function flush(): void
    {
        Cache::forget(self::key());
    }

    /** @return array{logo: ?string, favicon: ?string} */
    private function urls(): array
    {
        return Cache::rememberForever(self::key(), fn () => [
            'logo' => $this->mediaUrl('logo_media_id'),
            'favicon' => $this->mediaUrl('favicon_media_id'),
        ]);
    }

    private function mediaUrl(string $key): ?string
    {
        $id = $this->settings->get('branding', $key);

        return $id ? MediaAsset::with('media')->find($id)?->file()?->getUrl() : null;
    }

    private static function key(): string
    {
        return self::CACHE_KEY.'.'.md5(config('filesystems.disks.public.url').'|'.config('media-library.disk_name'));
    }
}
