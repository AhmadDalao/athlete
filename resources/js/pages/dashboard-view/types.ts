import { type BreadcrumbItem } from '@/types';

export const dashboardBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin/dashboard',
    },
];

export interface ViewerRole {
    name: string;
    label: string;
}

export interface Viewer {
    name: string;
    email: string;
    phone: string | null;
    primaryGoal: string | null;
    preferredContactMethod: string | null;
    registrationChannel: string | null;
    roles: ViewerRole[];
    primaryRole: string | null;
}

export interface AdminOverview {
    metrics: {
        totalUsers: number;
        totalCoaches: number;
        totalAthletes: number;
        activeMemberships: number;
        expiringMemberships: number;
        connectedDevices: number;
        attentionConnections: number;
        projectedMonthlyRevenue: number;
        coachPayoutLiability: number;
        activePrograms: number;
        scheduledSessionsThisWeek: number;
        loggedSessionsThisWeek: number;
        paymentVolumeThisMonth: number;
        failedPaymentsThisMonth: number;
        newUsersThisMonth: number;
    };
    expiringMembershipWatchlist: Array<{
        userName: string;
        planName: string;
        status: string;
        daysRemaining: number | null;
        endsAt: string | null;
    }>;
    deviceAttentionQueue: Array<{
        userName: string;
        provider: string;
        status: string;
        lastSyncedAt: string | null;
    }>;
    paymentAttentionQueue: Array<{
        userName: string;
        planName: string;
        status: string;
        amount: number | null;
        currency: string;
        eventAt: string | null;
        reference: string | null;
        notes: string | null;
    }>;
    signupMix: Array<{
        method: string;
        label: string;
        enabled: boolean;
        count: number;
    }>;
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        primaryRole: string | null;
        registrationChannel: string | null;
        createdAt: string | null;
        membershipsCount: number;
        deviceConnectionsCount: number;
        currentMembership: {
            status: string;
            planName: string;
            subscribedAt: string | null;
            daysRemaining: number | null;
        } | null;
    }>;
    athleteTable: Array<{
        id: number;
        name: string;
        email: string;
        goal: string | null;
        createdAt: string | null;
        coachNames: string[];
        deviceConnectionsCount: number;
        trainingProgramsCount: number;
        latestCheckInAt: string | null;
        currentMembership: {
            status: string;
            planName: string;
            subscribedAt: string | null;
            daysRemaining: number | null;
        } | null;
    }>;
    subscriptionTable: Array<{
        id: number;
        userId: number;
        userName: string;
        userEmail: string;
        planName: string;
        status: string;
        startsAt: string | null;
        renewsAt: string | null;
        endsAt: string | null;
        daysRemaining: number | null;
        price: number;
        currency: string;
        billingProvider: string | null;
        autoRenew: boolean;
    }>;
}

export interface CoachOverview {
    metrics: {
        rosterCount: number;
        athletesNeedingAttention: number;
        activePrograms: number;
        upcomingSessions: number;
        pendingWorkoutLogs: number;
    };
    roster: Array<{
        id: number;
        name: string;
        email: string;
        goal: string;
        membershipStatus: string;
        membershipPlan: string;
        daysRemaining: number | null;
        connectedDevices: number;
        latestSnapshot: {
            metricDate: string | null;
            readinessScore: number | null;
            sleepHours: number | null;
            strainScore: number | null;
        } | null;
        latestCheckIn: {
            loggedDate: string | null;
            weightKg: number | null;
            caloriesConsumed: number | null;
            proteinGrams: number | null;
            waterLiters: number | null;
            energyScore: number | null;
            sorenessScore: number | null;
        } | null;
        currentProgram: {
            title: string;
            status: string;
            nextSessionDate: string | null;
            pendingLogs: number;
            lastWorkoutStatus: string | null;
        } | null;
        weeklyBrief: {
            priority: string;
            score: number;
            headline: string;
            summary: string;
            reasons: string[];
        };
        flags: string[];
    }>;
    attentionQueue: Array<{
        id: number;
        name: string;
        goal: string;
        flags: string[];
        currentProgram: {
            title: string;
            status: string;
            nextSessionDate: string | null;
            pendingLogs: number;
            lastWorkoutStatus: string | null;
        } | null;
    }>;
    weeklyBriefs: Array<{
        id: number;
        name: string;
        goal: string;
        weeklyBrief: {
            priority: string;
            score: number;
            headline: string;
            summary: string;
            reasons: string[];
        };
    }>;
    upcomingSessions: Array<{
        athleteName: string;
        programTitle: string;
        sessionTitle: string;
        scheduledDate: string | null;
        focus: string | null;
    }>;
}

