import { Badge } from '@/components/ui/badge';
import { type ExerciseRow } from '@/pages/dashboard-view/types';

export function formatCurrency(value: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDays(days: number | null) {
    if (days === null) {
        return 'No end date';
    }

    if (days === 0) {
        return 'Ends today';
    }

    if (days === 1) {
        return '1 day left';
    }

    return `${days} days left`;
}

export function formatReadiness(score: number | null) {
    if (score === null) {
        return 'No readiness data';
    }

    return `${Math.round(score)}/100`;
}

export function formatSleepHours(hours: number | null) {
    if (hours === null) {
        return 'No sleep data';
    }

    return `${hours.toFixed(1)}h`;
}

export function formatSleepDebt(hours: number | null) {
    if (hours === null) {
        return 'No sleep-need data';
    }

    if (hours <= 0) {
        return `${Math.abs(hours).toFixed(1)}h banked`;
    }

    return `${hours.toFixed(1)}h behind`;
}

export function formatSignedDelta(value: number | null) {
    if (value === null) {
        return 'No change data';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value.toFixed(1)}`;
}

export function formatPercentage(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${Math.round(value)}%`;
}

export function formatWeight(value: number | null) {
    if (value === null) {
        return 'No weigh-in';
    }

    return `${value.toFixed(1)} kg`;
}

export function formatCalories(value: number | null) {
    if (value === null) {
        return 'No calorie log';
    }

    return `${Math.round(value)} kcal`;
}

export function formatGrams(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${Math.round(value)} g`;
}

export function formatLiters(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${value.toFixed(1)} L`;
}

export function formatRestLabel(seconds: number | null, label: string | null) {
    if (label) {
        return label;
    }

    if (seconds === null) {
        return null;
    }

    if (seconds >= 60 && seconds % 60 === 0) {
        return `${seconds / 60} min`;
    }

    return `${seconds}s`;
}

export function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'cancelled', 'expired', 'disconnected', 'missed'].includes(status)) {
        return 'destructive';
    }

    if (['grace', 'attention', 'none', 'partial', 'draft'].includes(status)) {
        return 'secondary';
    }

    if (['trialing', 'active', 'connected', 'completed'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

export function badgeVariantForPriority(priority: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (priority === 'high') {
        return 'destructive';
    }

    if (priority === 'medium') {
        return 'secondary';
    }

    if (priority === 'stable') {
        return 'default';
    }

    return 'outline';
}

export function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

export function exerciseHeadline(exercise: ExerciseRow) {
    if (exercise.sets && exercise.reps) {
        return `${exercise.sets} x ${exercise.reps}`;
    }

    return exercise.prescription;
}

export function shortDayLabel(value: string | null) {
    if (!value) {
        return 'N/A';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

export function ExerciseChipCard({ exercise }: { exercise: ExerciseRow }) {
    const restLabel = formatRestLabel(exercise.rest_seconds, exercise.rest_label);

    return (
        <div className="rounded-xl border border-stone-200/75 bg-white/80 p-3">
            <p className="font-medium text-stone-950">{exercise.name}</p>
            {exerciseHeadline(exercise) && <p className="mt-2 text-sm text-stone-600">{exerciseHeadline(exercise)}</p>}
            <div className="mt-3 flex flex-wrap gap-2">
                {exercise.load && <Badge variant="outline">Load {exercise.load}</Badge>}
                {restLabel && <Badge variant="outline">Rest {restLabel}</Badge>}
                {exercise.target && <Badge variant="outline">{exercise.target}</Badge>}
            </div>
            {exercise.note && <p className="mt-3 text-sm leading-6 text-stone-600">{exercise.note}</p>}
        </div>
    );
}
