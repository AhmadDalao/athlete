import { MaterialCommunityIcons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, EmptyState, LoadingState, MetricTile, Pill, Screen, SectionTitle, SessionCard } from '@/components/mobile-ui';
import { colors } from '@/theme';
import type { TrainingProgramSummary, TrainingSessionSummary } from '@/types/api';

export default function ProgramDetailScreen() {
  const { token, user } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const [program, setProgram] = useState<TrainingProgramSummary | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function load() {
      if (!token || !id) {
        return;
      }

      const response = await apiRequest<{ program: TrainingProgramSummary }>(
        `/api/v1/app/programs/${id}`,
        undefined,
        token,
      );

      if (active) {
        setProgram(response.data.program);
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
  }, [id, token]);

  if (isLoading || !program) {
    return <LoadingState label="Loading program..." />;
  }

  return (
    <Screen>
      <AppHeader title="Program" eyebrow="Assigned plan" onBack={() => router.back()} />

      <Card style={styles.hero}>
        <View style={styles.row}>
          <Pill tone={program.status === 'active' ? 'green' : 'gold'}>{program.status}</Pill>
          <Text style={styles.dateRange}>{program.startDate ?? 'Open start'} - {program.endDate ?? 'Open end'}</Text>
        </View>
        <Text style={styles.heroTitle}>{program.title}</Text>
        {program.goal ? <Text style={styles.heroNote}>{program.goal}</Text> : null}
        <Text style={styles.note}>
          Coach: {program.coach?.name ?? 'Not set'} - Athlete: {program.athlete?.name ?? 'Not set'}
        </Text>
      </Card>

      <View style={styles.grid}>
        <MetricTile label="Sessions" value={program.sessionCount ?? program.sessions?.length ?? 0} icon="calendar-check-outline" />
        <MetricTile label="Complete" value={program.completedSessionCount ?? 0} icon="check-decagram-outline" />
        <MetricTile label="Pending" value={program.pendingSessionCount ?? 0} icon="timer-sand" />
        <MetricTile label="Next" value={program.nextSessionDate ?? '--'} icon="calendar-clock-outline" />
      </View>

      <SectionTitle eyebrow="Schedule" title="Program sessions" note="Open a session to view media, targets, sets, and journal." />
      {program.sessions?.length ? (
        program.sessions.map((session) => <ProgramSession key={session.id} session={session} canOpen={user?.primaryRole === 'athlete'} />)
      ) : (
        <EmptyState title="No sessions" body="This program does not have sessions yet." />
      )}
    </Screen>
  );
}

function ProgramSession({ session, canOpen }: { session: TrainingSessionSummary; canOpen: boolean }) {
  const exercises = session.exercises?.slice(0, 4) ?? [];

  return (
    <View style={styles.sessionBlock}>
      <SessionCard session={session} canOpen={canOpen} />
      {exercises.length ? (
        <Card style={styles.exercisePreviewCard}>
          <View style={styles.exercisePreviewHeader}>
            <Text style={styles.previewTitle}>Exercise preview</Text>
            {session.mediaCount || session.videoUrl ? (
              <Text style={styles.mediaBadge}>
                <MaterialCommunityIcons name="play-box-outline" size={13} /> media
              </Text>
            ) : null}
          </View>
          {exercises.map((exercise, index) => (
            <View key={`${session.id}-${exercise.name}-${index}`} style={styles.exerciseLine}>
              <Text style={styles.exerciseIndex}>{index + 1}</Text>
              <View style={styles.exerciseCopy}>
                <Text style={styles.exerciseName}>{exercise.name}</Text>
                <Text style={styles.note} numberOfLines={2}>
                  {[exercise.sets ? `${exercise.sets} set(s)` : null, exercise.reps, exercise.load, exercise.restSeconds ? `${exercise.restSeconds}s rest` : null]
                    .filter(Boolean)
                    .join(' - ') || exercise.prescription || exercise.target || 'Coach prescription'}
                </Text>
              </View>
            </View>
          ))}
        </Card>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  hero: {
    gap: 12,
    backgroundColor: '#ffffff',
  },
  heroTitle: {
    color: colors.ink,
    fontSize: 30,
    fontWeight: '900',
    letterSpacing: -0.9,
  },
  heroNote: {
    color: colors.ink,
    fontSize: 16,
    lineHeight: 24,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  dateRange: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '800',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  sessionBlock: {
    gap: 10,
  },
  exercisePreviewCard: {
    gap: 12,
    backgroundColor: '#fbfaf6',
    shadowOpacity: 0,
    elevation: 0,
  },
  exercisePreviewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 10,
  },
  previewTitle: {
    color: colors.ink,
    fontSize: 16,
    fontWeight: '900',
  },
  mediaBadge: {
    color: colors.blue,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
  exerciseLine: {
    flexDirection: 'row',
    gap: 10,
    borderTopColor: colors.border,
    borderTopWidth: 1,
    paddingTop: 10,
  },
  exerciseIndex: {
    color: colors.green,
    fontSize: 15,
    fontWeight: '900',
    width: 22,
  },
  exerciseCopy: {
    flex: 1,
  },
  exerciseName: {
    color: colors.ink,
    fontSize: 16,
    fontWeight: '900',
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
  },
});
