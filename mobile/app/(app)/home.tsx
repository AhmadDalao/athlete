import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import {
  AppHeader,
  Card,
  EmptyState,
  LoadingState,
  MetricTile,
  Pill,
  Screen,
  SectionTitle,
  SessionCard,
  SignalRing,
} from '@/components/mobile-ui';
import { colors, radius } from '@/theme';
import type { AppHome, AthleteHome, CoachHome, TrainingProgramSummary } from '@/types/api';

export default function HomeScreen() {
  const { token } = useAuth();
  const [home, setHome] = useState<AppHome | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let active = true;

      async function load() {
        if (!token) {
          return;
        }

        setIsLoading(true);
        const response = await apiRequest<AppHome>('/api/v1/app/home', undefined, token);

        if (active) {
          setHome(response.data);
          setIsLoading(false);
        }
      }

      load().catch(() => {
        if (active) {
          setIsLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [token]),
  );

  if (isLoading || !home) {
    return <LoadingState />;
  }

  return home.role === 'coach' ? <CoachHomeView home={home} /> : <AthleteHomeView home={home} />;
}

function AthleteHomeView({ home }: { home: AthleteHome }) {
  const latest = home.wearable.latestSnapshot;
  const readiness = latest?.readinessScore ? Math.round(latest.readinessScore) : '--';
  const sleep = latest?.sleepHours ? `${Number(latest.sleepHours).toFixed(1)}h` : '--';
  const strain = latest?.strainScore ? Number(latest.strainScore).toFixed(1) : '--';
  const coachLabel = home.coaches.length ? home.coaches.map((coach) => coach.name).join(', ') : 'No coach assigned';

  return (
    <Screen>
      <AppHeader title="Throughline" eyebrow="Athlete app" rightLabel={initials(home.viewer.name)} />

      <View style={styles.hero}>
        <Text style={styles.heroEyebrow}>Today</Text>
        <Text style={styles.heroTitle}>Hello, {home.viewer.name.split(' ')[0]}</Text>
        <Text style={styles.heroNote}>{coachLabel} · {home.programs.length} active program(s)</Text>
      </View>

      <Card style={styles.signalCard}>
        <SignalRing label="Readiness" value={readiness} detail={latest?.readinessBand ?? 'Score'} />
        <SignalRing label="Sleep" value={sleep} detail="Hours" tone="blue" />
        <SignalRing label="Strain" value={strain} detail="Load" tone="gold" />
      </Card>

      <SectionTitle eyebrow="Today" title={home.todaySessions.length ? 'Workout assigned' : 'No workout today'} />
      {home.todaySessions.length ? (
        home.todaySessions.map((session) => <SessionCard key={session.id} session={session} />)
      ) : (
        <EmptyState title="Rest or check in" body="When your coach schedules a session, it will appear here first." />
      )}

      <SectionTitle eyebrow="Programs" title="Assigned programs" note="Open the full block to review schedule, media, and exercises." />
      {home.programs.length ? (
        home.programs.map((program) => <ProgramRow key={program.id} program={program} />)
      ) : (
        <EmptyState title="No active program" body="Your assigned programs will appear here after your coach publishes them." />
      )}

      <SectionTitle eyebrow="Account" title="Membership" />
      <Card style={styles.detailCard}>
        <Text style={styles.cardTitle}>{home.membership?.planName ?? 'No active plan'}</Text>
        <Text style={styles.note}>
          {home.membership ? `${home.membership.statusLabel} - ${home.membership.daysRemaining ?? 0} day(s) remaining` : 'Membership details will appear after subscription setup.'}
        </Text>
      </Card>
    </Screen>
  );
}

function CoachHomeView({ home }: { home: CoachHome }) {
  return (
    <Screen>
      <AppHeader title="Coach" eyebrow="Coach app" rightLabel={initials(home.viewer.name)} />

      <View style={styles.coachHero}>
        <Text style={styles.heroEyebrow}>Today</Text>
        <Text style={styles.heroTitle}>Coach board</Text>
        <Text style={styles.heroNote}>Your athletes, workouts, and message queues.</Text>
      </View>

      <View style={styles.metricGrid}>
        <MetricTile label="Athletes" value={home.summary.assignedAthletes} icon="account-group-outline" />
        <MetricTile label="Programs" value={home.summary.activePrograms} icon="clipboard-text-outline" />
        <MetricTile label="Upcoming" value={home.summary.upcomingSessions} icon="calendar-clock" />
        <MetricTile label="Pending logs" value={home.summary.pendingLogs} icon="alert-circle-outline" />
      </View>

      <SectionTitle eyebrow="Schedule" title="Next sessions" />
      {home.schedule.length ? (
        home.schedule.map((session) => <SessionCard key={session.id} session={session} canOpen={false} />)
      ) : (
        <EmptyState title="Nothing scheduled" body="Create sessions from the web coach workspace, then they will appear here." />
      )}

      <SectionTitle eyebrow="Roster" title="Assigned athletes" />
      <Card style={styles.listCard}>
        {home.athletes.length ? (
          home.athletes.map((athlete) => (
            <View key={athlete.id} style={styles.listRow}>
              <View style={styles.flexOne}>
                <Text style={styles.cardTitle}>{athlete.name}</Text>
                <Text style={styles.note}>{athlete.goal ?? athlete.email}</Text>
              </View>
              <Pill tone={athlete.latestSnapshot ? 'green' : 'neutral'}>{athlete.latestSnapshot ? 'tracked' : 'no data'}</Pill>
            </View>
          ))
        ) : (
          <Text style={styles.note}>No assigned athletes yet.</Text>
        )}
      </Card>
    </Screen>
  );
}

function ProgramRow({ program }: { program: TrainingProgramSummary }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/programs/[id]', params: { id: String(program.id) } })}
      style={styles.programRow}
    >
      <View style={styles.flexOne}>
        <Text style={styles.cardTitle}>{program.title}</Text>
        <Text style={styles.note}>{program.goal ?? `${program.sessionCount ?? 0} session(s)`}</Text>
      </View>
      <View style={styles.programAction}>
        <Pill tone={program.status === 'active' ? 'green' : 'gold'}>{program.status}</Pill>
        <Text style={styles.openText}>Open</Text>
      </View>
    </Pressable>
  );
}

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: colors.green,
    borderRadius: radius.xl,
    padding: 20,
    gap: 8,
  },
  coachHero: {
    backgroundColor: colors.greenDark,
    borderRadius: radius.xl,
    padding: 20,
    gap: 8,
  },
  heroEyebrow: {
    color: '#d8eee5',
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 34,
    fontWeight: '900',
    letterSpacing: -1.2,
  },
  heroNote: {
    color: '#d8eee5',
    fontSize: 16,
    lineHeight: 24,
  },
  signalCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  flexOne: {
    flex: 1,
  },
  cardTitle: {
    color: colors.ink,
    fontSize: 18,
    fontWeight: '900',
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
  },
  detailCard: {
    gap: 6,
  },
  metricGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  listCard: {
    gap: 14,
  },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  programRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: radius.lg,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: '#ffffff',
    padding: 18,
  },
  programAction: {
    alignItems: 'flex-end',
    gap: 8,
  },
  openText: {
    color: colors.green,
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'uppercase',
  },
});
