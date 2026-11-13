<?php

declare(strict_types=1);

namespace App\Core\Authorization\Http\Requests;

use App\Core\Authorization\PermissionCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/** Dipakai untuk tambah dan ubah role (di route ubah, parameter `role` terisi). */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $this->user()->can('roles.manage')
            && ! ($role instanceof Role && $role->name === config('platform.super_admin_role'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:64', 'regex:/^[\pL\pN _-]+$/u',
                Rule::unique(Role::class, 'name')->where('guard_name', 'web')->ignore($this->route('role')),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(app(PermissionCatalog::class)->grantable($this->user()))],
        ];
    }
}
