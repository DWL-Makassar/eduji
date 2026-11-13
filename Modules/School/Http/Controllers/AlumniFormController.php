<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers;

use App\Website\Seo\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\School\Http\Controllers\Admin\AlumniController;
use Modules\School\Models\Alumnus;

/** Formulir pendataan alumni (publik). Kiriman masuk nonaktif; tampil setelah admin mengaktifkannya. */
class AlumniFormController
{
    public function create(SeoBuilder $seo): Response
    {
        return Inertia::render('School::site/AlumniForm', [
            'labels' => __('school::messages.fields'),
            'seo' => $seo->make(__('school::messages.alumni.form_title'))->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([...AlumniController::alumniRules(), 'website' => ['nullable', 'max:0']]); // website = honeypot
        unset($data['website']);

        Alumnus::create($data + ['is_active' => false]);

        return back()->with('success', __('school::messages.alumni.thanks'));
    }
}
