import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { collectNativeHealthRecords, syncMobileHealthRecords } from '@/health/health-sync';
import { AppHeader, Card, EmptyState, LoadingState, MetricRow, PrimaryButton, Screen, SectionTitle, SignalRing } from '@/components/mobile-ui';
import { colors } from '@/theme';
import type { Snapshot } from '@/types/api';

type WearablesPayload = {
  summary: Record<string, number | null>;
  connections: {
    data: Array<{
      id: number;
      providerLabel: string;
      status: string;
      lastSyncedAt?: string | null;
      latestSnapshot?: Snapshot | null;
    }>;
  };
  whoopIntegration?: {
    connectUrl?: string;
    oauthReady?: boolean;
  };
};

export default function WearablesScreen() {
  const { token } = useAuth();
  const [payload, setPayload] = useState<WearablesPayload | null>(null);
  const [tab, setTab] = useState<'daily' | 'trends'>('daily');
  const [isLoading, setIsLoading] = useState(true);
  const [isSyncing, setIsSyncing] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    const response = await apiRequest<WearablesPayload>('/api/v1/wearables', undefined, token);
    setPayload(response.data);
    setIsLoading(false);
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load().catch(() => setIsLoading(false));
    }, [load]),
  );

  async function syncNative() {
    if (!token) {
      return;
    }

    setIsSyncing(true);
    setMessage(null);

    try {
      const records = await collectNativeHealthRecords();

      if (!records.length) {
        setMessage('Health Connect returned no readable records. Check Samsung Health and Health Connect permissions.');
        return;
      }

      await syncMobileHealthRecords({
        token,
        provider: 'health_connect',
        records,
        deviceName: Platform.OS === 'android' ? 'Android Health Connect' : 'Mobile device',
      });
      setMessage(`${records.length} daily health record(s) synced.`);
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Health sync failed. Reopen permissions and try again.');
    } finally {
      setIsSyncing(false);
    }
  }

  if (isLoading || !payload) {
    return <LoadingState label="Loading devices..." />;
  }

  const latest = payload.connections.data.find((connection) => connection.latestSnapshot)?.latestSnapshot ?? null;

  return (
    <Screen>
      <AppHeader title="Devices" eyebrow="Wearable data" />

      <View style={styles.tabs}>
        <TabButton label="Daily" active={tab === 'daily'} onPress={() => setTab('daily')} />
        <TabButton label="Trends" active={tab === 'trends'} onPress={() => setTab('trends')} />
      </View>

      {tab === 'daily' ? (
        <>
          <Card style={styles.signalCard}>
            <SignalRing label="Readiness" value={latest?.readinessScore ?? '--'} />
            <SignalRing label="Sleep" value={latest?.sleepHours ? `${latest.sleepHours}h` : '--'} tone="blue" />
            <SignalRing label="Strain" value={latest?.strainScore ?? '--'} tone="gold" />
          </Card>

          <PrimaryButton
            label={isSyncing ? 'Syncing...' : Platform.OS === 'android' ? 'Sync Health Connect' : 'Apple Health coming next'}
            onPress={syncNative}
            disabled={isSyncing}
          />
          {message ? <Text style={styles.message}>{message}</Text> : null}

          <SectionTitle eyebrow="Today" title="Health monitor" note="Latest available wearable snapshot." />
          <View style={styles.metricRows}>
            <MetricRow icon="shoe-print" label="Steps" value={formatValue(latest?.steps)} />
            <MetricRow icon="heart-pulse" label="Resting heart rate" value={formatValue(latest?.restingHeartRate)} target="bpm" />
            <MetricRow icon="fire" label="Calories" value={formatValue(latest?.caloriesBurned)} />
            <MetricRow icon="waveform" label="HRV" value={formatValue(latest?.heartRateVariability)} target="ms" />
            <MetricRow icon="bed-outline" label="Sleep need" value={latest?.sleepNeedHours ? `${latest.sleepNeedHours}h` : '--'} />
          </View>
        </>
      ) : (
        <>
          <SectionTitle eyebrow="Trends" title="30-day direction" note="Deeper charts will use synced mobile and WHOOP history." />
          <View style={styles.metricRows}>
            <MetricRow icon="watch-variant" label="Connections" value={payload.summary.totalConnections ?? 0} />
            <MetricRow icon="check-circle-outline" label="Healthy" value={payload.summary.healthyConnections ?? 0} />
            <MetricRow icon="alert-circle-outline" label="Attention" value={payload.summary.attentionRequired ?? 0} />
            <MetricRow icon="target" label="Average readiness" value={payload.summary.averageReadiness ?? '--'} />
          </View>
        </>
      )}

      <SectionTitle eyebrow="Connections" title="Linked devices" />
      {payload.connections.data.length ? (
        payload.connections.data.map((connection) => (
          <Card key={connection.id} style={styles.connection}>
            <Text style={styles.cardTitle}>{connection.providerLabel}</Text>
            <Text style={styles.note}>{connection.status} - {connection.lastSyncedAt ?? 'not synced yet'}</Text>
          </Card>
        ))
      ) : (
        <EmptyState
          title="No device connected"
          body="Connect WHOOP from the web flow, then add Apple Health or Health Connect through the native app build."
        />
      )}
    </Screen>
  );
}

function TabButton({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={[styles.tabButton, active && styles.tabButtonActive]}>
      <Text style={[styles.tabText, active && styles.tabTextActive]}>{label}</Text>
    </Pressable>
  );
}

function formatValue(value?: number | null) {
  return value === null || value === undefined ? '--' : value;
}

const styles = StyleSheet.create({
  tabs: {
    flexDirection: 'row',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 14,
  },
  tabButtonActive: {
    borderBottomColor: colors.ink,
    borderBottomWidth: 2,
  },
  tabText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '900',
  },
  tabTextActive: {
    color: colors.ink,
  },
  signalCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  metricRows: {
    gap: 10,
  },
  connection: {
    gap: 6,
  },
  cardTitle: {
    color: colors.ink,
    fontSize: 18,
    fontWeight: '900',
  },
  note: {
    color: colors.muted,
    fontSize: 14,
  },
  message: {
    color: colors.green,
    fontWeight: '800',
    textAlign: 'center',
  },
});
