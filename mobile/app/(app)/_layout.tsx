import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Redirect, Tabs } from 'expo-router';

import { useAuth } from '@/auth/auth-context';
import { LoadingState } from '@/components/mobile-ui';
import { colors } from '@/theme';

type TabIconName = keyof typeof MaterialCommunityIcons.glyphMap;

function TabIcon({ name, focused }: { name: TabIconName; focused: boolean }) {
  return (
    <MaterialCommunityIcons
      name={name}
      size={focused ? 27 : 24}
      color={focused ? colors.green : colors.muted}
    />
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
          height: 84,
          paddingTop: 8,
          paddingBottom: 12,
          borderTopColor: colors.border,
          backgroundColor: '#ffffff',
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '800',
        },
      }}
    >
      <Tabs.Screen
        name="home"
        options={{ title: 'Home', tabBarIcon: ({ focused }) => <TabIcon name="home-variant-outline" focused={focused} /> }}
      />
      <Tabs.Screen
        name="calendar"
        options={{ title: 'Schedule', tabBarIcon: ({ focused }) => <TabIcon name="calendar-month-outline" focused={focused} /> }}
      />
      <Tabs.Screen
        name="progress"
        options={{ title: 'Health', tabBarIcon: ({ focused }) => <TabIcon name="heart-pulse" focused={focused} /> }}
      />
      <Tabs.Screen
        name="wearables"
        options={{ title: 'Devices', tabBarIcon: ({ focused }) => <TabIcon name="watch-variant" focused={focused} /> }}
      />
      <Tabs.Screen
        name="messages"
        options={{ title: 'Messages', tabBarIcon: ({ focused }) => <TabIcon name="message-text-outline" focused={focused} /> }}
      />
      <Tabs.Screen
        name="profile"
        options={{ title: 'Profile', tabBarIcon: ({ focused }) => <TabIcon name="account-circle-outline" focused={focused} /> }}
      />
      <Tabs.Screen name="workout/[id]" options={{ href: null }} />
      <Tabs.Screen name="programs/[id]" options={{ href: null }} />
    </Tabs>
  );
}
