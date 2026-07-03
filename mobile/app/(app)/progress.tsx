import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { EmptyState, LoadingState, MetricTile, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

type ProgressPayload = {
  viewerRole: string;
  summary: Record<string, number>;
  athleteProfile?: {
    metrics: Record<string, number | null>;
    latestSnapshot?: Record<string, number | string | null> | null;
    recentCheckIns?: Array<Record<string, number | string | null>>;
  } | null;
  athletes?: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      progressOverview: Record<string, number | null>;
    }>;
  } | null;
};

export default function ProgressScreen() {
  const { token } = useAuth();
  const [payload, setPayload] = useState<ProgressPayload | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let active = true;

      async function load() {
        if (!token) {
          return;
        }

        const response = await apiRequest<ProgressPayload>('/api/v1/progress', undefined, token);

        if (active) {
          setPayload(response.data);
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

  if (isLoading || !payload) {
    return <LoadingState label="Loading progress..." />;
  }

  const metrics = payload.athleteProfile?.metrics;

  return (
    <Screen>
      <SectionTitle eyebrow="Health" title="Progress board" note="Training, body, nutrition, and wearable data in one view." />

      {metrics ? (
        <View style={styles.grid}>
          <MetricTile label="Weight" value={metrics.latestWeightKg ?? '--'} detail="kg" />
          <MetricTile label="Protein" value={metrics.averageProteinGrams ?? '--'} detail="avg grams" />
          <MetricTile label="Completion" value={`${metrics.completionRate ?? 0}%`} />
          <MetricTile label="Check-ins" value={metrics.checkInsThisWeek ?? 0} detail="this week" />
        </View>
      ) : null}

      {payload.athletes?.data?.length ? (
        <>
          <SectionTitle eyebrow="Coach view" title="Athlete snapshots" />
          {payload.athletes.data.map((athlete) => (
            <View key={athlete.id} style={styles.athleteRow}>
              <View>
                <Text style={styles.rowTitle}>{athlete.name}</Text>
                <Text style={styles.note}>{athlete.email}</Text>
              </View>
              <Text style={styles.rowMetric}>{athlete.progressOverview.completionRate ?? 0}%</Text>
            </View>
          ))}
        </>
      ) : null}

      {!metrics && !payload.athletes?.data?.length ? (
        <EmptyState title="No progress data" body="Check-ins and wearable records will appear here after syncing." />
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  athleteRow: {
    borderRadius: 22,
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  rowTitle: {
    color: colors.ink,
    fontSize: 18,
    fontWeight: '900',
  },
  note: {
    color: colors.muted,
    fontSize: 14,
  },
  rowMetric: {
    color: colors.green,
    fontSize: 22,
    fontWeight: '900',
  },
});
