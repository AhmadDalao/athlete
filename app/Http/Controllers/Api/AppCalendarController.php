<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsMobileAppPayloads;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppCalendarController extends Controller
{
    use BuildsMobileAppPayloads;
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingAppPresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $this->abortUnlessMobileUser($user);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $selectedDate = CarbonImmutable::parse($validated['date'] ?? now()->toDateString())->startOfDay();
        $monthStart = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m-d', "{$validated['month']}-01")->startOfMonth()
            : $selectedDate->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $calendarStart = $monthStart->startOfWeek(CarbonInterface::SUNDAY);
        $calendarEnd = $monthEnd->endOfWeek(CarbonInterface::SATURDAY);

        $programIds = $this->mobileProgramsQuery($user)->pluck('id');

        $monthSessions = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereBetween('scheduled_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->with(['program.coach', 'program.athlete', 'workoutLog'])
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->get();

        $sessionsByDate = $monthSessions->groupBy(fn (TrainingSession $session): string => $session->scheduled_date?->toDateString() ?? '');

        $days = [];
        $cursor = $calendarStart;

        while ($cursor->lte($calendarEnd)) {
            $dateKey = $cursor->toDateString();
            $daySessions = $sessionsByDate->get($dateKey, collect());

            $days[] = [
                'date' => $dateKey,
                'dayNumber' => $cursor->day,
                'weekday' => $cursor->format('D'),
                'isCurrentMonth' => $cursor->month === $monthStart->month,
                'isToday' => $cursor->isSameDay(now()),
                'isSelected' => $cursor->isSameDay($selectedDate),
                'sessionCount' => $daySessions->count(),
                'hasVideo' => $daySessions->contains(fn (TrainingSession $session): bool => filled($session->video_url)),
                'status' => $daySessions->isEmpty() ? 'rest' : 'workout',
            ];

            $cursor = $cursor->addDay();
        }

        $selectedDaySessions = $monthSessions
            ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->isSameDay($selectedDate) ?? false)
            ->map(fn (TrainingSession $session): array => $this->mobileSessionPayload($session, $presenter))
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'month' => $monthStart->format('Y-m'),
                'monthLabel' => $monthStart->format('F Y'),
                'selectedDate' => $selectedDate->toDateString(),
                'days' => $days,
                'selectedDaySessions' => $selectedDaySessions,
            ],
            'meta' => $this->metaPayload(),
        ]);
    }
}
