<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Requests;

use App\Core\Authorization\RoleAssignment;
use App\Core\Users\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'locale' => ['nullable', Rule::in(array_keys(config('platform.locales')))],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in($this->assignableRoles())],
        ];
    }

    /** @return list<string> */
    protected function assignableRoles(): array
    {
        return app(RoleAssignment::class)->assignable($this->user());
    }
}
