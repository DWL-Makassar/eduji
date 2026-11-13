<?php

declare(strict_types=1);

namespace App\Website\Http\Requests;

use App\Website\Sections\SectionRegistry;
use App\Website\Sections\SectionSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Susunan blok satu halaman. Pengaturan tiap blok divalidasi menurut skema section-nya. */
class PageBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('website.manage');
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $registry = app(SectionRegistry::class);

        $rules = [
            'blocks' => ['present', 'array', 'list', 'max:50'],
            'blocks.*' => ['array'],
            'blocks.*.key' => ['required', 'string', Rule::in(array_keys($registry->labels()))],
            'blocks.*.is_visible' => ['required', 'boolean'],
            'blocks.*.settings' => ['nullable', 'array'],
        ];

        foreach ((array) $this->input('blocks', []) as $i => $block) {
            $section = is_array($block) && is_string($block['key'] ?? null) ? $registry->definition($block['key']) : null;

            if ($section !== null) {
                $rules["blocks.{$i}.variant"] = ['required', Rule::in(array_keys($section['variants']))];
                $rules += SectionSchema::rules($section['fields'], "blocks.{$i}.settings");
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $registry = app(SectionRegistry::class);
        $names = [];

        foreach ((array) $this->input('blocks', []) as $i => $block) {
            $section = is_array($block) && is_string($block['key'] ?? null) ? $registry->definition($block['key']) : null;
            $names += $section ? SectionSchema::attributes($section['fields'], "blocks.{$i}.settings") : [];
        }

        return $names;
    }

    /**
     * Blok yang sudah dibersihkan: hanya pengaturan yang ada di skema, rich text disanitasi.
     *
     * @return list<array{key: string, variant: string, is_visible: bool, settings: array<string, mixed>}>
     */
    public function blocks(): array
    {
        $registry = app(SectionRegistry::class);

        return array_map(fn (array $block) => [
            'key' => $block['key'],
            'variant' => $block['variant'],
            'is_visible' => (bool) $block['is_visible'],
            'settings' => SectionSchema::clean($registry->definition($block['key'])['fields'], $block['settings'] ?? []),
        ], $this->validated('blocks'));
    }
}
