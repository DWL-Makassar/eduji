<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Core\Authorization\RoleAssignment;
use App\Core\Users\Http\Requests\StoreUserRequest;
use App\Core\Users\Http\Requests\UpdateUserRequest;
use App\Core\Users\Http\Resources\UserResource;
use App\Core\Users\User;
use App\Core\Users\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    public function __construct(
        private readonly UserService $users,
        private readonly RoleAssignment $roles,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => UserResource::collection($users),
            'filters' => ['q' => $search],
            'can' => ['create' => $request->user()->can('create', User::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/Create', ['roles' => $this->assignableRoles($request)]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return to_route('admin.users.index')->with('success', __('users.created'));
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('admin/users/Edit', [
            'user' => UserResource::make($user->load('roles')),
            'roles' => $this->assignableRoles($request),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Semua role milik pengguna yang boleh dikelola pasti boleh diberikan pelaku (izinnya ⊆ izin pelaku, lihat
        // UserPolicy), jadi tidak ada role "tersembunyi" yang ikut tercabut saat form disimpan.
        $this->users->update($user, $request->validated());

        // Mengubah akun sendiri: jadikan model terbaru sebagai pengguna aktif. AuthenticateSession menyimpan hash password
        // pengguna aktif di akhir permintaan, jadi sesi ini tetap sah sementara sesi lain (hash lama) terlempar.
        if ($request->user()->is($user)) {
            Auth::setUser($user);
        }

        return to_route('admin.users.index')->with('success', __('users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->users->delete($user);

        return to_route('admin.users.index')->with('success', __('users.deleted'));
    }

    /** @return list<string> */
    private function assignableRoles(Request $request): array
    {
        return $this->roles->assignable($request->user());
    }
}
