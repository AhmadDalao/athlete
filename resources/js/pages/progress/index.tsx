import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading, TrendBars } from '@/components/athlete-page-primitives';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    WorkspaceHero,
    WorkspaceMetricCard,
    WorkspacePanel,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
    WorkspaceTablePageSize,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Activity, ArrowLeft, ArrowRight, Beef, ClipboardPen, Droplets, Dumbbell, Flame, LineChart, Scale, Shield, Users, Watch } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Progress',
        href: '/progress',
    },
];

interface CheckInRow {
    id: number;
    loggedDate: string | null;
    weightKg: number | null;
    bodyFatPercentage: number | null;
    waistCm: number | null;
    caloriesConsumed: number | null;
    proteinGrams: number | null;
    carbsGrams: number | null;
    fatGrams: number | null;
    waterLiters: number | null;
    mealsLoggedCount: number | null;
    energyScore: number | null;
    sorenessScore: number | null;
    stressScore: number | null;
    sleepQualityScore: number | null;
    notes: string | null;
}

interface ProgressOverview {
    daysTracked: number;
    checkInsThisWeek: number;
    lastLoggedDate: string | null;
    latestWeightKg: number | null;
    weightDeltaKg: number | null;
    averageCaloriesConsumed: number | null;
    averageProteinGrams: number | null;
    averageCarbsGrams: number | null;
    averageFatGrams: number | null;
    averageWaterLiters: number | null;
    averageEnergyScore: number | null;
    averageSorenessScore: number | null;
    averageStressScore: number | null;
    averageSleepQualityScore: number | null;
    averageBodyFatPercentage: number | null;
    averageWaistCm: number | null;
    scheduledSessions: number;
    completedSessions: number;
    completionRate: number | null;
}

interface ProgressReport {
    latest: CheckInRow | null;
    overview: ProgressOverview;
    timeline: CheckInRow[];
    alerts: string[];
}

interface AthleteProfile {
    metrics: {
        latestWeightKg: number | null;
        weightDeltaKg: number | null;
        averageCaloriesConsumed: number | null;
        averageProteinGrams: number | null;
        averageWaterLiters: number | null;
        checkInsThisWeek: number;
        completionRate: number | null;
        scheduledSessions: number;
        completedSessions: number;
    };
    progressReport: ProgressReport;
    recentCheckIns: CheckInRow[];
    latestCheckIn: CheckInRow | null;
    defaults: {
        loggedDate: string;
    };
    coaches: Array<{
        name: string;
        email: string;
        goal: string | null;
    }>;
    currentProgram: {
        title: string;
        goal: string | null;
        coachName: string;
    } | null;
    latestSnapshot: {
        metricDate: string | null;
        readinessScore: number | null;
        sleepHours: number | null;
        trainingLoad: number | null;
    } | null;
}

interface AthleteRow {
    id: number;
    name: string;
    email: string;
    primaryGoal: string | null;
    coachName: string | null;
    latestCheckIn: CheckInRow | null;
    latestSnapshot: {
        metricDate: string | null;
        readinessScore: number | null;
        sleepHours: number | null;
        trainingLoad: number | null;
    } | null;
    progressOverview: ProgressOverview;
    alerts: string[];
}

interface AthletePaginator {
    data: AthleteRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
    per_page?: number | string;
}

interface ProgressPageProps {
    viewerRole: string | null;
    scopeLabel: string;
    canManageOwnCheckIns: boolean;
    summary: {
        visibleAthletes: number;
        checkInsThisWeek: number;
        athletesMissingRecentCheckIn: number;
        connectedDevices: number;
    };
    athleteProfile: AthleteProfile | null;
    athletes: AthletePaginator | null;
}

interface CheckInFormData {
    logged_date: string;
    weight_kg: string;
    body_fat_percentage: string;
    waist_cm: string;
    calories_consumed: string;
    protein_grams: string;
    carbs_grams: string;
    fat_grams: string;
    water_liters: string;
    meals_logged_count: string;
    energy_score: string;
    soreness_score: string;
    stress_score: string;
    sleep_quality_score: string;
    notes: string;
}

