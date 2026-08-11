@php
    $features = [
        ['fa-user-group', 'Roster control', 'Invite athletes, assign multiple coaches, and keep every relationship scoped to the right organization.'],
        ['fa-layer-group', 'Program builder', 'Create reusable phases and sessions with ordered exercises, targets, rest, notes, images, and video.'],
        ['fa-calendar-check', 'Smart scheduling', 'Turn relative program sessions into dated athlete workouts and reschedule without changing the source template.'],
        ['fa-list-check', 'Workout execution', 'Capture actual sets, reps, load, RPE, notes, timers, and completed, partial, missed, or skipped states.'],
        ['fa-chart-line', 'Progress evidence', 'Review body metrics, nutrition, recovery, photos, strength records, volume, and adherence over time.'],
        ['fa-comments', 'Connected feedback', 'Keep coach-athlete conversations, read state, attachments, and decisions next to the training context.'],
    ];
@endphp
<div class="row g-3">
    @foreach($features as [$icon, $title, $copy])
        <div class="col-md-6 col-xl-4">
            <article class="tl-feature-card">
                <span class="tl-feature-icon"><i class="fa-solid {{ $icon }}"></i></span>
                <h3>{{ $title }}</h3>
                <p>{{ $copy }}</p>
            </article>
        </div>
    @endforeach
</div>
