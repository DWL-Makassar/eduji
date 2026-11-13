<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers;

use App\Core\Support\Paginate;
use App\Website\Seo\SeoBuilder;
use Inertia\Inertia;
use Inertia\Response;
use Modules\School\Models\Achievement;
use Modules\School\Models\Alumnus;
use Modules\School\Models\Extracurricular;
use Modules\School\Models\Major;
use Modules\School\Models\SchoolRecord;
use Modules\School\Models\Staff;
use Modules\School\Models\Unit;

/**
 * Halaman daftar publik data sekolah. Satu halaman Vue (School::site/Index) merender komponen section yang sama
 * dengan page builder, jadi tampilan daftar dan section beranda selalu serupa.
 */
class SchoolPageController
{
    /** jenis (dari route) → [model, kunci section]. Kunci resource admin = "school-<jenis>". */
    public const PAGES = [
        'staff' => [Staff::class, 'school_staff'],
        'achievements' => [Achievement::class, 'school_achievements'],
        'extracurriculars' => [Extracurricular::class, 'school_extracurriculars'],
        'majors' => [Major::class, 'school_majors'],
        'units' => [Unit::class, 'school_units'],
        'alumni' => [Alumnus::class, 'school_alumni'],
    ];

    public function index(string $kind, SeoBuilder $seo): Response
    {
        /** @var class-string<SchoolRecord> $model */
        [$model, $section] = self::PAGES[$kind];
        $title = __("school::messages.resources.school-{$kind}.title");

        $items = $model::query()->active()->ordered()->with('image.media')->paginate(24);

        return Inertia::render('School::site/Index', [
            'title' => $title,
            'section' => $section,
            'items' => Paginate::shape($items, fn (SchoolRecord $record) => $record->toCard()),
            'seo' => $seo->make($title)->toArray(),
        ]);
    }
}
