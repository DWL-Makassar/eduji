<?php

declare(strict_types=1);

namespace App\Core\Licensing;

use App\Core\Settings\SettingsStore;

/**
 * Status lisensi situs ini. Hanya berpengaruh bila edisi mewajibkan lisensi (edisi gratis); edisi lengkap selalu `valid`.
 * Verifikasi sepenuhnya lokal (tanda tangan + domain + masa berlaku), jadi situs tetap jalan walau server penjual mati.
 * Alamat lokal (localhost, *.test, dst.) yang diakses dari komputer itu sendiri (IP loopback) selalu diizinkan agar sekolah
 * bisa mencoba pemasangan sebelum online. Syarat IP loopback mencegah server publik dibuka lewat nama host palsu (*.test).
 */
class LicenseManager
{
    public const VALID = 'valid';

    public const MISSING = 'missing';

    public const INVALID = 'invalid';

    public const WRONG_DOMAIN = 'wrong_domain';

    public const EXPIRED = 'expired';

    private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    private const LOCAL_SUFFIXES = ['.test', '.localhost', '.local'];

    public function __construct(
        private readonly SettingsStore $settings,
        private readonly bool $enforced,
        private readonly string $publicKey,
    ) {}

    public function enforced(): bool
    {
        return $this->enforced;
    }

    public function storedKey(): ?string
    {
        $key = $this->settings->get('license', 'key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function current(): ?LicenseKey
    {
        $key = $this->storedKey();

        return $key ? LicenseKey::parse($key, $this->publicKey) : null;
    }

    /** Status untuk permintaan saat ini (host + IP klien). */
    public function status(string $host, ?string $clientIp): string
    {
        if (! $this->enforced || self::isLocal($host, $clientIp)) {
            return self::VALID;
        }

        $key = $this->storedKey();

        return $key === null ? self::MISSING : $this->check($key, $host);
    }

    /** Memeriksa kunci yang akan disimpan. */
    public function check(string $key, string $host, ?string $clientIp = null): string
    {
        $license = LicenseKey::parse($key, $this->publicKey);

        return match (true) {
            $license === null => self::INVALID,
            $license->isExpired() => self::EXPIRED,
            ! self::isLocal($host, $clientIp) && ! $license->allowsHost($host) => self::WRONG_DOMAIN,
            default => self::VALID,
        };
    }

    public function store(string $key): void
    {
        $this->settings->set('license', 'key', trim($key));
    }

    public static function isLocal(string $host, ?string $clientIp): bool
    {
        if (! in_array($clientIp, ['127.0.0.1', '::1'], true)) {
            return false;
        }

        $host = LicenseKey::normalizeHost($host);

        if (in_array($host, self::LOCAL_HOSTS, true)) {
            return true;
        }

        foreach (self::LOCAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
