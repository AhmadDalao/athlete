<?php

namespace App\Http\Requests;

use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentEventStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_type' => Str::lower(trim((string) $this->input('event_type'))),
            'status' => Str::lower(trim((string) $this->input('status'))),
            'provider' => $this->nullify($this->input('provider')),
            'reference' => $this->nullify($this->input('reference')),
            'currency' => Str::upper(trim((string) $this->input('currency', 'USD'))),
            'event_at' => $this->nullify($this->input('event_at')),
            'notes' => $this->nullify($this->input('notes')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', Rule::in(collect(PaymentEventType::cases())->map->value->all())],
            'status' => ['required', 'string', Rule::in(collect(PaymentEventStatus::cases())->map->value->all())],
            'provider' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'event_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function nullify(mixed $value): mixed
    {
        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
