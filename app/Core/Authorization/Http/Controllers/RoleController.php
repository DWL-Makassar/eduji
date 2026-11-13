<?php

declare(strict_types=1);

namespace App\Core\Authorization\Http\Controllers;

use App\Core\AuditLog\AuditLog;
use App\Core\Authorization\Http\Requests\RoleRequest;
use App\Core\Authorization\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/** Role `super-admin` selalu memegang semua permission (disinkronkan dari registry), jadi tidak bisa diubah/dihapus dari UI. */
class RoleController
{
    public function __construct(private readonly PermissionCatalog $catalog) {}

    public function index(): Response
    {
        Gate::authorize('roles.manage');

        return Inertia::render('admin/roles/Index', [
            'roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'protected' => $this->isProtected($role),
                ])->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('roles.manage');

        return Inertia::render('admin/roles/Create', ['matrix' => $this->matrix($request)]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));

        AuditLog::record('created', $role, [], $this->snapshot($role));

        return to_route('admin.roles.index')->with('success', __('roles.created'));
    }

    public function edit(Request $request, Role $role): Response
    {
        Gate::authorize('roles.manage');
        abort_if($this->isProtected($role), 403);

        return Inertia::render('admin/roles/Edit', [
            'role' => ['id' => $role->id, 'name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()],
            'matrix' => $this->matrix($request),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        // Permission yang tidak boleh diberikan pengguna ini (tidak tampil di form) tetap dipertahankan.
        $kept = array_diff($role->permissions->pluck('name')->all(), $this->catalog->grantable($request->user()));

        $before = $this->snapshot($role);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions([...$request->validated('permissions', []), ...$kept]);

        // Auditable hanya mencatat kolom; perubahan izin (pivot) dicatat di sini.
        $after = $this->snapshot($role);

        if ($before !== $after) {
            AuditLog::record('updated', $role, $before, $after);
        }

        return to_route('admin.roles.index')->with('success', __('roles.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('roles.manage');
        abort_if($this->isProtected($role), 403);

        if ($count = $role->users()->count()) {
            return to_route('admin.roles.index')->with('error', __('roles.in_use', ['count' => $count]));
        }

        $snapshot = $this->snapshot($role);
        $role->delete();

        AuditLog::record('deleted', $role, $snapshot);

        return to_route('admin.roles.index')->with('success', __('roles.deleted'));
    }

    /** @return array{name: string, permissions: list<string>} */
    private function snapshot(Role $role): array
    {
        return ['name' => $role->name, 'permissions' => $role->refresh()->permissions->pluck('name')->sort()->values()->all()];
    }

    private function isProtected(Role $role): bool
    {
        return $role->name === config('platform.super_admin_role');
    }

    /** @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}> */
    private function matrix(Request $request): array
    {
        return $this->catalog->matrix($this->catalog->grantable($request->user()));
    }
}
