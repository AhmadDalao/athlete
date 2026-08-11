<?php

namespace App\Http\Requests\Api\V1;

class ProgressEntryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('progress.manage') === true;
    }

    public function rules(): array
    {
        return [
            'logged_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'calories_kcal' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'protein_g' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'hydration_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'sleep_quality' => ['nullable', 'integer', 'min:1', 'max:10'],
            'soreness' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
