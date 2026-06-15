<?php

namespace App\Http\Requests;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RosterAssignmentStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'goal' => $this->nullableTrim($this->input('goal')),
            'notes' => $this->nullableTrim($this->input('notes')),
            'coach_id' => $this->input('coach_id') ?: null,
            'athlete_id' => $this->input('athlete_id') ?: null,
            'started_at' => $this->input('started_at') ?: null,
            'ended_at' => $this->input('ended_at') ?: null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $viewer = $this->user();
        $viewer?->loadMissing('roles');

        $coachRules = ['nullable', 'integer', 'exists:users,id'];

        if ($viewer?->hasRole(RoleName::Admin)) {
            $coachRules[0] = 'required';
        }

        return [
            'coach_id' => $coachRules,
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', Rule::in(collect(CoachAthleteStatus::cases())->map->value->all())],
            'goal' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $viewer = $this->user();

            if (! $viewer) {
                return;
            }

            $viewer->loadMissing('roles');

            $coachId = $viewer->hasRole(RoleName::Admin)
                ? (int) $this->input('coach_id')
                : $viewer->id;
            $athleteId = (int) $this->input('athlete_id');

            if ($coachId > 0 && ! User::query()->role(RoleName::Coach)->whereKey($coachId)->exists()) {
                $validator->errors()->add('coach_id', 'Choose a valid coach account.');
            }

            if ($athleteId > 0 && ! User::query()->role(RoleName::Athlete)->whereKey($athleteId)->exists()) {
                $validator->errors()->add('athlete_id', 'Choose a valid athlete account.');
            }

            if ($coachId > 0 && $coachId === $athleteId) {
                $validator->errors()->add('athlete_id', 'A coach cannot be assigned to themself as an athlete.');
            }
        });
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
