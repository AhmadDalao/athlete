<?php

namespace App\Livewire\Forms;

use App\Models\TrainingSession;
use Livewire\Form;

class TrainingSessionForm extends Form
{
    public string $title = '';

    public string $focus = '';

    public ?string $scheduledOn = null;

    public int $dayOffset = 0;

    public int $sortOrder = 0;

    public ?int $estimatedMinutes = null;

    public ?int $phaseId = null;

    public string $coachNotes = '';

    public string $mediaUrl = '';

    /**
     * @var array<int, array<string, string>>
     */
    public array $exercises = [];

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'focus' => ['nullable', 'string', 'max:120'],
            'scheduledOn' => ['nullable', 'date'],
            'dayOffset' => ['required', 'integer', 'min:0', 'max:730'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:1000'],
            'estimatedMinutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'phaseId' => ['nullable', 'integer', 'exists:program_phases,id'],
            'coachNotes' => ['nullable', 'string', 'max:1000'],
            'mediaUrl' => ['nullable', 'url', 'max:255'],
            'exercises.*.name' => ['required', 'string', 'max:160'],
            'exercises.*.exercise_id' => ['nullable', 'integer', 'exists:exercise_library,id'],
            'exercises.*.section' => ['nullable', 'string', 'max:80'],
            'exercises.*.superset_label' => ['nullable', 'string', 'max:40'],
            'exercises.*.sets' => ['nullable', 'integer', 'min:1', 'max:50'],
            'exercises.*.reps' => ['nullable', 'string', 'max:40'],
            'exercises.*.rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'exercises.*.load' => ['nullable', 'string', 'max:80'],
            'exercises.*.unit' => ['nullable', 'string', 'max:30'],
            'exercises.*.note' => ['nullable', 'string', 'max:200'],
            'exercises.*.media_url' => ['nullable', 'url', 'max:255'],
            'exercises.*.movement_type' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function initialize(?string $scheduledOn = null): void
    {
        $this->scheduledOn = $scheduledOn;
        $this->exercises = [$this->emptyExercise()];
    }

    public function fillFrom(TrainingSession $session): void
    {
        $this->title = $session->title;
        $this->focus = $session->focus ?: '';
        $this->scheduledOn = $session->scheduled_on?->toDateString();
        $this->dayOffset = (int) $session->day_offset;
        $this->sortOrder = (int) $session->sort_order;
        $this->estimatedMinutes = $session->estimated_minutes;
        $this->phaseId = $session->program_phase_id;
        $this->coachNotes = $session->coach_notes ?: '';
        $this->mediaUrl = $session->media_url ?: '';
        $source = $session->prescribedExercises()->exists()
            ? $session->prescribedExercises->map(fn ($exercise): array => [
                'exercise_id' => $exercise->exercise_id,
                'section' => $exercise->section,
                'superset_label' => $exercise->superset_label,
                'name' => $exercise->name,
                'sets' => $exercise->target_sets,
                'reps' => $exercise->target_reps,
                'rest_seconds' => $exercise->rest_seconds,
                'load' => $exercise->target_load,
                'unit' => $exercise->unit,
                'note' => $exercise->notes,
                'media_url' => $exercise->media_url,
                'movement_type' => $exercise->movement_type,
            ])
            : collect($session->exercises ?: []);
        $this->exercises = $source
            ->map(fn (array $exercise): array => $this->normalizeExercise($exercise))
            ->values()
            ->all() ?: [$this->emptyExercise()];
    }

    public function addExercise(): void
    {
        $this->exercises[] = $this->emptyExercise();
    }

    public function removeExercise(int $index): void
    {
        unset($this->exercises[$index]);
        $this->exercises = array_values($this->exercises);

        if ($this->exercises === []) {
            $this->exercises[] = $this->emptyExercise();
        }
    }

    public function clear(): void
    {
        $this->reset();
        $this->initialize();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validate();

        return [
            'title' => $data['title'],
            'focus' => $data['focus'] ?: null,
            'scheduled_on' => $data['scheduledOn'],
            'day_offset' => $data['dayOffset'],
            'sort_order' => $data['sortOrder'],
            'estimated_minutes' => $data['estimatedMinutes'],
            'program_phase_id' => $data['phaseId'],
            'coach_notes' => $data['coachNotes'] ?: null,
            'media_url' => $data['mediaUrl'] ?: null,
            'exercises' => collect($data['exercises'])
                ->filter(fn (array $exercise): bool => filled($exercise['name'] ?? null))
                ->map(fn (array $exercise): array => $this->normalizeExercise($exercise))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $exercise
     * @return array<string, mixed>
     */
    private function normalizeExercise(array $exercise): array
    {
        return [
            'exercise_id' => filled($exercise['exercise_id'] ?? null) ? (int) $exercise['exercise_id'] : null,
            'section' => trim((string) ($exercise['section'] ?? 'Main work')),
            'superset_label' => trim((string) ($exercise['superset_label'] ?? '')),
            'name' => trim((string) ($exercise['name'] ?? '')),
            'sets' => max(1, (int) ($exercise['sets'] ?? 1)),
            'reps' => trim((string) ($exercise['reps'] ?? '')),
            'rest_seconds' => $this->normalizeRestSeconds($exercise),
            'load' => trim((string) ($exercise['load'] ?? '')),
            'unit' => trim((string) ($exercise['unit'] ?? '')),
            'note' => trim((string) ($exercise['note'] ?? '')),
            'media_url' => trim((string) ($exercise['media_url'] ?? '')),
            'movement_type' => trim((string) ($exercise['movement_type'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyExercise(): array
    {
        return $this->normalizeExercise([]);
    }

    /** @param array<string, mixed> $exercise */
    private function normalizeRestSeconds(array $exercise): ?int
    {
        $rest = $exercise['rest_seconds'] ?? $exercise['rest'] ?? null;

        if (blank($rest)) {
            return null;
        }

        if (is_numeric($rest)) {
            return (int) $rest;
        }

        return preg_match('/\d+/', (string) $rest, $matches) ? (int) $matches[0] : null;
    }
}
