import { router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/auth/auth-context';
import { Card, PrimaryButton, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';

export default function ProfileScreen() {
  const { user, signOut } = useAuth();

  async function logout() {
    await signOut();
    router.replace('/login');
  }

  return (
    <Screen>
      <SectionTitle eyebrow="Profile" title={user?.name ?? 'Account'} note={user?.email ?? undefined} />

      <Card style={styles.profileCard}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user?.name?.slice(0, 2).toUpperCase() ?? 'TL'}</Text>
        </View>
        <Text style={styles.cardTitle}>{user?.primaryRole ?? 'user'}</Text>
        <Text style={styles.note}>Phone: {user?.phone ?? 'Not set'}</Text>
      </Card>

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
  cardTitle: {
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  note: {
    color: colors.muted,
    fontSize: 15,
  },
});
