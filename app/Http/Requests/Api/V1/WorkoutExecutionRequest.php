<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class WorkoutExecutionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('workouts.complete') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['completed', 'partial', 'missed', 'skipped'])],
            'confirmed_complete' => ['required_if:status,completed', 'boolean', 'accepted_if:status,completed'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sync_version' => ['nullable', 'integer', 'min:0'],
            'sets' => ['array'],
            'sets.*.exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_index' => ['required', 'integer', 'min:0'],
            'sets.*.exercise_name' => ['required', 'string', 'max:160'],
            'sets.*.set_number' => ['required', 'integer', 'min:1'],
            'sets.*.target_reps' => ['nullable', 'string', 'max:60'],
            'sets.*.target_load' => ['nullable', 'string', 'max:80'],
            'sets.*.target_rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'sets.*.actual_reps' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sets.*.actual_load' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sets.*.rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sets.*.notes' => ['nullable', 'string', 'max:500'],
            'sets.*.completed' => ['required', 'boolean'],
        ];
    }
}
