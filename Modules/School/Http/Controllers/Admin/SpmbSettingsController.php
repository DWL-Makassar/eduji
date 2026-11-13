<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\School\Spmb;

/** Admin → Pengaturan SPMB: buka/tutup pendaftaran, tahun ajaran, dan informasi untuk calon pendaftar. */
class SpmbSettingsController
{
    public function __construct(private readonly Spmb $spmb) {}

    public function edit(): Response
    {
        Gate::authorize('school-spmb.manage');

        return Inertia::render('School::admin/SpmbSettings', [
            'values' => ['spmb_open' => $this->spmb->isOpen(), 'spmb_year' => $this->spmb->academicYear() ?? '', 'spmb_info' => $this->spmb->info() ?? ''],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('school-spmb.manage');

        $data = $request->validate([
            'spmb_open' => ['required', 'boolean'],
            'spmb_year' => ['nullable', 'string', 'max:16', 'regex:/^\d{4}(\/\d{4})?$/'],
            'spmb_info' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->spmb->save(['spmb_open' => (bool) $data['spmb_open'], 'spmb_year' => $data['spmb_year'] ?? null, 'spmb_info' => $data['spmb_info'] ?? null]);

        return back()->with('success', __('school::messages.spmb.settings_saved'));
    }
}
