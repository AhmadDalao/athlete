import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import {
  collectNativeHealthRecordsWithDiagnostics,
  getNativeHealthLinkStatus,
  linkMobileHealthProvider,
  openNativeHealthSettings,
  requestNativeHealthAccess,
  syncMobileHealthRecords,
  type HealthConnectReadDiagnostic,
  type HealthConnectPermissionStatus,
} from '@/health/health-sync';
import {
  AppHeader,
  Card,
  EmptyState,
  ErrorState,
  LoadingState,
  MetricRow,
  PrimaryButton,
  Screen,
  SecondaryButton,
  SectionTitle,
  SignalRing,
} from '@/components/mobile-ui';
import { colors } from '@/theme';
import type { Snapshot } from '@/types/api';

type WearableConnection = {
  id: number;
  provider: string;
  providerLabel: string;
  status: string;
  lastSyncedAt?: string | null;
  lastSyncStartedAt?: string | null;
  grantedScopes?: string[];
  lastErrorMessage?: string | null;
  latestSnapshot?: Snapshot | null;
};

type ProviderStatus = {
  linked: boolean;
  status?: string | null;
  authType?: string | null;
  grantedScopes: string[];
  lastSyncedAt?: string | null;
  lastSyncStartedAt?: string | null;
  lastErrorMessage?: string | null;
  syncFailuresCount: number;
  latestSnapshot?: Snapshot | null;
};

