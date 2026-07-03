import { router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, MetricTile, PrimaryButton, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

export default function ProfileScreen() {
  const { user, signOut } = useAuth();

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
