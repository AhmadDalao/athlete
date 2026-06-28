<?php

namespace App\Http\Requests\Api;

use App\Enums\WorkoutCompletionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiWorkoutLogStoreRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'completion_status' => ['required', Rule::enum(WorkoutCompletionStatus::class)],
            'performed_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'exertion_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'soreness_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'stress_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sleep_quality_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
