<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->normalizePhone($this->input('phone')),
            'registration_channel' => Str::lower(trim((string) $this->input('registration_channel', SignupMethod::Email->value))),
            'roles' => collect($this->input('roles', []))
                ->filter(fn ($role) => is_string($role) && $role !== '')
                ->values()
                ->all(),
            'permissions' => PermissionCatalog::sanitize((array) $this->input('permissions', [])),
            'position' => trim((string) $this->input('position', '')),
            'email_verified' => $this->boolean('email_verified'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => [
                'required_if:preferred_contact_method,phone',
                'nullable',
                'string',
                'regex:/^\+?[1-9][0-9]{7,14}$/',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'primary_goal' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:80'],
            'preferred_contact_method' => ['required', 'string', Rule::in(['email', 'phone'])],
            'registration_channel' => ['required', 'string', Rule::in(collect(SignupMethod::cases())->map->value->all())],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(collect(RoleName::cases())->map->value->all())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', Rule::in(PermissionCatalog::keys())],
            'email_verified' => ['boolean'],
        ];
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[^0-9+]/', '', trim($phone)) ?: null;
    }
}
