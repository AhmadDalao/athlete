import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, MetricTile, PrimaryButton, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

type ProfileProgress = {
  athleteProfile?: {
    metrics?: Record<string, number | null>;
    coaches?: Array<{ name: string; email: string; goal?: string | null }>;
    currentProgram?: { title: string; goal?: string | null; coachName?: string | null } | null;
  } | null;
};

export default function ProfileScreen() {
  const { token, user, signOut } = useAuth();
  const [progress, setProgress] = useState<ProfileProgress | null>(null);

  useFocusEffect(
    useCallback(() => {
      let active = true;

      async function load() {
        if (!token || user?.primaryRole !== 'athlete') {
          return;
        }

        const response = await apiRequest<ProfileProgress>('/api/v1/progress', undefined, token);

        if (active) {
          setProgress(response.data);
        }
      }

      load().catch(() => undefined);

      return () => {
        active = false;
      };
    }, [token, user?.primaryRole]),
  );

  async function logout() {
    await signOut();
    router.replace('/login');
  }

  return (
    <Screen>
      <AppHeader title="Profile" eyebrow="Account" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />

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
      </View>

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
});
