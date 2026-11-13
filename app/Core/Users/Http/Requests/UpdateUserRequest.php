<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Requests;

use App\Core\Users\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
    }
}
