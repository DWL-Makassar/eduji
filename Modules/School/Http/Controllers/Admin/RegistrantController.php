<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers\Admin;

use App\Cms\Support\Column;
use App\Core\Support\Paginate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\School\Models\Registrant;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin → Pendaftar SPMB: daftar, detail + berkas, ubah status (dengan catatan untuk pendaftar), hapus. */
class RegistrantController
{
    public function index(Request $request): Response
    {
        Gate::authorize('school-registrants.view');

        $search = trim((string) $request->query('q'));
        $registrants = Registrant::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return Inertia::render('admin/cms/Index', [
            'resource' => $this->meta() + ['rowAction' => 'show'],
            'columns' => [
                Column::text('number', label: 'school::messages.spmb.fields.number'),
                Column::text('full_name', label: 'school::messages.spmb.fields.full_name'),
                Column::badge('status', label: 'school::messages.spmb.fields.status'),
                Column::datetime('created_at'),
            ],
            'rows' => Paginate::shape($registrants, fn (Registrant $r) => [
                'id' => $r->id,
                'number' => $r->number,
                'full_name' => $r->full_name,
                'status' => __("school::messages.spmb.statuses.{$r->status}"),
                'created_at' => $r->created_at?->toIso8601String(),
                'can' => ['update' => true, 'delete' => Gate::allows('school-registrants.delete')],
            ]),
            'filters' => ['q' => $search],
            'searchable' => true,
            'can' => ['create' => false],
        ]);
    }

    public function show(Registrant $registrant): Response
    {
        Gate::authorize('school-registrants.view');

        $registrant->load('major:id,name');
        $field = fn (string $key, mixed $value, ?string $type = null) => ['label' => __("school::messages.spmb.fields.{$key}"), 'value' => $value, 'type' => $type];

        return Inertia::render('School::admin/Registrant', [
            'registrant' => ['id' => $registrant->id, 'number' => $registrant->number, 'status' => $registrant->status, 'note' => $registrant->note ?? ''],
            'details' => [
                $field('access_code', $registrant->access_code), // untuk membantu orang tua yang lupa kodenya
                $field('academic_year', $registrant->academic_year),
                $field('full_name', $registrant->full_name),
                $field('nisn', $registrant->nisn),
                $field('gender', __("school::messages.spmb.genders.{$registrant->gender}")),
                $field('birth_place', $registrant->birth_place),
                $field('birth_date', $registrant->birth_date->toDateString(), 'date'),
                $field('previous_school', $registrant->previous_school),
                $field('major_id', $registrant->major?->name),
                $field('address', $registrant->address, 'multiline'),
                $field('parent_name', $registrant->parent_name),
                $field('parent_phone', $registrant->parent_phone),
                $field('email', $registrant->email),
                $field('created_at', $registrant->created_at?->toIso8601String(), 'datetime'),
            ],
            'documents' => collect(Registrant::DOCUMENTS)->keys()->map(fn (string $type) => [
                'type' => $type,
                'label' => __("school::messages.spmb.documents.{$type}"),
                'url' => $registrant->documentPath($type) ? route('admin.school-registrants.document', [$registrant, $type]) : null,
            ])->values(),
            'statuses' => collect(Registrant::STATUSES)->map(fn (string $s) => ['value' => $s, 'label' => __("school::messages.spmb.statuses.{$s}")])->values(),
            'can' => ['update' => Gate::allows('school-registrants.update'), 'delete' => Gate::allows('school-registrants.delete')],
        ]);
    }

    public function updateStatus(Request $request, Registrant $registrant): RedirectResponse
    {
        Gate::authorize('school-registrants.update');

        $registrant->update($request->validate([
            'status' => ['required', Rule::in(Registrant::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('success', __('school::messages.spmb.status_saved'));
    }

    /** Berkas pendaftar hanya untuk panitia yang berhak; ditampilkan inline (PDF/gambar) dari disk privat. */
    public function document(Registrant $registrant, string $type): StreamedResponse
    {
        Gate::authorize('school-registrants.view');

        $path = $registrant->documentPath($type);
        abort_unless($path && Storage::disk(Registrant::DISK)->exists($path), 404);

        return Storage::disk(Registrant::DISK)->response($path, "{$registrant->number}-{$type}.".pathinfo($path, PATHINFO_EXTENSION));
    }

    public function destroy(Registrant $registrant): RedirectResponse
    {
        Gate::authorize('school-registrants.delete');

        $registrant->delete(); // berkasnya ikut dihapus (Registrant::booted)

        return to_route('admin.school-registrants.index')->with('success', __('cms.messages.deleted', ['name' => __('school::messages.spmb.singular')]));
    }

    /** @return array{key: string, title: string, singular: string, routeBase: string} */
    private function meta(): array
    {
        return [
            'key' => 'school-registrants',
            'title' => __('school::messages.spmb.registrants'),
            'singular' => __('school::messages.spmb.singular'),
            'routeBase' => 'admin.school-registrants',
        ];
    }
}
