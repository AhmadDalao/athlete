import { router } from 'expo-router';
import { PropsWithChildren } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleProp,
  StyleSheet,
  Text,
  View,
  ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, radius, shadow } from '@/theme';
import type { TrainingSessionSummary } from '@/types/api';

export function Screen({ children }: PropsWithChildren) {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <ScrollView contentContainerStyle={styles.screenContent} showsVerticalScrollIndicator={false}>
        {children}
      </ScrollView>
    </SafeAreaView>
  );
}

export function Card({
  children,
  style,
}: PropsWithChildren<{
  style?: StyleProp<ViewStyle>;
}>) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function SectionTitle({ eyebrow, title, note }: { eyebrow?: string; title: string; note?: string }) {
  return (
    <View style={styles.sectionHeader}>
      {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
      <Text style={styles.sectionTitle}>{title}</Text>
      {note ? <Text style={styles.note}>{note}</Text> : null}
    </View>
  );
}

export function Pill({ children, tone = 'neutral' }: PropsWithChildren<{ tone?: 'neutral' | 'green' | 'gold' }>) {
  return <Text style={[styles.pill, tone === 'green' && styles.greenPill, tone === 'gold' && styles.goldPill]}>{children}</Text>;
}

export function LoadingState({ label = 'Loading Throughline...' }: { label?: string }) {
  return (
    <SafeAreaView style={styles.centered}>
      <ActivityIndicator size="large" color={colors.green} />
      <Text style={styles.loadingText}>{label}</Text>
    </SafeAreaView>
  );
}

export function EmptyState({ title, body }: { title: string; body: string }) {
  return (
    <Card style={styles.empty}>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.note}>{body}</Text>
    </Card>
  );
}

export function PrimaryButton({
  label,
  onPress,
  disabled = false,
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
}) {
  return (
    <Pressable onPress={onPress} disabled={disabled} style={[styles.primaryButton, disabled && styles.disabledButton]}>
      <Text style={styles.primaryButtonText}>{label}</Text>
    </Pressable>
  );
}

export function SecondaryButton({
  label,
  onPress,
}: {
  label: string;
  onPress: () => void;
}) {
  return (
    <Pressable onPress={onPress} style={styles.secondaryButton}>
      <Text style={styles.secondaryButtonText}>{label}</Text>
    </Pressable>
  );
}

export function MetricTile({ label, value, detail }: { label: string; value: string | number; detail?: string }) {
  return (
    <Card style={styles.metricTile}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Text style={styles.metricValue}>{value}</Text>
      {detail ? <Text style={styles.note}>{detail}</Text> : null}
    </Card>
  );
}

export function SessionCard({ session, canOpen = true }: { session: TrainingSessionSummary; canOpen?: boolean }) {
  const preview = session.exercisePreview?.join(' | ') || `${session.exerciseCount ?? 0} exercise(s)`;

  return (
    <Pressable
      disabled={!canOpen}
      onPress={() => router.push({ pathname: '/workout/[id]', params: { id: String(session.id) } })}
      style={styles.sessionCard}
    >
      <View style={styles.sessionTop}>
        <Text style={styles.sessionTitle}>{session.title}</Text>
        <Pill tone={session.completionStatus === 'completed' ? 'green' : 'gold'}>
          {session.completionStatus ?? 'scheduled'}
        </Pill>
      </View>
      <Text style={styles.note}>{session.focus ?? 'Training'} - {session.scheduledDate ?? 'No date'}</Text>
      <Text style={styles.sessionPreview}>{preview}</Text>
      {session.mediaCount || session.videoUrl ? <Text style={styles.mediaHint}>Media attached</Text> : null}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.background,
  },
  screenContent: {
    padding: 20,
    paddingBottom: 110,
    gap: 18,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
    gap: 12,
  },
  loadingText: {
    color: colors.muted,
    fontSize: 15,
  },
  card: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radius.lg,
    padding: 18,
    ...shadow,
  },
  sectionHeader: {
    gap: 5,
  },
  eyebrow: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  sectionTitle: {
    color: colors.ink,
    fontSize: 28,
    fontWeight: '900',
    letterSpacing: -0.8,
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
  },
  pill: {
    alignSelf: 'flex-start',
    overflow: 'hidden',
    borderRadius: 999,
    backgroundColor: '#f2eee7',
    color: colors.ink,
    paddingHorizontal: 10,
    paddingVertical: 5,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
  greenPill: {
    backgroundColor: colors.greenSoft,
    color: colors.green,
  },
  goldPill: {
    backgroundColor: '#fff0c7',
    color: '#815b00',
  },
  empty: {
    gap: 8,
    alignItems: 'flex-start',
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: colors.ink,
  },
  primaryButton: {
    borderRadius: 18,
    backgroundColor: colors.green,
    paddingVertical: 15,
    paddingHorizontal: 18,
    alignItems: 'center',
  },
  primaryButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '900',
  },
  secondaryButton: {
    borderRadius: 18,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: '#ffffff',
    paddingVertical: 14,
    paddingHorizontal: 18,
    alignItems: 'center',
  },
  secondaryButtonText: {
    color: colors.ink,
    fontSize: 15,
    fontWeight: '800',
  },
  disabledButton: {
    opacity: 0.5,
  },
  metricTile: {
    flex: 1,
    minWidth: 145,
    gap: 8,
  },
  metricLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  metricValue: {
    color: colors.ink,
    fontSize: 34,
    fontWeight: '900',
    letterSpacing: -1,
  },
  sessionCard: {
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radius.lg,
    padding: 18,
    gap: 10,
  },
  sessionTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
  sessionTitle: {
    color: colors.ink,
    flex: 1,
    fontSize: 19,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  sessionPreview: {
    color: colors.ink,
    fontSize: 15,
    lineHeight: 22,
  },
  mediaHint: {
    color: colors.blue,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
});
