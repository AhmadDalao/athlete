import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { collectNativeHealthRecords, syncMobileHealthRecords } from '@/health/health-sync';
import { Card, EmptyState, LoadingState, MetricTile, PrimaryButton, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

type WearablesPayload = {
  summary: Record<string, number | null>;
  connections: {
    data: Array<{
      id: number;
      providerLabel: string;
      status: string;
      lastSyncedAt?: string | null;
      latestSnapshot?: Record<string, number | string | null> | null;
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
  const [isLoading, setIsLoading] = useState(true);
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

    const records = await collectNativeHealthRecords();

    if (!records.length) {
      setMessage('Native health modules are ready to wire in development builds; no local records were returned yet.');
      return;
    }

    await syncMobileHealthRecords({
      token,
      provider: 'health_connect',
      records,
      deviceName: 'Mobile device',
    });
    setMessage('Health records synced.');
    await load();
  }

  if (isLoading || !payload) {
    return <LoadingState label="Loading devices..." />;
  }

  return (
    <Screen>
      <SectionTitle eyebrow="Wearables" title="Device data" note="WHOOP stays OAuth-based. Phone health records sync through authenticated mobile sync." />

      <View style={styles.grid}>
        <MetricTile label="Connections" value={payload.summary.totalConnections ?? 0} />
        <MetricTile label="Healthy" value={payload.summary.healthyConnections ?? 0} />
        <MetricTile label="Attention" value={payload.summary.attentionRequired ?? 0} />
        <MetricTile label="Readiness" value={payload.summary.averageReadiness ?? '--'} />
      </View>

      <PrimaryButton label="Sync phone health data" onPress={syncNative} />
      {message ? <Text style={styles.message}>{message}</Text> : null}

      <SectionTitle eyebrow="Connections" title="Linked devices" />
      {payload.connections.data.length ? (
        payload.connections.data.map((connection) => (
          <Card key={connection.id} style={styles.connection}>
            <Text style={styles.cardTitle}>{connection.providerLabel}</Text>
            <Text style={styles.note}>{connection.status} - {connection.lastSyncedAt ?? 'not synced yet'}</Text>
          </Card>
        ))
      ) : (
        <EmptyState title="No device connected" body="Connect WHOOP from the web flow, then add Apple Health or Health Connect through the native app build." />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
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
