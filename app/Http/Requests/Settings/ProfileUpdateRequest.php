<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->normalizePhone($this->input('phone')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                'required_if:preferred_contact_method,phone',
                'nullable',
                'string',
                'regex:/^\+?[1-9][0-9]{7,14}$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'primary_goal' => ['nullable', 'string', 'max:255'],
            'preferred_contact_method' => ['required', 'string', Rule::in(['email', 'phone'])],
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
