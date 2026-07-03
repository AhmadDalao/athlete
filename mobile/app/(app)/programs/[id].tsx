import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { Card, EmptyState, LoadingState, Pill, Screen, SectionTitle, SessionCard } from '@/components/mobile-ui';
import { colors } from '@/theme';
import type { TrainingProgramSummary } from '@/types/api';

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
      <Pressable onPress={() => router.back()}>
        <Text style={styles.back}>Back</Text>
      </Pressable>

      <SectionTitle eyebrow="Program" title={program.title} note={program.goal ?? undefined} />

      <Card style={styles.summary}>
        <View style={styles.row}>
          <Pill tone={program.status === 'active' ? 'green' : 'gold'}>{program.status}</Pill>
          <Text style={styles.note}>{program.startDate ?? 'Open start'} - {program.endDate ?? 'Open end'}</Text>
        </View>
        <Text style={styles.cardTitle}>{program.sessionCount ?? 0} session(s)</Text>
        <Text style={styles.note}>
          Coach: {program.coach?.name ?? 'Not set'} - Athlete: {program.athlete?.name ?? 'Not set'}
        </Text>
      </Card>

      <SectionTitle eyebrow="Schedule" title="Program sessions" />
      {program.sessions?.length ? (
        program.sessions.map((session) => (
          <SessionCard key={session.id} session={session} canOpen={user?.primaryRole === 'athlete'} />
        ))
      ) : (
        <EmptyState title="No sessions" body="This program does not have sessions yet." />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  back: {
    color: colors.green,
    fontSize: 16,
    fontWeight: '900',
  },
  summary: {
    gap: 10,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  cardTitle: {
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
  },
});
