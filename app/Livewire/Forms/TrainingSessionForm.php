<?php

namespace App\Livewire\Forms;

use App\Models\TrainingSession;
use Livewire\Form;

class TrainingSessionForm extends Form
{
    public string $title = '';

    public string $focus = '';

    public string $scheduledOn = '';

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
            'scheduledOn' => ['required', 'date'],
            'coachNotes' => ['nullable', 'string', 'max:1000'],
            'mediaUrl' => ['nullable', 'url', 'max:255'],
            'exercises.*.name' => ['required', 'string', 'max:160'],
            'exercises.*.sets' => ['nullable', 'string', 'max:20'],
            'exercises.*.reps' => ['nullable', 'string', 'max:40'],
            'exercises.*.rest' => ['nullable', 'string', 'max:40'],
            'exercises.*.load' => ['nullable', 'string', 'max:80'],
            'exercises.*.note' => ['nullable', 'string', 'max:200'],
            'exercises.*.media_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function initialize(?string $scheduledOn = null): void
    {
        $this->scheduledOn = $scheduledOn ?: today()->toDateString();
        $this->exercises = [$this->emptyExercise()];
    }

    public function fillFrom(TrainingSession $session): void
    {
        $this->title = $session->title;
        $this->focus = $session->focus ?: '';
        $this->scheduledOn = $session->scheduled_on->toDateString();
        $this->coachNotes = $session->coach_notes ?: '';
        $this->mediaUrl = $session->media_url ?: '';
        $this->exercises = collect($session->exercises ?: [])
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
     * @return array{name: string, sets: string, reps: string, rest: string, load: string, note: string, media_url: string}
     */
    private function normalizeExercise(array $exercise): array
    {
        return [
            'name' => trim((string) ($exercise['name'] ?? '')),
            'sets' => trim((string) ($exercise['sets'] ?? '')),
            'reps' => trim((string) ($exercise['reps'] ?? '')),
            'rest' => trim((string) ($exercise['rest'] ?? '')),
            'load' => trim((string) ($exercise['load'] ?? '')),
            'note' => trim((string) ($exercise['note'] ?? '')),
            'media_url' => trim((string) ($exercise['media_url'] ?? '')),
        ];
    }

    /**
     * @return array{name: string, sets: string, reps: string, rest: string, load: string, note: string, media_url: string}
     */
    private function emptyExercise(): array
    {
        return $this->normalizeExercise([]);
    }
}
