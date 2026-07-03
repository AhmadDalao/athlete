import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { Card, EmptyState, LoadingState, MetricTile, Pill, Screen, SectionTitle, SessionCard } from '@/components/mobile-ui';
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
  const coachLabel = home.coaches.length ? home.coaches.map((coach) => coach.name).join(', ') : 'No coach assigned';

  return (
    <Screen>
      <View style={styles.hero}>
        <Text style={styles.heroEyebrow}>Athlete app</Text>
        <Text style={styles.heroTitle}>Hello, {home.viewer.name.split(' ')[0]}</Text>
        <Text style={styles.heroNote}>Coach: {coachLabel}</Text>
      </View>

      <Card style={styles.readinessCard}>
        <View style={styles.ring}>
          <Text style={styles.ringValue}>{readiness}</Text>
        </View>
        <View style={styles.flexOne}>
          <Text style={styles.cardTitle}>Readiness</Text>
          <Text style={styles.note}>
            {latest ? `${latest.sleepHours ?? '--'}h sleep - ${latest.strainScore ?? '--'} strain` : 'Connect WHOOP, Apple Health, or Health Connect.'}
          </Text>
        </View>
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
      <View style={styles.coachHero}>
        <Text style={styles.heroEyebrow}>Coach app</Text>
        <Text style={styles.heroTitle}>Coach board</Text>
        <Text style={styles.heroNote}>Your athletes, workouts, and message queues.</Text>
      </View>

      <View style={styles.metricGrid}>
        <MetricTile label="Athletes" value={home.summary.assignedAthletes} />
        <MetricTile label="Programs" value={home.summary.activePrograms} />
        <MetricTile label="Upcoming" value={home.summary.upcomingSessions} />
        <MetricTile label="Pending logs" value={home.summary.pendingLogs} />
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
      <Pill tone={program.status === 'active' ? 'green' : 'gold'}>{program.status}</Pill>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: colors.green,
    borderRadius: radius.xl,
    padding: 24,
    gap: 8,
  },
  coachHero: {
    backgroundColor: colors.greenDark,
    borderRadius: radius.xl,
    padding: 24,
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
    fontSize: 38,
    fontWeight: '900',
    letterSpacing: -1.2,
  },
  heroNote: {
    color: '#d8eee5',
    fontSize: 16,
    lineHeight: 24,
  },
  readinessCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 18,
  },
  ring: {
    width: 88,
    height: 88,
    borderRadius: 44,
    borderColor: colors.green,
    borderWidth: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ringValue: {
    color: colors.ink,
    fontSize: 28,
    fontWeight: '900',
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
});
