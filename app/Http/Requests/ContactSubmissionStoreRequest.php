<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactSubmissionStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimmed($this->input('name')),
            'email' => $this->trimmed($this->input('email')),
            'phone' => $this->nullableTrimmed($this->input('phone')),
            'organization' => $this->nullableTrimmed($this->input('organization')),
            'role_interest' => $this->nullableTrimmed($this->input('role_interest')),
            'message' => $this->trimmed($this->input('message')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9][0-9]{7,14}$/'],
            'organization' => ['nullable', 'string', 'max:120'],
            'role_interest' => ['nullable', 'string', Rule::in(['coach', 'athlete', 'admin', 'other'])],
            'message' => ['required', 'string', 'min:20', 'max:3000'],
        ];
    }

    private function trimmed(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
