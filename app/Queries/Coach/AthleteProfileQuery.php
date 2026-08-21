<?php

namespace App\Queries\Coach;

use App\Models\CoachNote;
use App\Models\PersonalRecord;
use App\Models\ProgramAssignment;
use App\Models\ProgressEntry;
use App\Models\ProgressPhoto;
use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;

class AthleteProfileQuery
{
    public static function isAssignedTo(User $athlete, int $coachId): bool
    {
        return $athlete->isAthlete()
            && $athlete->athleteAssignments()
                ->where('coach_id', $coachId)
                ->where('status', 'active')
                ->exists();
    }

    public static function assignments(int $coachId, int $athleteId, string $search = '', string $status = 'all'): Builder
    {
        return ProgramAssignment::query()
            ->where('athlete_id', $athleteId)
            ->with(['program.coach', 'program.sessions', 'scheduledWorkouts.logs'])
            ->withCount(['scheduledWorkouts', 'workoutLogs'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search, fn (Builder $query) => $query->whereHas('program', fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('goal', 'like', "%{$search}%")))
            ->latest('starts_on');
    }

    public static function schedule(
        int $coachId,
        int $athleteId,
        string $search = '',
        string $status = 'all',
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return ScheduledWorkout::query()
            ->where('athlete_id', $athleteId)
            ->with(['coach', 'session.program', 'logs', 'assignment'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($from, fn (Builder $query) => $query->whereDate('scheduled_for', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('scheduled_for', '<=', $to))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereHas('session', fn (Builder $query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('focus', 'like', "%{$search}%"))
                ->orWhereHas('session.program', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))))
            ->latest('scheduled_for');
    }

    public static function workoutLogs(
        int $coachId,
        int $athleteId,
        string $search = '',
        string $status = 'all',
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return WorkoutLog::query()
            ->where('athlete_id', $athleteId)
            ->with(['session.program', 'setLogs', 'scheduledWorkout'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('notes', 'like', "%{$search}%")
                ->orWhereHas('session', fn (Builder $query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('focus', 'like', "%{$search}%"))))
            ->latest();
    }

    public static function progress(
        int $athleteId,
        string $search = '',
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return ProgressEntry::query()
            ->where('athlete_id', $athleteId)
            ->when($from, fn (Builder $query) => $query->whereDate('logged_on', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('logged_on', '<=', $to))
            ->when($search, fn (Builder $query) => $query->where('notes', 'like', "%{$search}%"))
            ->latest('logged_on');
    }

    public static function photos(
        int $athleteId,
        string $search = '',
        string $category = 'all',
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return ProgressPhoto::query()
            ->where('athlete_id', $athleteId)
            ->with('uploadedBy')
            ->when($category !== 'all', fn (Builder $query) => $query->where('category', $category))
            ->when($from, fn (Builder $query) => $query->whereDate('taken_on', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('taken_on', '<=', $to))
            ->when($search, fn (Builder $query) => $query->where('notes', 'like', "%{$search}%"))
            ->latest('taken_on');
    }

    public static function records(int $athleteId, string $search = '', string $recordType = 'all'): Builder
    {
        return PersonalRecord::query()
            ->where('athlete_id', $athleteId)
            ->when($recordType !== 'all', fn (Builder $query) => $query->where('record_type', $recordType))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('exercise_name', 'like', "%{$search}%")
                ->orWhere('unit', 'like', "%{$search}%")))
            ->latest('achieved_on');
    }

    public static function notes(int $coachId, int $athleteId, string $search = ''): Builder
    {
        return CoachNote::query()
            ->where('athlete_id', $athleteId)
            ->where(fn (Builder $query) => $query
                ->where('coach_id', $coachId)
                ->orWhere('visibility', 'organization'))
            ->with('coach')
            ->when($search, fn (Builder $query) => $query->where('body', 'like', "%{$search}%"))
            ->orderByDesc('is_pinned')
            ->latest();
    }
}
