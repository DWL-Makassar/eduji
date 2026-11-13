<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Slug stabil: dibuat sekali dan tidak berubah saat judul diedit
 * (URL lama tidak putus). Mengubah slug hanya bila diisi eksplisit.
 */
final class Slug
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $scope  kolom tambahan yang membatasi keunikan (mis. ['type' => 'post'])
     */
    public static function resolve(string $model, ?string $input, string $fallback, ?Model $record = null, array $scope = [], string $column = 'slug'): string
    {
        $input = trim((string) $input);

        if ($input === '' && $record !== null) {
            return (string) $record->getAttribute($column);
        }

        $base = Str::slug($input !== '' ? $input : $fallback) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (self::taken($model, $column, $slug, $record, $scope)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /** @param class-string<Model> $model */
    private static function taken(string $model, string $column, string $slug, ?Model $record, array $scope): bool
    {
        return $model::withoutGlobalScopes()
            ->where($column, $slug)
            ->where($scope)
            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
            ->exists();
    }
}