export interface ExerciseRow {
    name: string;
    prescription: string | null;
    sets: number | null;
    reps: string | null;
    load: string | null;
    rest_seconds: number | null;
    rest_label: string | null;
    target: string | null;
    note: string | null;
}

export interface MetricTimelineEntry {
    metricDate: string | null;
    readinessScore: number | null;
    readinessBand: string | null;
    strainScore: number | null;
    sleepHours: number | null;
    sleepNeedHours: number | null;
    sleepDebtHours: number | null;
    steps: number | null;
    distanceMeters: number | null;
    activeMinutes: number | null;
    restingHeartRate: number | null;
    heartRateVariability: number | null;
    trainingLoad: number | null;
    sleepPerformancePercentage: number | null;
    sleepConsistencyPercentage: number | null;
    sleepEfficiencyPercentage: number | null;
    respiratoryRate: number | null;
    bloodOxygenPercent: number | null;
    skinTemperatureCelsius: number | null;
    remSleepHours: number | null;
    slowWaveSleepHours: number | null;
}

export interface MetricReport {
    latest: MetricTimelineEntry | null;
    overview: {
        daysTracked: number;
        averageReadiness: number | null;
        averageSleepHours: number | null;
        averageStrain: number | null;
        totalTrainingLoad: number | null;
        averageRestingHeartRate: number | null;
        averageHrv: number | null;
        averageSleepPerformance: number | null;
        averageSleepConsistency: number | null;
        averageRespiratoryRate: number | null;
        averageBloodOxygen: number | null;
        averageSleepDebtHours: number | null;
        readinessBand: string | null;
        readinessDelta: number | null;
        lowReadinessDays: number;
    };
    timeline: MetricTimelineEntry[];
    alerts: string[];
}

export interface ProgressTimelineEntry {
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

export interface ProgressReport {
    latest: ProgressTimelineEntry | null;
    overview: {
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
    };
    timeline: ProgressTimelineEntry[];
    alerts: string[];
}

export interface AthleteOverview {
    metrics: {
        coachCount: number;
        connectedDevices: number;
        membershipDaysRemaining: number | null;
        latestReadinessScore: number | null;
        latestWeightKg: number | null;
        weightDeltaKg: number | null;
        averageCaloriesConsumed: number | null;
        averageProteinGrams: number | null;
        checkInsThisWeek: number;
        upcomingSessionsCount: number;
        completedSessionsThisWeek: number;
    };
    membership: {
        planName: string;
        status: string;
        daysRemaining: number | null;
        renewsAt: string | null;
        endsAt: string | null;
        autoRenew: boolean;
    };
    training: {
        title: string;
        goal: string | null;
        status: string;
        coachName: string;
        startDate: string | null;
        endDate: string | null;
        nextSession: {
            title: string;
            scheduledDate: string | null;
            focus: string | null;
            instructions: string | null;
            videoUrl: string | null;
            exercises: ExerciseRow[];
        } | null;
        upcomingSessions: Array<{
            title: string;
            scheduledDate: string | null;
            focus: string | null;
            videoUrl: string | null;
            exerciseCount: number;
        }>;
        recentLogs: Array<{
            sessionTitle: string;
            completionStatus: string;
            performedAt: string | null;
            durationMinutes: number | null;
            exertionRating: number | null;
        }>;
    } | null;
    coaches: Array<{
        id: number;
        name: string;
        email: string;
        relationshipStatus: string;
        startDate: string | null;
        goal: string | null;
    }>;
    deviceConnections: Array<{
        provider: string;
        providerLabel: string;
        status: string;
        lastSyncedAt: string | null;
        latestSnapshot: {
            metricDate: string | null;
            readinessScore: number | null;
            sleepHours: number | null;
            strainScore: number | null;
        } | null;
    }>;
    latestSnapshot: {
        metricDate: string | null;
        readinessScore: number | null;
        strainScore: number | null;
        sleepHours: number | null;
        sleepNeedHours: number | null;
        sleepDebtHours: number | null;
        steps: number | null;
        restingHeartRate: number | null;
        heartRateVariability: number | null;
        sleepPerformancePercentage: number | null;
        sleepConsistencyPercentage: number | null;
        respiratoryRate: number | null;
        bloodOxygenPercent: number | null;
        skinTemperatureCelsius: number | null;
        trainingLoad: number | null;
    } | null;
    latestCheckIn: ProgressTimelineEntry | null;
    metricReport: MetricReport;
    progressReport: ProgressReport;
}

export interface DashboardPageProps {
    viewer: Viewer;
    admin: AdminOverview | null;
    coach: CoachOverview | null;
    athlete: AthleteOverview | null;
}
