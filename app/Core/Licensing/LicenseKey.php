<?php

declare(strict_types=1);

namespace App\Core\Licensing;

use Carbon\CarbonImmutable;

/**
 * Kunci lisensi bertanda tangan dari penjual: `SKL1.<payload base64url>.<tanda tangan base64url>`.
 * Payload = JSON {id, domain, school, edition, issued_at, expires_at|null}. Tanda tangan ECDSA P-256 + SHA-256
 * dibuat dengan kunci privat penjual (tidak pernah ikut ke aplikasi) dan diverifikasi di sini dengan kunci publik.
 */
final class LicenseKey
{
    public const PREFIX = 'SKL1';

    private function __construct(
        public readonly string $id,
        public readonly string $domain,
        public readonly string $school,
        public readonly string $edition,
        public readonly CarbonImmutable $issuedAt,
        public readonly ?CarbonImmutable $expiresAt,
    ) {}

    /** Null bila format rusak atau tanda tangan tidak cocok dengan kunci publik. */
    public static function parse(string $key, string $publicKeyPem): ?self
    {
        $parts = explode('.', trim($key));

        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return null;
        }

        $payload = self::decode($parts[1]);
        $signature = self::decode($parts[2]);

        if ($payload === null || $signature === null
            || openssl_verify(self::PREFIX.'.'.$parts[1], $signature, $publicKeyPem, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ! is_string($data['id'] ?? null) || ! is_string($data['domain'] ?? null) || ! is_string($data['issued_at'] ?? null)) {
            return null;
        }

        try {
            return new self(
                id: $data['id'],
                domain: self::normalizeHost($data['domain']),
                school: (string) ($data['school'] ?? ''),
                edition: (string) ($data['edition'] ?? 'free'),
                issuedAt: CarbonImmutable::parse($data['issued_at']),
                expiresAt: isset($data['expires_at']) ? CarbonImmutable::parse((string) $data['expires_at'])->endOfDay() : null,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /** Domain cocok persis, atau dengan/ tanpa awalan www. */
    public function allowsHost(string $host): bool
    {
        return self::normalizeHost($host) === $this->domain;
    }

    public function isExpired(?CarbonImmutable $now = null): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isBefore($now ?? CarbonImmutable::now());
    }

    public static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host) ?? $host;
        $host = explode('/', $host)[0];
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    private static function decode(string $value): ?string
    {
        $value = strtr($value, '-_', '+/');
        $decoded = base64_decode(str_pad($value, (int) ceil(strlen($value) / 4) * 4, '='), true);

        return $decoded === false || $decoded === '' ? null : $decoded;
    }
}
