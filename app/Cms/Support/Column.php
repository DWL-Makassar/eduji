<?php

declare(strict_types=1);

namespace App\Cms\Support;

/** Deskripsi kolom tabel admin. Tipe: text | badge | boolean | datetime. Label: lihat Field::label(). */
final class Column
{
    /** @return array{key: string, label: string, type: string} */
    public static function text(string $key, ?string $label = null): array
    {
        return self::make($key, 'text', $label);
    }

    /** @return array{key: string, label: string, type: string} */
    public static function badge(string $key, ?string $label = null): array
    {
        return self::make($key, 'badge', $label);
    }

    /** @return array{key: string, label: string, type: string} */
    public static function boolean(string $key, ?string $label = null): array
    {
        return self::make($key, 'boolean', $label);
    }

    /** @return array{key: string, label: string, type: string} */
    public static function datetime(string $key, ?string $label = null): array
    {
        return self::make($key, 'datetime', $label);
    }

    /** @return array{key: string, label: string, type: string} */
    private static function make(string $key, string $type, ?string $label): array
    {
        return ['key' => $key, 'label' => Field::label($key, $label), 'type' => $type];
    }
}
