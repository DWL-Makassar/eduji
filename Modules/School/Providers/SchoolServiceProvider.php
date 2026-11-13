<?php

declare(strict_types=1);

namespace Modules\School\Providers;

use App\Cms\Support\Field;
use App\Core\Admin\AdminMenuRegistry;
use App\Core\Authorization\PermissionRegistry;
use App\Core\Modules\ModuleServiceProvider;
use App\Website\Sections\SectionRegistry;
use App\Website\Seo\SitemapRegistry;
use Modules\School\Http\Controllers\SchoolPageController;
use Modules\School\Models\SchoolRecord;
use Modules\School\Models\Staff;
use Modules\School\Spmb;

/** Modul data sekolah. Registry Core di register(); registry Website (section, sitemap) di boot(). */
class SchoolServiceProvider extends ModuleServiceProvider
{
    /** jenis → [ikon menu, urutan section, jumlah bawaan di section] */
    private const KINDS = [
        'staff' => ['users', 44, 8],
        'achievements' => ['star', 45, 6],
        'extracurriculars' => ['layers', 46, 8],
        'majors' => ['file', 47, 6],
        'units' => ['home', 48, 12],
        'alumni' => ['quote', 72, 6],
    ];

    protected function moduleName(): string
    {
        return 'School';
    }

    public function register(): void
    {
        $permissions = $this->app->make(PermissionRegistry::class);
        $menu = $this->app->make(AdminMenuRegistry::class);
        $order = 400;

        foreach (self::KINDS as $kind => [$icon]) {
            $key = "school-{$kind}";
            $permissions->register('school', array_map(fn (string $action) => "{$key}.{$action}", ['view', 'create', 'update', 'delete']));
            $menu->register($key, "school::messages.resources.{$key}.title", "admin.{$key}.index", "{$key}.view", $icon, $order++, 'school::messages.group');
        }

        // SPMB: panitia melihat/mengubah status/menghapus pendaftar; pengaturan buka-tutup terpisah.
        $permissions->register('school', ['school-registrants.view', 'school-registrants.update', 'school-registrants.delete', 'school-spmb.manage']);
        $menu->register('school-registrants', 'school::messages.spmb.registrants', 'admin.school-registrants.index', 'school-registrants.view', 'file', $order++, 'school::messages.group');
        $menu->register('school-spmb', 'school::messages.spmb.settings', 'admin.school-spmb.edit', 'school-spmb.manage', 'settings', $order, 'school::messages.group');
    }

    public function boot(): void
    {
        parent::boot();

        $sections = $this->app->make(SectionRegistry::class);

        foreach (self::KINDS as $kind => [$icon, $order, $limit]) {
            /** @var class-string<SchoolRecord> $model */
            [$model, $section] = SchoolPageController::PAGES[$kind];

            // Closure (dijalankan per permintaan) agar label field mengikuti bahasa admin, bukan bahasa saat boot.
            $fields = fn () => [
                Field::text('title', label: 'website.fields.title', help: 'website.fields.title_help'),
                Field::number('limit', label: 'website.fields.limit'),
                ...($kind === 'staff'
                    ? [Field::select('type', array_combine(Staff::TYPES, array_map(fn (string $t) => "school::messages.types.{$t}", Staff::TYPES)), blank: true, label: 'school::messages.fields.type')]
                    : []),
            ];

            $sections->register($section, "school::messages.sections.{$kind}", fn (array $s) => $model::query()->active()->ordered()->with('image.media')
                ->when($s['type'] ?? null, fn ($q, string $type) => $q->where('type', $type))
                ->limit((int) ($s['limit'] ?? $limit))->get()
                ->map(fn (SchoolRecord $record) => $record->toCard())->all(), $order, $fields, icon: $icon);
        }

        // Ajakan daftar SPMB: hanya tampil selama pendaftaran dibuka.
        $sections->register('school_spmb', 'school::messages.sections.spmb', fn () => ($spmb = $this->app->make(Spmb::class))->isOpen()
            ? ['year' => $spmb->academicYear(), 'info' => $spmb->info()]
            : null, 12, fn () => [
                Field::text('title', label: 'website.fields.title', help: 'website.fields.title_help'),
                Field::text('button_label', label: 'website.fields.button_label'),
            ], icon: 'file');

        // Halaman daftar masuk sitemap bila ada isinya.
        $this->app->make(SitemapRegistry::class)->register(fn () => collect(SchoolPageController::PAGES)
            ->map(fn (array $page, string $kind) => ['loc' => route("school.{$kind}"), 'lastmod' => $page[0]::query()->active()->latest('updated_at')->first(['updated_at'])?->updated_at])
            ->filter(fn (array $url) => $url['lastmod'] !== null)
            ->values());
    }
}
