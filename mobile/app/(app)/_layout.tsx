import { Redirect, Tabs } from 'expo-router';

import { useAuth } from '@/auth/auth-context';
import { LoadingState, MobileShell, TabMark } from '@/components/mobile-ui';
import { colors } from '@/theme';

export default function AppTabs() {
  const { token, user, isLoading, signOut } = useAuth();

  if (isLoading) {
    return <LoadingState />;
  }

  if (!token) {
    return <Redirect href="/login" />;
  }

  return (
    <MobileShell user={user} onSignOut={signOut}>
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.green,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: {
          height: 78,
          paddingTop: 7,
          paddingBottom: 10,
          borderTopColor: colors.border,
          backgroundColor: '#ffffff',
        },
        tabBarLabelStyle: {
          fontSize: 10,
          fontWeight: '800',
        },
      }}
    >
      <Tabs.Screen
        name="home"
        options={{ title: 'Home', tabBarIcon: ({ focused }) => <TabMark label="H" focused={focused} /> }}
      />
      <Tabs.Screen
        name="calendar"
        options={{ title: 'Schedule', tabBarIcon: ({ focused }) => <TabMark label="C" focused={focused} /> }}
      />
      <Tabs.Screen
        name="progress"
        options={{ title: 'Health', tabBarIcon: ({ focused }) => <TabMark label="+" focused={focused} /> }}
      />
      <Tabs.Screen
        name="wearables"
        options={{ href: null }}
      />
      <Tabs.Screen
        name="messages"
        options={{ title: 'Messages', tabBarIcon: ({ focused }) => <TabMark label="M" focused={focused} /> }}
      />
      <Tabs.Screen
        name="profile"
        options={{ title: 'Profile', tabBarIcon: ({ focused }) => <TabMark label="P" focused={focused} /> }}
      />
      <Tabs.Screen name="workout/[id]" options={{ href: null, tabBarStyle: { display: 'none' } }} />
      <Tabs.Screen name="programs/[id]" options={{ href: null, tabBarStyle: { display: 'none' } }} />
    </Tabs>
    </MobileShell>
  );
}
