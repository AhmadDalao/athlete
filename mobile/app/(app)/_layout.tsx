import { Redirect, Tabs } from 'expo-router';
import { Text } from 'react-native';

import { useAuth } from '@/auth/auth-context';
import { LoadingState } from '@/components/mobile-ui';
import { colors } from '@/theme';

function TabIcon({ label, focused }: { label: string; focused: boolean }) {
  return (
    <Text style={{ color: focused ? colors.green : colors.muted, fontSize: 18, fontWeight: '900' }}>
      {label}
    </Text>
  );
}

export default function AppTabs() {
  const { token, isLoading } = useAuth();

  if (isLoading) {
    return <LoadingState />;
  }

  if (!token) {
    return <Redirect href="/login" />;
  }

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.green,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: {
          height: 76,
          paddingTop: 8,
          paddingBottom: 10,
          borderTopColor: colors.border,
          backgroundColor: '#ffffff',
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '800',
        },
      }}
    >
      <Tabs.Screen name="home" options={{ title: 'Home', tabBarIcon: ({ focused }) => <TabIcon label="H" focused={focused} /> }} />
      <Tabs.Screen name="calendar" options={{ title: 'Schedule', tabBarIcon: ({ focused }) => <TabIcon label="S" focused={focused} /> }} />
      <Tabs.Screen name="progress" options={{ title: 'Health', tabBarIcon: ({ focused }) => <TabIcon label="P" focused={focused} /> }} />
      <Tabs.Screen name="wearables" options={{ title: 'Devices', tabBarIcon: ({ focused }) => <TabIcon label="D" focused={focused} /> }} />
      <Tabs.Screen name="messages" options={{ title: 'Messages', tabBarIcon: ({ focused }) => <TabIcon label="M" focused={focused} /> }} />
      <Tabs.Screen name="profile" options={{ title: 'Profile', tabBarIcon: ({ focused }) => <TabIcon label="A" focused={focused} /> }} />
      <Tabs.Screen name="workout/[id]" options={{ href: null }} />
      <Tabs.Screen name="programs/[id]" options={{ href: null }} />
    </Tabs>
  );
}
