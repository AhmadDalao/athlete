<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'performance' => $this->performanceSummary($request->user()),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        if ($request->user()->isDirty('phone')) {
            $request->user()->phone_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * @return array<string, mixed>
     */
    private function performanceSummary(User $athlete): array
    {
        $latestLog = WorkoutLog::query()
            ->with(['session.program.coach', 'setLogs'])
            ->where('athlete_id', $athlete->id)
            ->latest('performed_at')
            ->latest('updated_at')
            ->first();

        $sevenDayLogs = $this->workoutLogsSince($athlete, now()->subDays(6)->startOfDay());
        $thirtyDayLogs = $this->workoutLogsSince($athlete, now()->subDays(29)->startOfDay());

        return [
            'latestSession' => $this->workoutLogStats($latestLog ? collect([$latestLog]) : collect(), 'Latest Session'),
            'sevenDays' => $this->workoutLogStats($sevenDayLogs, 'Last 7 Days'),
            'thirtyDays' => $this->workoutLogStats($thirtyDayLogs, 'Last 30 Days'),
            'prs' => $this->personalRecords($athlete),
        ];
    }

    /**
     * @return Collection<int, WorkoutLog>
     */
    private function workoutLogsSince(User $athlete, Carbon $start): Collection
    {
        return WorkoutLog::query()
            ->with(['session.program.coach', 'setLogs'])
            ->where('athlete_id', $athlete->id)
            ->where(function ($query) use ($start) {
                $query->where('performed_at', '>=', $start)
                    ->orWhereHas('setLogs', fn ($setQuery) => $setQuery->where('completed_at', '>=', $start));
            })
            ->orderByDesc('performed_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<string, mixed>
     */
    private function workoutLogStats(Collection $logs, string $label): array
    {
        $setLogs = $logs
            ->flatMap(fn (WorkoutLog $log) => $log->setLogs)
            ->filter(fn (WorkoutSetLog $setLog) => $setLog->completed_at !== null);

        $totalReps = $setLogs->sum(fn (WorkoutSetLog $setLog) => $this->numericValue($setLog->actual_reps) ?? 0);
        $tonnage = $setLogs->sum(function (WorkoutSetLog $setLog) {
            $reps = $this->numericValue($setLog->actual_reps);
            $load = $this->numericValue($setLog->actual_load);

            return $reps !== null && $load !== null ? $reps * $load : 0;
        });
        $duration = $logs->sum(fn (WorkoutLog $log) => $log->duration_minutes ?? 0);
        $latestLog = $logs->first();

        return [
            'label' => $label,
            'sessionTitle' => $latestLog?->session?->title,
            'programTitle' => $latestLog?->session?->program?->title,
            'coachName' => $latestLog?->session?->program?->coach?->name,
            'performedAt' => $latestLog?->performed_at?->toDateTimeString(),
            'sessionsLogged' => $logs->count(),
            'setsCompleted' => $setLogs->count(),
            'totalReps' => (int) $totalReps,
            'tonnage' => round($tonnage, 1),
            'durationMinutes' => (int) $duration,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function personalRecords(User $athlete): array
    {
        return WorkoutSetLog::query()
            ->where('athlete_id', $athlete->id)
            ->whereNotNull('completed_at')
            ->whereNotNull('actual_load')
            ->latest('completed_at')
            ->get()
            ->map(function (WorkoutSetLog $setLog) {
                return [
                    'exerciseName' => $setLog->exercise_name,
                    'load' => $this->numericValue($setLog->actual_load),
                    'reps' => $this->numericValue($setLog->actual_reps),
                    'completedAt' => $setLog->completed_at?->toDateString(),
                ];
            })
            ->filter(fn (array $row) => $row['load'] !== null)
            ->groupBy('exerciseName')
            ->map(fn (Collection $rows) => $rows->sortByDesc('load')->first())
            ->sortByDesc('load')
            ->values()
            ->take(5)
            ->all();
    }

    private function numericValue(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        preg_match('/-?\d+(?:\.\d+)?/', $value, $matches);

        return isset($matches[0]) ? (float) $matches[0] : null;
    }
}