type WearablesPayload = {
  summary: Record<string, number | null>;
  latestSnapshot?: Snapshot | null;
  syncState?: {
    hasLiveData: boolean;
    latestProvider?: string | null;
    latestMetricDate?: string | null;
    message?: string | null;
  };
  providerStatus?: Record<'health_connect' | 'apple_health' | 'whoop', ProviderStatus>;
  connections: {
    data: WearableConnection[];
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
  const [isLinking, setIsLinking] = useState(false);
  const [linkStatus, setLinkStatus] = useState<HealthConnectPermissionStatus | null>(null);
  const [diagnostics, setDiagnostics] = useState<HealthConnectReadDiagnostic[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const [response, nativeStatus] = await Promise.all([
        apiRequest<WearablesPayload>('/api/v1/wearables', undefined, token),
        getNativeHealthLinkStatus(),
      ]);
      setPayload(response.data);
      setLinkStatus(nativeStatus);
    } catch (loadError) {
      setError(apiErrorMessage(loadError, 'Could not load wearable data.'));
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  useEffect(() => {
    if (!token || tab !== 'daily') {
      return;
    }

    const interval = setInterval(() => {
      void load();
    }, 60000);

    return () => clearInterval(interval);
  }, [load, tab, token]);

  async function linkNative() {
    if (!token) {
      return;
    }

    setIsLinking(true);
    setMessage(null);

    try {
      const status = await requestNativeHealthAccess();
      setLinkStatus(status);
      await linkMobileHealthProvider({
        token,
        provider: 'health_connect',
        deviceName: 'Samsung Health via Health Connect',
        scopes: status.grantedScopes,
        permissionStatus: status.permissionStatus,
      });
      setMessage(status.permissionStatus === 'granted'
        ? 'Samsung Health is linked. You can sync now.'
        : 'Samsung Health is partially linked. Sync will use the permissions you allowed.');
      await load();
    } catch (error) {
      setMessage(apiErrorMessage(error, 'Could not link Samsung Health.'));
    } finally {
      setIsLinking(false);
    }
  }

  async function syncNative() {
    if (!token) {
      return;
    }

    setIsSyncing(true);
    setMessage(null);

    try {
      let status = linkStatus ?? await getNativeHealthLinkStatus();

      if (!status.canSync) {
        status = await requestNativeHealthAccess();
        await linkMobileHealthProvider({
          token,
          provider: 'health_connect',
          deviceName: 'Samsung Health via Health Connect',
          scopes: status.grantedScopes,
          permissionStatus: status.permissionStatus,
        });
      }

      setLinkStatus(status);

      const collection = await collectNativeHealthRecordsWithDiagnostics();
      const records = collection.records;
      setDiagnostics(collection.diagnostics);

      if (!records.length) {
        setMessage(collection.message);
        return;
      }

      const syncResponse = await syncMobileHealthRecords({
        token,
        provider: 'health_connect',
        records,
        deviceName: Platform.OS === 'android' ? 'Android Health Connect' : 'Mobile device',
        scopes: status.grantedScopes,
      });
      setPayload((current) => current
        ? {
            ...current,
            latestSnapshot: (syncResponse.data.latestSnapshot as Snapshot | null | undefined) ?? current.latestSnapshot,
            syncState: {
              hasLiveData: true,
              latestProvider: 'health_connect',
              latestMetricDate: (syncResponse.data.latestSnapshot as Snapshot | null | undefined)?.metricDate ?? current.syncState?.latestMetricDate,
              message: syncResponse.data.syncMessage,
            },
          }
        : current);
      setMessage(`${syncResponse.data.acceptedCount} of ${syncResponse.data.receivedRecordCount} daily health record(s) synced.`);
      await load();
    } catch (error) {
      setMessage(apiErrorMessage(error, 'Health sync failed. Reopen permissions and try again.'));
    } finally {
      setIsSyncing(false);
    }
  }

  if (isLoading && !payload) {
    return <LoadingState label="Loading devices..." />;
  }

  if (error && !payload) {
    return (
      <Screen>
        <AppHeader title="Devices" eyebrow="Wearable data" />
        <ErrorState body={error} onRetry={load} />
      </Screen>
    );
  }

  if (!payload) {
    return <LoadingState label="Loading devices..." />;
  }

  const healthConnectProvider = payload.providerStatus?.health_connect;
  const healthConnectConnection = payload.connections.data.find((connection) => connection.provider === 'health_connect');
  const latest = payload.latestSnapshot
    ?? healthConnectProvider?.latestSnapshot
    ?? payload.connections.data.find((connection) => connection.latestSnapshot)?.latestSnapshot
    ?? null;
  const latestDiagnostics = diagnostics.slice(-3).reverse();

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

          <Card style={styles.linkCard}>
            <View style={styles.linkHeader}>
              <View style={styles.flexOne}>
                <Text style={styles.cardTitle}>Samsung Health link</Text>
                <Text style={styles.note}>
                  Galaxy Watch data should sync into Samsung Health first, then Health Connect lets Throughline read it.
                </Text>
              </View>
              <View style={[styles.statusDot, linkStatus?.canSync && styles.statusDotLinked]} />
            </View>

            <View style={styles.statusGrid}>
              <StatusTile label="Phone permission" value={permissionLabel(linkStatus)} />
              <StatusTile label="Backend device" value={connectionLabel(healthConnectProvider, healthConnectConnection)} />
              <StatusTile label="Last API sync" value={healthConnectProvider?.lastSyncedAt ?? healthConnectConnection?.lastSyncedAt ?? 'Not synced'} />
              <StatusTile label="Latest displayed" value={latest?.metricDate ?? 'No live snapshot'} />
            </View>

            <Text style={styles.syncStateText}>{payload.syncState?.message ?? 'Sync state is waiting for the first real health record.'}</Text>

            {linkStatus?.missingScopes.length ? (
              <Text style={styles.warningText}>
                Missing permissions: {linkStatus.missingScopes.slice(0, 4).join(', ')}
                {linkStatus.missingScopes.length > 4 ? '...' : ''}
              </Text>
            ) : null}

            <View style={styles.buttonStack}>
              <PrimaryButton
                label={isLinking ? 'Opening permission...' : linkStatus?.canSync ? 'Relink permissions' : 'Link Samsung Health'}
                onPress={linkNative}
                disabled={isLinking || isSyncing || Platform.OS !== 'android'}
              />
              <PrimaryButton
                label={isSyncing ? 'Syncing data...' : 'Sync Samsung Health'}
                onPress={syncNative}
                disabled={isSyncing || isLinking || Platform.OS !== 'android'}
              />
              <SecondaryButton
                label="Open Health Connect settings"
                onPress={() => {
                  void openNativeHealthSettings();
                }}
              />
            </View>
          </Card>

          {message ? <Text style={styles.message}>{message}</Text> : null}

          {latestDiagnostics.length ? (
            <Card style={styles.diagnosticsCard}>
              <Text style={styles.cardTitle}>Health Connect diagnostics</Text>
              {latestDiagnostics.map((day) => (
                <View key={day.metricDate} style={styles.diagnosticRow}>
                  <Text style={styles.statusLabel}>{day.metricDate}</Text>
                  <Text style={styles.note}>
                    {Object.entries(day.recordCounts)
                      .filter(([, count]) => count > 0)
                      .map(([key, count]) => `${key.replace(/_/g, ' ')} ${count}`)
                      .join(' · ') || 'No readable records'}
                  </Text>
                  {day.errors.length ? (
                    <Text style={styles.warningText}>
                      {day.errors.slice(0, 2).map((readError) => `${readError.recordType}: ${readError.message}`).join(' · ')}
                    </Text>
                  ) : null}
                </View>
              ))}
            </Card>
          ) : null}

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

function StatusTile({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.statusTile}>
      <Text style={styles.statusLabel}>{label}</Text>
      <Text style={styles.statusValue} numberOfLines={2}>{value}</Text>
    </View>
  );
}

function formatValue(value?: number | null) {
  return value === null || value === undefined ? '--' : value;
}

function permissionLabel(status: HealthConnectPermissionStatus | null) {
  if (!status) {
    return 'Checking';
  }

  if (!status.available) {
    return 'Unavailable';
  }

  if (status.permissionStatus === 'granted') {
    return 'Linked';
  }

  if (status.permissionStatus === 'partial') {
    return 'Partial';
  }

  return 'Needs permission';
}

function connectionLabel(provider?: ProviderStatus, connection?: WearableConnection) {
  if (!provider?.linked && !connection) {
    return 'Not linked';
  }

  const status = provider?.status ?? connection?.status;

  return status === 'connected' ? 'Linked' : status ?? 'Linked';
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
    borderBottomColor: colors.green,
    borderBottomWidth: 2,
  },
  tabText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '900',
  },
  tabTextActive: {
    color: colors.green,
  },
  signalCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  linkCard: {
    gap: 16,
  },
  linkHeader: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'flex-start',
  },
  flexOne: {
    flex: 1,
  },
  statusDot: {
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.gold,
  },
  statusDotLinked: {
    backgroundColor: colors.green,
  },
  statusGrid: {
    gap: 8,
  },
  statusTile: {
    borderColor: colors.border,
    borderRadius: 18,
    borderWidth: 1,
    padding: 14,
    backgroundColor: colors.cardRaised,
  },
  statusLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  statusValue: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
    marginTop: 6,
  },
  warningText: {
    color: colors.danger,
    fontSize: 13,
    fontWeight: '800',
    lineHeight: 18,
  },
  syncStateText: {
    color: colors.blue,
    fontSize: 13,
    fontWeight: '900',
    lineHeight: 18,
  },
  diagnosticsCard: {
    gap: 14,
  },
  diagnosticRow: {
    gap: 5,
    borderTopColor: colors.border,
    borderTopWidth: 1,
    paddingTop: 12,
  },
  buttonStack: {
    gap: 10,
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
