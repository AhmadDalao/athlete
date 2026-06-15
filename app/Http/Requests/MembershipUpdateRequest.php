<?php

namespace App\Http\Requests;

use App\Enums\MembershipStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembershipUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => trim((string) $this->input('status')),
            'renews_at' => $this->nullify($this->input('renews_at')),
            'ends_at' => $this->nullify($this->input('ends_at')),
            'grace_ends_at' => $this->nullify($this->input('grace_ends_at')),
            'notes' => $this->nullify($this->input('notes')),
            'extension_days' => $this->nullify($this->input('extension_days')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(collect(MembershipStatus::cases())->map->value->all())],
            'auto_renew' => ['required', 'boolean'],
            'renews_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'grace_ends_at' => ['nullable', 'date', 'after_or_equal:ends_at'],
            'extension_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function nullify(mixed $value): mixed
    {
        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
