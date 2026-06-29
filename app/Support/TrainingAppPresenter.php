<?php

namespace App\Support;

use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\WorkoutLog;

class TrainingAppPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function program(TrainingProgram $program, bool $includeSessions = false): array
    {
        $sessions = $program->relationLoaded('sessions') ? $program->sessions : collect();
        $nextSession = $sessions
            ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->gte(now()->startOfDay()) ?? false)
            ->sortBy(fn (TrainingSession $session): int => $session->scheduled_date?->timestamp ?? 0)
            ->first();

        $payload = [
            'id' => $program->id,
            'title' => $program->title,
            'goal' => $program->goal,
            'status' => $program->status->value,
            'startDate' => $program->start_date?->toDateString(),
            'endDate' => $program->end_date?->toDateString(),
            'notes' => $program->notes,
            'coach' => [
                'id' => $program->coach->id,
                'name' => $program->coach->name,
                'email' => $program->coach->email,
            ],
            'athlete' => [
                'id' => $program->athlete->id,
                'name' => $program->athlete->name,
                'email' => $program->athlete->email,
            ],
            'sessionCount' => $sessions->count(),
            'completedSessionCount' => $sessions->filter(fn (TrainingSession $session): bool => $this->workoutStatus($session) === 'completed')->count(),
            'pendingSessionCount' => $sessions->filter(fn (TrainingSession $session): bool => $this->workoutStatus($session) === null && ($session->scheduled_date?->lte(now()->endOfDay()) ?? false))->count(),
            'nextSessionDate' => $nextSession?->scheduled_date?->toDateString(),
        ];

        if ($includeSessions) {
            $payload['sessions'] = $sessions
                ->map(fn (TrainingSession $session): array => $this->session($session))
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function session(TrainingSession $session): array
    {
        $exercises = $this->exercises($session->exercises ?? []);
        $mediaItems = $this->mediaItems($session);

        return [
            'id' => $session->id,
            'title' => $session->title,
            'scheduledDate' => $session->scheduled_date?->toDateString(),
            'focus' => $session->focus,
            'instructions' => $session->instructions,
            'videoUrl' => $session->video_url,
            'mediaItems' => $mediaItems,
            'mediaCount' => count($mediaItems),
            'exerciseCount' => count($exercises),
            'exercisePreview' => collect($exercises)
                ->take(3)
                ->map(fn (array $exercise): string => $this->exerciseSummary($exercise))
                ->values()
                ->all(),
            'exercises' => $exercises,
            'workoutLog' => $session->workoutLog ? $this->workoutLog($session->workoutLog) : null,
            'completionStatus' => $this->workoutStatus($session) ?? 'scheduled',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     * @return list<array<string, mixed>>
     */
    public function exercises(array $exercises): array
    {
        return collect($exercises)
            ->filter(fn ($exercise): bool => is_array($exercise))
            ->map(fn (array $exercise): array => [
                'name' => (string) ($exercise['name'] ?? 'Exercise'),
                'prescription' => $exercise['prescription'] ?? null,
                'sets' => isset($exercise['sets']) ? (int) $exercise['sets'] : null,
                'reps' => $exercise['reps'] ?? null,
                'load' => $exercise['load'] ?? null,
                'restSeconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
                'restLabel' => $exercise['rest_label'] ?? null,
                'target' => $exercise['target'] ?? null,
                'note' => $exercise['note'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{type:string,url:string,title:string|null,isPrimary:bool}>
     */
    public function mediaItems(TrainingSession $session): array
    {
        $items = collect();

        if ($session->video_url) {
            $items->push([
                'type' => 'video',
                'url' => $session->video_url,
                'title' => 'Workout video',
                'isPrimary' => true,
            ]);
        }

        return $items
            ->merge(collect($session->media_items ?? [])
                ->filter(fn ($item): bool => is_array($item) && ($item['type'] ?? null) === 'image' && filled($item['url'] ?? null))
                ->map(fn (array $item): array => [
                    'type' => 'image',
                    'url' => (string) $item['url'],
                    'title' => isset($item['title']) && filled($item['title']) ? (string) $item['title'] : null,
                    'isPrimary' => false,
                ]))
            ->values()
            ->all();
    }

    private function exerciseSummary(array $exercise): string
    {
        $pieces = [
            $exercise['name'],
            $exercise['sets'] && $exercise['reps'] ? "{$exercise['sets']} x {$exercise['reps']}" : $exercise['prescription'],
            $exercise['load'] ? "Load {$exercise['load']}" : null,
        ];

        return collect($pieces)->filter()->join(' - ');
    }

    /**
     * @return array<string, mixed>
     */
    private function workoutLog(WorkoutLog $workoutLog): array
    {
        return [
            'id' => $workoutLog->id,
            'completionStatus' => $workoutLog->completion_status->value,
            'performedAt' => $workoutLog->performed_at?->toDateString(),
            'durationMinutes' => $workoutLog->duration_minutes,
            'exertionRating' => $workoutLog->exertion_rating,
            'notes' => $workoutLog->notes,
        ];
    }

    private function workoutStatus(TrainingSession $session): ?string
    {
        return $session->workoutLog?->completion_status?->value;
    }
}
