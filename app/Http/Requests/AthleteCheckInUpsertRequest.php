<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AthleteCheckInUpsertRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->nullableTrim($this->input('notes')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logged_date' => ['required', 'date'],
            'weight_kg' => ['nullable', 'numeric', 'between:20,400'],
            'body_fat_percentage' => ['nullable', 'numeric', 'between:1,80'],
            'waist_cm' => ['nullable', 'numeric', 'between:20,250'],
            'calories_consumed' => ['nullable', 'integer', 'min:0', 'max:15000'],
            'protein_grams' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'carbs_grams' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'fat_grams' => ['nullable', 'integer', 'min:0', 'max:1500'],
            'water_liters' => ['nullable', 'numeric', 'between:0,20'],
            'meals_logged_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'energy_score' => ['nullable', 'integer', 'between:1,10'],
            'soreness_score' => ['nullable', 'integer', 'between:1,10'],
            'stress_score' => ['nullable', 'integer', 'between:1,10'],
            'sleep_quality_score' => ['nullable', 'integer', 'between:1,10'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
