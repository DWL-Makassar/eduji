<?php

declare(strict_types=1);

namespace App\Cms\Support;

use Illuminate\Support\Str;

/**
 * Deskripsi field form admin (dikirim ke frontend sebagai skema). Label bawaan diambil dari lang/{id,en}/cms.php
 * ("cms.fields.<name>"); bila tidak ada, dari nama field ("price_per_unit" → "Price Per Unit").
 * Modul memberi label sendiri lewat argumen `label:` (kunci terjemahan, mis. 'example::messages.fields.price').
 */
final class Field
{
    /** @return array<string, mixed> */
    public static function text(string $name, bool $required = false, ?string $help = null, ?string $label = null): array
    {
        return self::make('text', $name, $required, ['help' => $help ? __($help) : null], $label);
    }

    /**
     * Tautan: /halaman, #bagian, https://…, mailto:, tel: (divalidasi dengan MenuRequest::URL_PATTERN).
     *
     * @return array<string, mixed>
     */
    public static function url(string $name, bool $required = false, ?string $label = null, ?string $help = null): array
    {
        return self::make('url', $name, $required, ['help' => $help ? __($help) : null], $label);
    }

    /** @return array<string, mixed> */
    public static function textarea(string $name, bool $required = false, int $rows = 4, ?string $label = null): array
    {
        return self::make('textarea', $name, $required, ['rows' => $rows], $label);
    }

    /** @return array<string, mixed> */
    public static function richtext(string $name, bool $required = false, ?string $label = null): array
    {
        return self::make('richtext', $name, $required, [], $label);
    }

    /**
     * @param  array<string, string>  $options  value => kunci terjemahan (atau label final bila $translate false)
     * @return array<string, mixed>
     */
    public static function select(string $name, array $options, bool $required = false, bool $blank = false, bool $translate = true, ?string $label = null): array
    {
        return self::make('select', $name, $required, ['options' => self::options($options, $blank, $translate)], $label);
    }

    /**
     * @param  array<string, string>  $options  value => label (sudah final, bukan kunci terjemahan)
     * @return array<string, mixed>
     */
    public static function multiselect(string $name, array $options, ?string $label = null): array
    {
        return self::make('multiselect', $name, false, ['options' => self::options($options, false, translate: false)], $label);
    }

    /** @return array<string, mixed> */
    public static function checkbox(string $name, ?string $label = null): array
    {
        return self::make('checkbox', $name, false, [], $label);
    }

    /** @return array<string, mixed> */
    public static function number(string $name, ?string $label = null): array
    {
        return self::make('number', $name, false, [], $label);
    }

    /** @return array<string, mixed> */
    public static function datetime(string $name, bool $required = false, ?string $label = null): array
    {
        return self::make('datetime', $name, $required, [], $label);
    }

    /** @return array<string, mixed> */
    public static function media(string $name, bool $required = false, ?string $label = null): array
    {
        return self::make('media', $name, $required, [], $label);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function repeater(string $name, array $fields, string $itemLabel = 'cms.item', ?string $label = null): array
    {
        return self::make('repeater', $name, false, ['fields' => $fields, 'item_label' => __($itemLabel)], $label);
    }

    /** Label kolom/field: kunci eksplisit → "cms.fields.<nama>" → nama yang dirapikan. */
    public static function label(string $name, ?string $label = null): string
    {
        if ($label !== null) {
            return __($label);
        }

        return trans()->has("cms.fields.{$name}") ? __("cms.fields.{$name}") : Str::headline($name);
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $options, bool $blank, bool $translate = true): array
    {
        $list = $blank ? [['value' => '', 'label' => __('cms.none')]] : [];

        foreach ($options as $value => $label) {
            $list[] = ['value' => (string) $value, 'label' => $translate ? __($label) : $label];
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function make(string $type, string $name, bool $required, array $extra, ?string $label): array
    {
        return [
            'name' => $name,
            'type' => $type,
            'label' => self::label($name, $label),
            'required' => $required,
            ...$extra,
        ];
    }
}
