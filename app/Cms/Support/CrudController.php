<?php

declare(strict_types=1);

namespace App\Cms\Support;

use App\Core\Media\Http\Resources\MediaAssetResource;
use App\Core\Media\MediaAsset;
use App\Core\Support\Paginate;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD admin seragam untuk resource CMS. Subclass hanya mendeskripsikan resource-nya
 * (kolom, field, nilai, cara menyimpan); halaman Vue `admin/cms/Index` & `admin/cms/Form`
 * dirender dari skema itu.
 *
 * Otorisasi: permission "<key>.<view|create|update|delete>" (didaftarkan CmsServiceProvider).
 * Bila suatu resource butuh aturan per-record, tambahkan Policy khusus untuk resource itu.
 */
abstract class CrudController
{
    /** Kunci resource: dasar nama permission (pages.view), route (admin.pages.*), dan label. */
    abstract protected function key(): string;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** @return class-string<CmsRequest> */
    abstract protected function requestClass(): string;

    /** @return list<array{key: string, label: string, type: string}> */
    abstract protected function columns(): array;

    /** @return list<array<string, mixed>> */
    abstract protected function fields(): array;

    /** @return array<string, mixed> nilai untuk tiap kolom di daftar */
    abstract protected function row(Model $record): array;

    /** @return array<string, mixed> nilai awal form (record null = create) */
    abstract protected function values(?Model $record): array;

    /** @param array<string, mixed> $data */
    abstract protected function persist(?Model $record, array $data): Model;

    /** @return list<string> kolom yang dicari lewat ?q= */
    protected function searchColumns(): array
    {
        return [];
    }

    protected function query(): Builder
    {
        return $this->modelClass()::query()->latest('id');
    }

    public function index(Request $request): Response
    {
        $this->authorizeAction('view');

        $search = trim((string) $request->query('q'));

        $records = $this->query()
            ->when($search !== '' && $this->searchColumns() !== [], fn (Builder $q) => $q->where(
                fn (Builder $q) => collect($this->searchColumns())->each(fn (string $c) => $q->orWhere($c, 'like', "%{$search}%"))
            ))
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return Inertia::render('admin/cms/Index', [
            'resource' => $this->meta(),
            'columns' => $this->columns(),
            'rows' => Paginate::shape($records, fn (Model $r) => [
                'id' => $r->getKey(),
                ...$this->row($r),
                'can' => ['update' => $this->can('update'), 'delete' => $this->can('delete')],
            ]),
            'filters' => ['q' => $search],
            'searchable' => $this->searchColumns() !== [],
            'can' => ['create' => $this->can('create')],
        ]);
    }

    public function create(): Response
    {
        $this->authorizeAction('create');

        return $this->form(null);
    }

    public function store(): RedirectResponse
    {
        $this->authorizeAction('create');

        $data = app($this->requestClass())->validated();

        DB::transaction(fn () => $this->persist(null, $data));

        return $this->backToIndex('created');
    }

    public function edit(string $id): Response
    {
        $this->authorizeAction('update');

        return $this->form($this->find($id));
    }

    public function update(string $id): RedirectResponse
    {
        $this->authorizeAction('update');

        $record = $this->find($id);
        $data = app($this->requestClass())->validated();

        DB::transaction(fn () => $this->persist($record, $data));

        return $this->backToIndex('updated');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorizeAction('delete');

        $this->find($id)->delete();

        return $this->backToIndex('deleted');
    }

    // ----- helper untuk subclass -----

    /** @return array<string, mixed>|null */
    protected function mediaValue(?MediaAsset $asset): ?array
    {
        return $asset ? MediaAssetResource::make($asset)->resolve() : null;
    }

    protected function datetimeValue(?CarbonInterface $date): ?string
    {
        return $date?->format('Y-m-d\TH:i');
    }

    /**
     * Ganti seluruh anak (mis. slide, item menu) dengan baris dari form; `order` mengikuti urutan baris.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  Closure(array<string, mixed>, int): array<string, mixed>  $attributes
     */
    protected function replaceChildren(HasMany $relation, array $rows, Closure $attributes): void
    {
        $relation->delete();

        foreach (array_values($rows) as $index => $row) {
            $relation->create($attributes($row, $index) + ['order' => $index]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: ?string, description: ?string}|null
     */
    protected function seoFrom(array $data): ?array
    {
        $seo = array_filter([
            'title' => $data['seo_title'] ?? null,
            'description' => $data['seo_description'] ?? null,
        ]);

        return $seo === [] ? null : $seo;
    }

    // ----- internal -----

    protected function find(string $id): Model
    {
        return $this->modelClass()::query()->findOrFail($id);
    }

    protected function form(?Model $record): Response
    {
        return Inertia::render('admin/cms/Form', [
            'resource' => $this->meta(),
            'fields' => $this->fields(),
            'values' => $this->values($record),
            'recordId' => $record?->getKey(),
        ]);
    }

    /** Judul daftar (jamak). Modul menimpa ini dengan terjemahan miliknya, mis. __('example::messages.title'). */
    protected function title(): string
    {
        return __("cms.resources.{$this->key()}");
    }

    /** Nama tunggal untuk tombol "Tambah …" dan pesan sukses. */
    protected function singular(): string
    {
        return __("cms.singular.{$this->key()}");
    }

    /** @return array{key: string, title: string, singular: string, routeBase: string} */
    protected function meta(): array
    {
        return [
            'key' => $this->key(),
            'title' => $this->title(),
            'singular' => $this->singular(),
            'routeBase' => "admin.{$this->key()}",
        ];
    }

    protected function can(string $action): bool
    {
        return Gate::allows("{$this->key()}.{$action}");
    }

    protected function authorizeAction(string $action): void
    {
        Gate::authorize("{$this->key()}.{$action}");
    }

    private function backToIndex(string $event): RedirectResponse
    {
        return to_route("admin.{$this->key()}.index")->with('success', __("cms.messages.{$event}", ['name' => $this->singular()]));
    }
}
