<?php

declare(strict_types=1);

namespace App\Website\Http\Controllers\Admin;

use App\Cms\Pages\Page;
use App\Core\Media\Http\Resources\MediaAssetResource;
use App\Core\Media\MediaAsset;
use App\Core\Settings\SettingsStore;
use App\Website\Http\Requests\PageBuilderRequest;
use App\Website\Theme\ThemeManager;
use App\Website\Sections\PageBlock;
use App\Website\Sections\SectionRegistry;
use App\Website\Sections\SectionSchema;
use App\Website\Sections\Templates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** Admin → Susun halaman: beranda (tanpa {page}) atau halaman CMS disusun dari blok section. */
class PageBuilderController
{
    public function __construct(
        private readonly SectionRegistry $sections,
        private readonly SettingsStore $settings,
    ) {}

    public function edit(?Page $page = null): Response
    {
        Gate::authorize('website.manage');

        $blocks = PageBlock::layout($page?->id);
        $blocks ??= array_map(fn (array $block) => [...$block, 'is_visible' => true], $this->sections->defaultBlocks());
        // Blok yang section-nya tidak terdaftar lagi (modul dimatikan) tidak bisa diedit; terbuang saat disimpan.
        $blocks = array_values(array_filter($blocks, fn (array $block) => $this->sections->definition($block['key']) !== null));

        return Inertia::render('admin/website/Builder', [
            'page' => $page?->only(['id', 'title', 'slug']),
            'pages' => Page::query()->orderBy('title')->get(['id', 'title']),
            'catalog' => $this->sections->catalog(),
            'templates' => $page ? [] : Templates::options(),
            'blocks' => array_map(fn (array $block) => [...$block, 'settings' => $this->editorSettings($block)], $blocks),
            'preview' => $this->sections->resolve($blocks),
            // Pratinjau memakai desain beranda yang aktif (Desain 1/2) agar sama dengan situs publik.
            'design' => app(ThemeManager::class)->active('design'),
        ]);
    }

    public function update(PageBuilderRequest $request, ?Page $page = null): RedirectResponse
    {
        DB::transaction(fn () => $this->store($page, $request->blocks()));

        return back()->with('success', __('website.builder.saved'));
    }

    /** Terapkan template awal ke beranda: susunan blok + warna/font/sudut situs (menimpa yang ada). */
    public function applyTemplate(Request $request): SymfonyResponse
    {
        Gate::authorize('website.manage');

        $template = Templates::all()[$request->validate(['template' => ['required', 'string', Rule::in(array_keys(Templates::all()))]])['template']];

        DB::transaction(function () use ($template) {
            $this->store(null, array_map(fn (array $block) => [
                'key' => $block['key'],
                'variant' => $block['variant'] ?? 'default',
                'is_visible' => true,
                'settings' => SectionSchema::clean($this->sections->definition($block['key'])['fields'], $block['settings'] ?? []),
            ], $template['blocks']));

            $this->settings->setMany('website', ['theme' => $template['theme'], 'font' => $template['font'], 'radius' => $template['radius']]);
        });

        // Muat ulang penuh (bukan kunjungan Inertia): editor harus memuat blok template yang baru, dan CSS tema di <head>
        // baru berubah setelah halaman dirender ulang. Tanpa ini editor menampilkan blok lama dan simpan berikutnya membatalkan template.
        session()->flash('success', __('website.builder.template_applied'));

        return Inertia::location(route('admin.website.builder'));
    }

    /** Pratinjau susunan yang belum disimpan (dipanggil editor setiap kali ada perubahan). */
    public function preview(PageBuilderRequest $request): JsonResponse
    {
        return response()->json(['sections' => $this->sections->resolve($request->blocks())]);
    }

    /** @param list<array{key: string, variant: string, is_visible: bool, settings: array<string, mixed>}> $blocks */
    private function store(?Page $page, array $blocks): void
    {
        PageBlock::query()->where('page_id', $page?->id)->delete();

        foreach ($blocks as $position => $block) {
            PageBlock::create([
                'page_id' => $page?->id,
                'type' => $block['key'],
                'variant' => $block['variant'],
                'settings' => $block['settings'],
                'position' => $position,
                'is_visible' => $block['is_visible'],
            ]);
        }

        if ($page === null) {
            // Penanda bahwa beranda sudah disusun: tanpa ini, beranda tanpa blok kembali menampilkan semua section bawaan.
            $this->settings->set('website', 'home_built', true);
        }
    }

    /**
     * Pengaturan untuk form editor: id media → objek media pustaka (bentuk yang dipakai MediaField).
     *
     * @param  array{key: string, settings: array<string, mixed>}  $block
     * @return array<string, mixed>
     */
    private function editorSettings(array $block): array
    {
        $fields = $this->sections->definition($block['key'])['fields'] ?? [];

        return SectionSchema::map($fields, $block['settings'], fn (array $field, mixed $value) => match (true) {
            $field['type'] === 'media' => ($asset = $value ? MediaAsset::with('media')->find($value) : null) ? MediaAssetResource::make($asset)->resolve() : null,
            $field['type'] === 'checkbox' => (bool) $value,
            default => $value ?? ($field['type'] === 'repeater' ? [] : ''),
        });
    }
}
