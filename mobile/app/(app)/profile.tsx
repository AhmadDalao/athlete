import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, ErrorState, LoadingState, MetricTile, PrimaryButton, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

type ProfileProgress = {
  athleteProfile?: {
    metrics?: Record<string, number | null>;
    coaches?: Array<{ name: string; email: string; goal?: string | null }>;
    currentProgram?: { title: string; goal?: string | null; coachName?: string | null } | null;
  } | null;
};

type MembershipRow = {
  id: number;
  planName: string;
  status: string;
  daysRemaining?: number | null;
  effectiveEndsAt?: string | null;
  renewsAt?: string | null;
};

type MembershipsPayload = {
  memberships?: {
    data: MembershipRow[];
  };
};

type ProfileWearablesPayload = {
  summary?: {
    totalConnections?: number | null;
    healthyConnections?: number | null;
    attentionRequired?: number | null;
  };
  connections?: {
    data: Array<{
      id: number;
      providerLabel: string;
      status: string;
      lastSyncedAt?: string | null;
    }>;
  };
};

export default function ProfileScreen() {
  const { token, user, signOut } = useAuth();
  const [progress, setProgress] = useState<ProfileProgress | null>(null);
  const [memberships, setMemberships] = useState<MembershipRow[]>([]);
  const [wearables, setWearables] = useState<ProfileWearablesPayload | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadProfile = useCallback(async () => {
    if (!token) {
      return;
    }

    setIsLoading(true);
    setError(null);

    const failures: string[] = [];
    const requests: Array<Promise<void>> = [];

    if (user?.primaryRole === 'athlete') {
      requests.push(
        apiRequest<ProfileProgress>('/api/v1/progress', undefined, token)
          .then((response) => setProgress(response.data))
          .catch((loadError) => {
            failures.push(apiErrorMessage(loadError, 'Could not load progress.'));
          }),
      );
    }

    requests.push(
      apiRequest<MembershipsPayload>('/api/v1/memberships', undefined, token)
        .then((response) => setMemberships(response.data.memberships?.data ?? []))
        .catch((loadError) => {
          failures.push(apiErrorMessage(loadError, 'Could not load memberships.'));
        }),
    );

    requests.push(
      apiRequest<ProfileWearablesPayload>('/api/v1/wearables', undefined, token)
        .then((response) => setWearables(response.data))
        .catch((loadError) => {
          failures.push(apiErrorMessage(loadError, 'Could not load devices.'));
        }),
    );

    await Promise.all(requests);
    setError(failures.length ? failures.join('\n') : null);
    setIsLoading(false);
  }, [token, user?.primaryRole]);

  useFocusEffect(
    useCallback(() => {
      void loadProfile();
    }, [loadProfile]),
  );

  async function logout() {
    await signOut();
    router.replace('/login');
  }

  if (isLoading && !progress && !memberships.length && !wearables) {
    return <LoadingState label="Loading profile..." />;
  }

  const currentMembership = memberships[0] ?? null;
  const wearableConnections = wearables?.connections?.data ?? [];

  return (
    <Screen>
      <AppHeader title="Profile" eyebrow="Account" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />

      {error ? <ErrorState title="Profile partially loaded" body={error} onRetry={loadProfile} /> : null}

      <Card style={styles.profileCard}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user?.name?.slice(0, 2).toUpperCase() ?? 'TL'}</Text>
        </View>
        <Text style={styles.name}>{user?.name ?? 'Throughline user'}</Text>
        <Text style={styles.note}>{user?.email ?? 'No email on file'}</Text>
        <Text style={styles.cardTitle}>{user?.primaryRole ?? 'user'}</Text>
      </Card>

      <View style={styles.grid}>
        <MetricTile label="Role" value={user?.primaryRole ?? 'user'} icon="account-badge-outline" />
        <MetricTile label="Phone" value={user?.phone ? 'Set' : 'Missing'} icon="phone-outline" />
        <MetricTile label="Devices" value={wearables?.summary?.totalConnections ?? 0} icon="watch-variant" />
        <MetricTile label="Membership" value={currentMembership?.status ?? 'none'} icon="check-decagram-outline" />
      </View>

      <SectionTitle eyebrow="Membership" title="Subscription" />
      <Card style={styles.infoCard}>
        <Text style={styles.cardTitle}>{currentMembership?.planName ?? 'No current plan'}</Text>
        <Text style={styles.note}>
          {currentMembership
            ? `${currentMembership.daysRemaining ?? 0} day(s) left · ends ${currentMembership.effectiveEndsAt ?? currentMembership.renewsAt ?? 'not set'}`
            : 'Your active subscription will appear here after billing setup.'}
        </Text>
      </Card>

      <SectionTitle eyebrow="Devices" title="Health connections" />
      <Card style={styles.infoCard}>
        {wearableConnections.length ? (
          wearableConnections.slice(0, 4).map((connection) => (
            <View key={connection.id} style={styles.connectionRow}>
              <View style={styles.flexOne}>
                <Text style={styles.connectionName}>{connection.providerLabel}</Text>
                <Text style={styles.note}>{connection.lastSyncedAt ?? 'Not synced yet'}</Text>
              </View>
              <Text style={styles.connectionStatus}>{connection.status}</Text>
            </View>
          ))
        ) : (
          <Text style={styles.note}>No watch or wearable connection is linked yet.</Text>
        )}
      </Card>

      {progress?.athleteProfile?.metrics ? (
        <>
          <SectionTitle eyebrow="Performance" title="Your latest profile" />
          <View style={styles.grid}>
            <MetricTile label="Weight" value={progress.athleteProfile.metrics.latestWeightKg ?? '--'} detail="kg" icon="scale-bathroom" />
            <MetricTile label="Completion" value={`${progress.athleteProfile.metrics.completionRate ?? 0}%`} icon="check-circle-outline" />
            <MetricTile label="Protein" value={progress.athleteProfile.metrics.averageProteinGrams ?? '--'} detail="avg grams" icon="food-steak" />
            <MetricTile label="Check-ins" value={progress.athleteProfile.metrics.checkInsThisWeek ?? 0} detail="this week" icon="clipboard-check-outline" />
          </View>
        </>
      ) : null}

      {progress?.athleteProfile?.currentProgram ? (
        <Card style={styles.infoCard}>
          <Text style={styles.cardTitle}>Current program</Text>
          <Text style={styles.name}>{progress.athleteProfile.currentProgram.title}</Text>
          <Text style={styles.note}>
            Coach: {progress.athleteProfile.currentProgram.coachName ?? 'Not assigned'}
          </Text>
          {progress.athleteProfile.currentProgram.goal ? (
            <Text style={styles.note}>{progress.athleteProfile.currentProgram.goal}</Text>
          ) : null}
        </Card>
      ) : null}

      <SectionTitle eyebrow="Settings" title="Account actions" note="Profile editing stays in the web account settings for this build." />
      <PrimaryButton label="Log out" onPress={logout} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  profileCard: {
    alignItems: 'center',
    gap: 10,
  },
  infoCard: {
    gap: 8,
  },
  avatar: {
    width: 86,
    height: 86,
    borderRadius: 43,
    backgroundColor: colors.green,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
  },
  name: {
    color: colors.ink,
    fontSize: 24,
    fontWeight: '900',
    textAlign: 'center',
  },
  cardTitle: {
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    textAlign: 'center',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  flexOne: {
    flex: 1,
  },
  connectionRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 12,
    justifyContent: 'space-between',
  },
  connectionName: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
  },
  connectionStatus: {
    color: colors.green,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
});
