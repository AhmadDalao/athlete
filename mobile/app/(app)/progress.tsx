import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, EmptyState, ErrorState, LoadingState, MetricTile, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors, radius } from '@/theme';

type ProgressPayload = {
  viewerRole: string;
  summary: Record<string, number>;
  athleteProfile?: {
    metrics: Record<string, number | null>;
    latestSnapshot?: Record<string, number | string | null> | null;
    progressReport?: {
      timeline?: Array<Record<string, number | string | null>>;
      alerts?: string[];
    };
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
  const [error, setError] = useState<string | null>(null);

  const loadProgress = useCallback(async () => {
    if (!token) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await apiRequest<ProgressPayload>('/api/v1/progress', undefined, token);
      setPayload(response.data);
    } catch (loadError) {
      setError(apiErrorMessage(loadError, 'Could not load progress.'));
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      void loadProgress();
    }, [loadProgress]),
  );

  if (isLoading && !payload) {
    return <LoadingState label="Loading progress..." />;
  }

  if (error && !payload) {
    return (
      <Screen>
        <AppHeader title="Health" eyebrow="Progress" />
        <ErrorState body={error} onRetry={loadProgress} />
      </Screen>
    );
  }

  if (!payload) {
    return <LoadingState label="Loading progress..." />;
  }

  const metrics = payload.athleteProfile?.metrics;
  const timeline = payload.athleteProfile?.progressReport?.timeline ?? [];
  const recentCheckIns = payload.athleteProfile?.recentCheckIns ?? [];

  return (
    <Screen>
      <AppHeader title="Health" eyebrow="Progress" />

      {metrics ? (
        <>
          <SectionTitle eyebrow="Latest" title="Body and training" note="Quick view first, records below." />
          <View style={styles.grid}>
            <MetricTile label="Weight" value={metrics.latestWeightKg ?? '--'} detail="kg" />
            <MetricTile label="Protein" value={metrics.averageProteinGrams ?? '--'} detail="avg grams" />
            <MetricTile label="Completion" value={`${metrics.completionRate ?? 0}%`} />
            <MetricTile label="Check-ins" value={metrics.checkInsThisWeek ?? 0} detail="this week" />
          </View>

          <SectionTitle eyebrow="Trend" title="Recent direction" />
          <TrendCard label="Weight" unit="kg" values={timeline.map((row) => numberFrom(row.weightKg))} />
          <TrendCard label="Protein" unit="g" values={timeline.map((row) => numberFrom(row.proteinGrams))} tone="blue" />
          <TrendCard label="Energy" unit="/10" values={timeline.map((row) => numberFrom(row.energyScore))} tone="gold" />
        </>
      ) : null}

      {recentCheckIns.length ? (
        <>
          <SectionTitle eyebrow="Records" title="Recent check-ins" />
          {recentCheckIns.map((checkIn, index) => (
            <Card key={`${checkIn.loggedDate ?? index}`} style={styles.checkInCard}>
              <View style={styles.checkInTop}>
                <Text style={styles.rowTitle}>{String(checkIn.loggedDate ?? 'Check-in')}</Text>
                <Text style={styles.rowMetric}>{numberFrom(checkIn.weightKg) ?? '--'} kg</Text>
              </View>
              <Text style={styles.note}>
                Calories {numberFrom(checkIn.caloriesConsumed) ?? '--'} - Protein {numberFrom(checkIn.proteinGrams) ?? '--'}g - Water{' '}
                {numberFrom(checkIn.waterLiters) ?? '--'}L
              </Text>
              <Text style={styles.note}>
                Energy {numberFrom(checkIn.energyScore) ?? '--'}/10 - Soreness {numberFrom(checkIn.sorenessScore) ?? '--'}/10 - Sleep{' '}
                {numberFrom(checkIn.sleepQualityScore) ?? '--'}/10
              </Text>
            </Card>
          ))}
        </>
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

function TrendCard({
  label,
  unit,
  values,
  tone = 'green',
}: {
  label: string;
  unit: string;
  values: Array<number | undefined>;
  tone?: 'green' | 'blue' | 'gold';
}) {
  const cleanValues = values.filter((value): value is number => value !== undefined);
  const latest = cleanValues.at(-1);
  const max = Math.max(...cleanValues, 1);
  const color = tone === 'blue' ? colors.blue : tone === 'gold' ? colors.gold : colors.green;

  return (
    <Card style={styles.trendCard}>
      <View style={styles.checkInTop}>
        <View>
          <Text style={styles.rowTitle}>{label}</Text>
          <Text style={styles.note}>Latest {latest ?? '--'}{unit}</Text>
        </View>
        <Text style={styles.rowMetric}>{cleanValues.length} logs</Text>
      </View>
      <View style={styles.barRow}>
        {cleanValues.slice(-10).map((value, index) => (
          <View key={`${label}-${index}-${value}`} style={styles.barSlot}>
            <View style={[styles.bar, { height: Math.max(18, (value / max) * 88), backgroundColor: color }]} />
          </View>
        ))}
      </View>
    </Card>
  );
}

function numberFrom(value: unknown) {
  return typeof value === 'number' && Number.isFinite(value) ? value : undefined;
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  athleteRow: {
    borderRadius: 22,
    backgroundColor: colors.card,
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
  trendCard: {
    gap: 14,
  },
  barRow: {
    minHeight: 104,
    borderRadius: radius.lg,
    backgroundColor: colors.panelDark,
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    gap: 7,
    padding: 14,
  },
  barSlot: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  bar: {
    width: '100%',
    maxWidth: 24,
    borderRadius: 999,
  },
  checkInCard: {
    gap: 8,
  },
  checkInTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
});
