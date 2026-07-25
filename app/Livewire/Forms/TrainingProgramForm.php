<?php

namespace App\Livewire\Forms;

use App\Models\TrainingProgram;
use Livewire\Form;

class TrainingProgramForm extends Form
{
    public string $title = '';

    public string $goal = '';

    public string $status = 'active';

    public ?string $startsOn = null;

    public ?string $endsOn = null;

    public string $notes = '';

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'goal' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:draft,active,archived'],
            'startsOn' => ['nullable', 'date'],
            'endsOn' => ['nullable', 'date', 'after_or_equal:startsOn'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function fillFrom(TrainingProgram $program): void
    {
        $this->title = $program->title;
        $this->goal = $program->goal ?: '';
        $this->status = $program->status;
        $this->startsOn = $program->starts_on?->toDateString();
        $this->endsOn = $program->ends_on?->toDateString();
        $this->notes = $program->notes ?: '';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validate();

        return [
            'title' => $data['title'],
            'goal' => $data['goal'] ?: null,
            'status' => $data['status'],
            'starts_on' => $data['startsOn'],
            'ends_on' => $data['endsOn'],
            'notes' => $data['notes'] ?: null,
        ];
    }
}