function formatWeight(value: number | null) {
    return value === null ? 'N/A' : `${value.toFixed(1)} kg`;
}

function formatDelta(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    if (value === 0) {
        return '0.0 kg';
    }

    return `${value > 0 ? '+' : ''}${value.toFixed(1)} kg`;
}

function formatCalories(value: number | null) {
    return value === null ? 'N/A' : `${Math.round(value)} kcal`;
}

function formatGrams(value: number | null) {
    return value === null ? 'N/A' : `${Math.round(value)} g`;
}

function formatLiters(value: number | null) {
    return value === null ? 'N/A' : `${value.toFixed(1)} L`;
}

function formatScore(value: number | null) {
    return value === null ? 'N/A' : `${value}/10`;
}

function formatPercent(value: number | null) {
    return value === null ? 'N/A' : `${value.toFixed(1)}%`;
}

function shortDate(value: string | null) {
    if (!value) {
        return 'Unknown date';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function numericInput(value: string | number | null | undefined) {
    return value === null || value === undefined ? '' : String(value);
}

function CheckInDialog({
    label,
    checkIn,
    defaults,
}: {
    label: string;
    checkIn?: CheckInRow | null;
    defaults: {
        loggedDate: string;
    };
}) {
    const [open, setOpen] = useState(false);
    const isEditing = Boolean(checkIn);
    const { data, setData, post, patch, processing, errors } = useForm<CheckInFormData>({
        logged_date: checkIn?.loggedDate ?? defaults.loggedDate,
        weight_kg: numericInput(checkIn?.weightKg),
        body_fat_percentage: numericInput(checkIn?.bodyFatPercentage),
        waist_cm: numericInput(checkIn?.waistCm),
        calories_consumed: numericInput(checkIn?.caloriesConsumed),
        protein_grams: numericInput(checkIn?.proteinGrams),
        carbs_grams: numericInput(checkIn?.carbsGrams),
        fat_grams: numericInput(checkIn?.fatGrams),
        water_liters: numericInput(checkIn?.waterLiters),
        meals_logged_count: numericInput(checkIn?.mealsLoggedCount),
        energy_score: numericInput(checkIn?.energyScore),
        soreness_score: numericInput(checkIn?.sorenessScore),
        stress_score: numericInput(checkIn?.stressScore),
        sleep_quality_score: numericInput(checkIn?.sleepQualityScore),
        notes: checkIn?.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (isEditing && checkIn) {
            patch(route('progress.check-ins.update', checkIn.id), options);

            return;
        }

        post(route('progress.check-ins.store'), options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant={isEditing ? 'outline' : 'default'} size="sm">
                    <ClipboardPen className="mr-2 size-4" />
                    {label}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{isEditing ? 'Update athlete check-in' : 'Log athlete check-in'}</DialogTitle>
                    <DialogDescription>
                        Food, weight, hydration, soreness, and energy belong in the product too. Watches do not know what you ate.
                    </DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="checkin-date">Date</Label>
                            <Input
                                id="checkin-date"
                                type="date"
                                value={data.logged_date}
                                onChange={(event) => setData('logged_date', event.target.value)}
                            />
                            <InputError message={errors.logged_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-weight">Weight (kg)</Label>
                            <Input id="checkin-weight" value={data.weight_kg} onChange={(event) => setData('weight_kg', event.target.value)} />
                            <InputError message={errors.weight_kg} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-body-fat">Body fat %</Label>
                            <Input
                                id="checkin-body-fat"
                                value={data.body_fat_percentage}
                                onChange={(event) => setData('body_fat_percentage', event.target.value)}
                            />
                            <InputError message={errors.body_fat_percentage} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-waist">Waist (cm)</Label>
                            <Input id="checkin-waist" value={data.waist_cm} onChange={(event) => setData('waist_cm', event.target.value)} />
                            <InputError message={errors.waist_cm} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-5">
                        <div className="grid gap-2">
                            <Label htmlFor="checkin-calories">Calories</Label>
                            <Input
                                id="checkin-calories"
                                value={data.calories_consumed}
                                onChange={(event) => setData('calories_consumed', event.target.value)}
                            />
                            <InputError message={errors.calories_consumed} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-protein">Protein (g)</Label>
                            <Input
                                id="checkin-protein"
                                value={data.protein_grams}
                                onChange={(event) => setData('protein_grams', event.target.value)}
                            />
                            <InputError message={errors.protein_grams} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-carbs">Carbs (g)</Label>
                            <Input id="checkin-carbs" value={data.carbs_grams} onChange={(event) => setData('carbs_grams', event.target.value)} />
                            <InputError message={errors.carbs_grams} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-fat">Fat (g)</Label>
                            <Input id="checkin-fat" value={data.fat_grams} onChange={(event) => setData('fat_grams', event.target.value)} />
                            <InputError message={errors.fat_grams} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-water">Water (L)</Label>
                            <Input id="checkin-water" value={data.water_liters} onChange={(event) => setData('water_liters', event.target.value)} />
                            <InputError message={errors.water_liters} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-5">
                        <div className="grid gap-2">
                            <Label htmlFor="checkin-meals">Meals logged</Label>
                            <Input
                                id="checkin-meals"
                                value={data.meals_logged_count}
                                onChange={(event) => setData('meals_logged_count', event.target.value)}
                            />
                            <InputError message={errors.meals_logged_count} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-energy">Energy /10</Label>
                            <Input id="checkin-energy" value={data.energy_score} onChange={(event) => setData('energy_score', event.target.value)} />
                            <InputError message={errors.energy_score} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-soreness">Soreness /10</Label>
                            <Input
                                id="checkin-soreness"
                                value={data.soreness_score}
                                onChange={(event) => setData('soreness_score', event.target.value)}
                            />
                            <InputError message={errors.soreness_score} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-stress">Stress /10</Label>
                            <Input id="checkin-stress" value={data.stress_score} onChange={(event) => setData('stress_score', event.target.value)} />
                            <InputError message={errors.stress_score} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkin-sleep">Sleep quality /10</Label>
                            <Input
                                id="checkin-sleep"
                                value={data.sleep_quality_score}
                                onChange={(event) => setData('sleep_quality_score', event.target.value)}
                            />
                            <InputError message={errors.sleep_quality_score} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="checkin-notes">Notes</Label>
                        <Textarea
                            id="checkin-notes"
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            placeholder="What mattered today? Appetite, travel, soreness, missed meals, clean training session, whatever is actually useful."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Save check-in
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function SummaryMetric({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: typeof Users }) {
    return <WorkspaceMetricCard title={title} value={value} note={note} icon={Icon} />;
}

function AthleteProgressView({ athleteProfile, canManageOwnCheckIns }: { athleteProfile: AthleteProfile; canManageOwnCheckIns: boolean }) {
    const weightPoints = athleteProfile.progressReport.timeline.slice(-7).map((entry) => ({
        label: shortDate(entry.loggedDate),
        value: entry.weightKg,
        note: entry.weightKg === null ? 'No weigh-in' : `${entry.weightKg.toFixed(1)} kg`,
    }));
    const proteinPoints = athleteProfile.progressReport.timeline.slice(-7).map((entry) => ({
        label: shortDate(entry.loggedDate),
        value: entry.proteinGrams,
        note: entry.proteinGrams === null ? 'No protein log' : `${entry.proteinGrams} g`,
    }));

    return (
        <div className="space-y-6">
            <AthleteHero
                eyebrow="Athlete progress board"
                title="Food, body metrics, and training context in one place."
                description="Wearables tell part of the story. This is the part they miss: bodyweight, nutrition, hydration, soreness, stress, and whether the week is actually supporting the work."
                badges={[
                    `${athleteProfile.progressReport.overview.daysTracked} tracked day(s)`,
                    `${athleteProfile.metrics.completedSessions}/${athleteProfile.metrics.scheduledSessions} sessions done`,
                ]}
                actions={canManageOwnCheckIns ? <CheckInDialog label="Log today" defaults={athleteProfile.defaults} /> : null}
            >
                <div className="rounded-[1.5rem] border border-stone-200/80 bg-white/90 p-5 shadow-sm">
                    <p className="text-sm font-medium text-stone-500">Current alignment</p>
                    <div className="mt-4 space-y-4">
                        <div>
                            <p className="text-3xl font-semibold tracking-tight text-stone-950">
                                {formatWeight(athleteProfile.metrics.latestWeightKg)}
                            </p>
                            <p className="mt-1 text-sm text-stone-600">Latest logged bodyweight.</p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 p-4">
                                <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Training completion</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">{formatPercent(athleteProfile.metrics.completionRate)}</p>
                                <p className="mt-1 text-sm text-stone-600">
                                    {athleteProfile.metrics.completedSessions} completed of {athleteProfile.metrics.scheduledSessions} scheduled.
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 p-4">
                                <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Latest wearable sync</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">{athleteProfile.latestSnapshot?.readinessScore ?? 'N/A'}</p>
                                <p className="mt-1 text-sm text-stone-600">
                                    {athleteProfile.latestSnapshot
                                        ? `${shortDate(athleteProfile.latestSnapshot.metricDate)} · ${athleteProfile.latestSnapshot.sleepHours ?? 'N/A'}h sleep`
                                        : 'No synced recovery snapshot yet.'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </AthleteHero>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <AthleteMetricCard
                    title="Current weight"
                    value={formatWeight(athleteProfile.metrics.latestWeightKg)}
                    note={`Window delta: ${formatDelta(athleteProfile.metrics.weightDeltaKg)}.`}
                    icon={Scale}
                />
                <AthleteMetricCard
                    title="Average calories"
                    value={formatCalories(athleteProfile.metrics.averageCaloriesConsumed)}
                    note="Average daily intake across the current tracking window."
                    icon={Flame}
                    tone="amber"
                />
                <AthleteMetricCard
                    title="Average protein"
                    value={formatGrams(athleteProfile.metrics.averageProteinGrams)}
                    note="Simple recovery anchor. If this is weak, the week gets shakier fast."
                    icon={Beef}
                />
                <AthleteMetricCard
                    title="Hydration"
                    value={formatLiters(athleteProfile.metrics.averageWaterLiters)}
                    note="Average daily water intake."
                    icon={Droplets}
                    tone="teal"
                />
                <AthleteMetricCard
                    title="Check-ins this week"
                    value={athleteProfile.metrics.checkInsThisWeek.toString()}
                    note="Manual logs posted since the week started."
                    icon={ClipboardPen}
                    tone="stone"
                />
                <AthleteMetricCard
                    title="Training completion"
                    value={formatPercent(athleteProfile.metrics.completionRate)}
                    note={`${athleteProfile.metrics.completedSessions} completed session(s) out of ${athleteProfile.metrics.scheduledSessions}.`}
                    icon={Dumbbell}
                    tone="stone"
                />
            </div>

            <section className="space-y-4">
                <AthleteSectionHeading
                    eyebrow="Trend view"
                    title="Body and fuel trend"
                    description="This is the part that shows whether the athlete is actually supporting the training, not just surviving it."
                />
                <div className="grid gap-4 xl:grid-cols-2">
                    <TrendBars
                        title="Weight trend"
                        description="Short-term bodyweight trend across the latest seven logged entries."
                        points={weightPoints}
                        formatter={(value) => (value === null ? 'N/A' : value.toFixed(1))}
                        tone="amber"
                    />
                    <TrendBars
                        title="Protein trend"
                        description="Daily protein intake is the easiest place to spot whether recovery support is drifting."
                        points={proteinPoints}
                        formatter={(value) => (value === null ? 'N/A' : `${Math.round(value)}g`)}
                        tone="teal"
                    />
                </div>
            </section>

            <div className="grid gap-4 xl:grid-cols-3">
                <AthletePanel
                    title="Latest check-in"
                    description="The freshest manual progress entry, which often explains the training week better than a readiness number does."
                    className="xl:col-span-2"
                    contentClassName="grid gap-4 md:grid-cols-3"
                >
                    <div className="rounded-2xl border border-stone-200/80 bg-stone-50/70 p-4">
                        <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Fuel</p>
                        <p className="mt-2 font-medium text-stone-950">
                            {formatCalories(athleteProfile.latestCheckIn?.caloriesConsumed ?? null)} ·{' '}
                            {formatGrams(athleteProfile.latestCheckIn?.proteinGrams ?? null)}
                        </p>
                        <p className="mt-1 text-sm text-stone-600">
                            {formatGrams(athleteProfile.latestCheckIn?.carbsGrams ?? null)} carbs ·{' '}
                            {formatGrams(athleteProfile.latestCheckIn?.fatGrams ?? null)} fat
                        </p>
                    </div>
                    <div className="rounded-2xl border border-stone-200/80 bg-stone-50/70 p-4">
                        <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Body metrics</p>
                        <p className="mt-2 font-medium text-stone-950">
                            {formatWeight(athleteProfile.latestCheckIn?.weightKg ?? null)} ·{' '}
                            {formatPercent(athleteProfile.latestCheckIn?.bodyFatPercentage ?? null)}
                        </p>
                        <p className="mt-1 text-sm text-stone-600">
                            Waist{' '}
                            {athleteProfile.latestCheckIn?.waistCm === null || athleteProfile.latestCheckIn?.waistCm === undefined
                                ? 'N/A'
                                : `${athleteProfile.latestCheckIn.waistCm.toFixed(1)} cm`}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-stone-200/80 bg-stone-50/70 p-4">
                        <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Subjective state</p>
                        <p className="mt-2 font-medium text-stone-950">
                            Energy {formatScore(athleteProfile.latestCheckIn?.energyScore ?? null)} · Soreness{' '}
                            {formatScore(athleteProfile.latestCheckIn?.sorenessScore ?? null)}
                        </p>
                        <p className="mt-1 text-sm text-stone-600">
                            Stress {formatScore(athleteProfile.latestCheckIn?.stressScore ?? null)} · Sleep{' '}
                            {formatScore(athleteProfile.latestCheckIn?.sleepQualityScore ?? null)}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-stone-200/80 bg-stone-50/70 p-4 md:col-span-3">
                        <p className="text-xs tracking-[0.18em] text-stone-500 uppercase">Coach-facing note</p>
                        <p className="mt-2 text-sm leading-6 text-stone-700">{athleteProfile.latestCheckIn?.notes ?? 'No note logged yet.'}</p>
                    </div>
                </AthletePanel>

                <AthletePanel title="Alerts" description="The app should call out obvious issues instead of making coaches hunt for them.">
                    <div className="space-y-3">
                        {athleteProfile.progressReport.alerts.length === 0 ? (
                            <p className="text-sm leading-6 text-stone-600">No obvious progress-side problems are firing right now.</p>
                        ) : (
                            athleteProfile.progressReport.alerts.map((alert) => (
                                <div
                                    key={alert}
                                    className="rounded-2xl border border-stone-200/80 bg-stone-50/80 p-4 text-sm leading-6 text-stone-700"
                                >
                                    {alert}
                                </div>
                            ))
                        )}
                    </div>
                </AthletePanel>
            </div>

            <AthletePanel
                title="Recent check-ins"
                description="Recent history is often more useful than a single perfect-looking day."
                contentClassName="space-y-3"
            >
                <WorkspaceTable minWidth="min-w-[980px]">
                    <WorkspaceTableHeader
                        labels={['Date', 'Weight', 'Calories', 'Protein', 'Water', 'Energy', 'Soreness', 'Sleep', 'Notes', 'Action']}
                    />
                    {athleteProfile.recentCheckIns.length === 0 ? (
                        <WorkspaceTableEmpty message="No check-ins logged yet." colSpan={10} />
                    ) : (
                        <tbody className="divide-y divide-stone-100">
                            {athleteProfile.recentCheckIns.map((checkIn) => (
                                <tr key={checkIn.id} className="align-top transition-colors hover:bg-stone-50/80">
                                    <td className="px-5 py-4 font-medium text-stone-950">{shortDate(checkIn.loggedDate)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatWeight(checkIn.weightKg)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatCalories(checkIn.caloriesConsumed)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatGrams(checkIn.proteinGrams)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatLiters(checkIn.waterLiters)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatScore(checkIn.energyScore)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatScore(checkIn.sorenessScore)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{formatScore(checkIn.sleepQualityScore)}</td>
                                    <td className="px-5 py-4">
                                        <p className="line-clamp-2 max-w-[18rem] text-sm leading-6 text-stone-700">
                                            {checkIn.notes ?? 'No notes.'}
                                        </p>
                                    </td>
                                    <td className="px-5 py-4">
                                        {canManageOwnCheckIns && <CheckInDialog label="Edit" checkIn={checkIn} defaults={athleteProfile.defaults} />}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    )}
                </WorkspaceTable>
            </AthletePanel>
        </div>
    );
}

function CoachAdminProgressView({
    viewerRole,
    summary,
    athletes,
}: {
    viewerRole: string | null;
    summary: ProgressPageProps['summary'];
    athletes: AthletePaginator;
}) {
    const isAdmin = viewerRole === 'admin';
    const perPage = String(athletes.per_page ?? '10');
    const updatePerPage = (value: string) => {
        router.get(route('progress.index'), { per_page: value }, { only: ['summary', 'athletes'], preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <div className="space-y-6">
            <WorkspaceHero
                eyebrow={isAdmin ? 'Admin progress control' : 'Coach progress control'}
                title="Progress workspace"
                description="Nutrition, weight, soreness, hydration, and training support across the visible athlete group. This is where you catch drift before it becomes an excuse."
                badges={[`${summary.visibleAthletes} visible athletes`, `${summary.checkInsThisWeek} check-ins this week`]}
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <SummaryMetric
                    title="Visible athletes"
                    value={summary.visibleAthletes.toString()}
                    note="Athletes whose progress layer you can currently inspect."
                    icon={Users}
                />
                <SummaryMetric
                    title="Check-ins this week"
                    value={summary.checkInsThisWeek.toString()}
                    note="Manual logs created since the week started."
                    icon={ClipboardPen}
                />
                <SummaryMetric
                    title="Missing recent check-in"
                    value={summary.athletesMissingRecentCheckIn.toString()}
                    note="Athletes with no fresh log in the last 72 hours."
                    icon={Activity}
                />
                <SummaryMetric
                    title="Connected devices"
                    value={summary.connectedDevices.toString()}
                    note="Recovery data still matters. This just adds the manual layer it was missing."
                    icon={Watch}
                />
            </div>

            <WorkspacePanel
                title="Athlete progress queue"
                description="Table-first tracking for fueling, body metrics, subjective state, compliance, wearable context, and alerts."
                contentClassName="space-y-4"
            >
                    <div className="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-stone-50/40 p-4 lg:flex-row lg:items-center lg:justify-between">
                        <WorkspaceTablePageSize value={perPage} onChange={updatePerPage} />
                        <p className="text-sm text-stone-500">Showing {athletes.data.length} of {athletes.total} matching athletes.</p>
                    </div>
                    <WorkspaceTable minWidth="min-w-[1280px]">
                        <WorkspaceTableHeader
                            labels={['Athlete', 'Coach', 'Latest check-in', 'Weight', 'Fuel', 'State', 'Compliance', 'Wearable', 'Alerts', 'Actions']}
                        />
                        {athletes.data.length === 0 ? (
                            <WorkspaceTableEmpty message="No athlete progress data is visible yet." colSpan={10} />
                        ) : (
                            <tbody className="divide-y divide-stone-100">
                                {athletes.data.map((athlete) => (
                                    <tr key={athlete.id} className="align-top transition-colors hover:bg-stone-50/80">
                                        <td className="px-5 py-4">
                                            <p className="font-semibold text-stone-950">{athlete.name}</p>
                                            <p className="mt-1 text-xs text-stone-500">{athlete.email}</p>
                                            <p className="mt-2 line-clamp-2 max-w-[16rem] text-xs text-stone-600">
                                                {athlete.primaryGoal ?? 'No goal saved.'}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 text-sm text-stone-700">{athlete.coachName ?? 'No coach label'}</td>
                                        <td className="px-5 py-4">
                                            <p className="font-medium text-stone-950">
                                                {athlete.latestCheckIn ? shortDate(athlete.latestCheckIn.loggedDate) : 'No check-in'}
                                            </p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Water {formatLiters(athlete.latestCheckIn?.waterLiters ?? null)}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p className="font-medium text-stone-950">{formatWeight(athlete.progressOverview.latestWeightKg)}</p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Delta {formatDelta(athlete.progressOverview.weightDeltaKg)}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p className="font-medium text-stone-950">
                                                {formatCalories(athlete.progressOverview.averageCaloriesConsumed)}
                                            </p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Protein {formatGrams(athlete.progressOverview.averageProteinGrams)}
                                            </p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Carbs {formatGrams(athlete.progressOverview.averageCarbsGrams)} · Fat{' '}
                                                {formatGrams(athlete.progressOverview.averageFatGrams)}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p className="text-sm text-stone-700">
                                                Energy {formatScore(athlete.progressOverview.averageEnergyScore)}
                                            </p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Soreness {formatScore(athlete.progressOverview.averageSorenessScore)} · Stress{' '}
                                                {formatScore(athlete.progressOverview.averageStressScore)}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p className="font-medium text-stone-950">{formatPercent(athlete.progressOverview.completionRate)}</p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                {athlete.progressOverview.completedSessions}/{athlete.progressOverview.scheduledSessions} sessions
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p className="font-medium text-stone-950">
                                                Readiness {athlete.latestSnapshot?.readinessScore ?? 'N/A'}
                                            </p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Sleep {athlete.latestSnapshot?.sleepHours ?? 'N/A'} · Load{' '}
                                                {athlete.latestSnapshot?.trainingLoad ?? 'N/A'}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {athlete.alerts.length === 0 ? (
                                                <span className="text-xs text-stone-500">No alerts</span>
                                            ) : (
                                                <div className="flex max-w-[14rem] flex-wrap gap-1.5">
                                                    {athlete.alerts.map((alert) => (
                                                        <Badge key={alert} variant="secondary">
                                                            {alert}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-col gap-2">
                                                <Button asChild variant="outline" size="sm">
                                                    <Link href="/training">
                                                        <Dumbbell className="mr-2 size-4" />
                                                        Training
                                                    </Link>
                                                </Button>
                                                <Button asChild variant="outline" size="sm">
                                                    <Link href="/roster">
                                                        <Users className="mr-2 size-4" />
                                                        Roster
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </WorkspaceTable>

                    {athletes.last_page > 1 && (
                        <div className="border-sidebar-border/70 flex items-center justify-between border-t pt-4">
                            <p className="text-muted-foreground text-sm">
                                Page {athletes.current_page} of {athletes.last_page}
                            </p>

                            <div className="flex gap-2">
                                <Button asChild variant="outline" size="sm" disabled={!athletes.prev_page_url}>
                                    <Link href={athletes.prev_page_url ?? route('progress.index')} preserveScroll>
                                        <ArrowLeft className="mr-2 size-4" />
                                        Previous
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" size="sm" disabled={!athletes.next_page_url}>
                                    <Link href={athletes.next_page_url ?? route('progress.index')} preserveScroll>
                                        Next
                                        <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    )}
            </WorkspacePanel>

            <WorkspacePanel
                title="Progress operating notes"
                description="Reference detail stays available, but it lives in one table instead of three cards."
            >
                <WorkspaceTable minWidth="min-w-[980px]">
                    <WorkspaceTableHeader labels={['Area', 'Why it matters', 'Open next']} />
                    <tbody className="divide-y divide-stone-100">
                        <tr className="align-top hover:bg-stone-50/80">
                            <td className="px-5 py-4 font-semibold text-stone-950">Nutrition and body context</td>
                            <td className="px-5 py-4 text-sm leading-6 text-stone-600">
                                Food intake, bodyweight drift, and subjective scores explain training weeks faster than pretending every problem lives inside HRV.
                            </td>
                            <td className="px-5 py-4">
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/training">
                                        Training workspace
                                        <Dumbbell className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr className="align-top hover:bg-stone-50/80">
                            <td className="px-5 py-4 font-semibold text-stone-950">Roster follow-up</td>
                            <td className="px-5 py-4 text-sm leading-6 text-stone-600">
                                Missing logs are a signal. Either compliance is poor, the athlete needs a reminder, or the workflow is annoying.
                            </td>
                            <td className="px-5 py-4">
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/roster">
                                        Roster workspace
                                        <Users className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr className="align-top hover:bg-stone-50/80">
                            <td className="px-5 py-4 font-semibold text-stone-950">Wearable context</td>
                            <td className="px-5 py-4 text-sm leading-6 text-stone-600">
                                Readiness still matters, but it becomes useful when it sits next to food, soreness, stress, and completed work.
                            </td>
                            <td className="px-5 py-4">
                                <Button asChild variant="outline" size="sm">
                                    <Link href={isAdmin ? '/admin/control-center' : '/wearables'}>
                                        {isAdmin ? 'Admin control center' : 'Wearable board'}
                                        {isAdmin ? <Shield className="ml-2 size-4" /> : <Watch className="ml-2 size-4" />}
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </WorkspaceTable>
            </WorkspacePanel>
        </div>
    );
}

export default function ProgressIndex({ viewerRole, scopeLabel, canManageOwnCheckIns, summary, athleteProfile, athletes }: ProgressPageProps) {
    const isAthleteView = viewerRole === 'athlete' && athleteProfile;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Progress" />

            <div className="space-y-6 px-4 py-6 md:px-6">
                {!isAthleteView && (
                    <div className="border-sidebar-border/70 bg-background rounded-2xl border p-5">
                        <div className="flex items-start gap-3">
                            <LineChart className="text-primary mt-1 size-5" />
                            <div>
                                <p className="font-medium">Scope</p>
                                <p className="text-muted-foreground mt-1 text-sm leading-6">{scopeLabel}</p>
                            </div>
                        </div>
                    </div>
                )}

                {isAthleteView ? (
                    <AthleteProgressView athleteProfile={athleteProfile} canManageOwnCheckIns={canManageOwnCheckIns} />
                ) : (
                    <CoachAdminProgressView
                        viewerRole={viewerRole}
                        summary={summary}
                        athletes={athletes ?? { data: [], current_page: 1, last_page: 1, prev_page_url: null, next_page_url: null, total: 0, per_page: '10' }}
                    />
                )}
            </div>
        </AppLayout>
    );
}
