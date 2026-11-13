<?php

declare(strict_types=1);

namespace App\Website\Sections;

use App\Cms\Menus\MenuRequest;
use App\Core\Media\Rules\ImageAsset;
use App\Core\Support\Sanitizer;
use Closure;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Pengaturan section mengikuti skema field (App\Cms\Support\Field). Kelas ini membuat aturan validasinya,
 * membersihkan nilainya, dan menelusuri nilai per field (mis. id media → data gambar).
 */
final class SectionSchema
{
    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, list<mixed>>
     */
    public static function rules(array $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = "{$prefix}.{$field['name']}";
            $presence = $field['required'] ? 'required' : 'nullable';

            $rules[$key] = match ($field['type']) {
                'text' => [$presence, 'string', 'max:255'],
                'url' => [$presence, 'string', 'max:2048', 'regex:'.MenuRequest::URL_PATTERN],
                'textarea' => [$presence, 'string', 'max:2000'],
                'richtext' => [$presence, 'string', 'max:100000'],
                'number' => [$presence, 'integer', 'min:1', 'max:48'],
                'checkbox' => ['nullable', 'boolean'],
                'select' => [$presence, Rule::in(array_column($field['options'], 'value'))],
                'media' => [$presence, 'integer', new ImageAsset],
                'repeater' => [$presence, 'array', 'list', 'max:30'],
                default => throw new InvalidArgumentException("Tipe field \"{$field['type']}\" belum didukung untuk section."),
            };

            if ($field['type'] === 'repeater') {
                $rules += self::rules($field['fields'], "{$key}.*");
            }
        }

        return $rules;
    }

    /**
     * Nama field untuk pesan validasi ("Tautan tombol tidak valid", bukan "blocks.1.settings.button url").
     *
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, string>
     */
    public static function attributes(array $fields, string $prefix): array
    {
        $names = [];

        foreach ($fields as $field) {
            $names["{$prefix}.{$field['name']}"] = $field['label'];

            if ($field['type'] === 'repeater') {
                $names += self::attributes($field['fields'], "{$prefix}.{$field['name']}.*");
            }
        }

        return $names;
    }

    /**
     * Nilai yang disimpan: hanya kunci yang ada di skema, rich text disanitasi, angka/centang bertipe benar.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function clean(array $fields, array $values): array
    {
        return self::map($fields, $values, fn (array $field, mixed $value) => match ($field['type']) {
            'richtext' => $value === null ? null : Sanitizer::html($value),
            'number', 'media' => $value === null || $value === '' ? null : (int) $value,
            'checkbox' => (bool) $value,
            default => $value === '' ? null : $value,
        });
    }

    /**
     * Terapkan $fn pada nilai tiap field (repeater ditelusuri per baris). Kunci di luar skema dibuang.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     * @param  Closure(array<string, mixed>, mixed): mixed  $fn
     * @return array<string, mixed>
     */
    public static function map(array $fields, array $values, Closure $fn): array
    {
        $out = [];

        foreach ($fields as $field) {
            $value = $values[$field['name']] ?? null;

            $out[$field['name']] = $field['type'] === 'repeater'
                ? array_map(fn (mixed $row) => self::map($field['fields'], is_array($row) ? $row : [], $fn), array_values(is_array($value) ? $value : []))
                : $fn($field, $value);
        }

        return $out;
    }
}
