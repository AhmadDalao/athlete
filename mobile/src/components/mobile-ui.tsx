import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
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

type IconName = keyof typeof MaterialCommunityIcons.glyphMap;

export function Screen({ children, padded = true }: PropsWithChildren<{ padded?: boolean }>) {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <ScrollView contentContainerStyle={[styles.screenContent, !padded && styles.flushScreen]} showsVerticalScrollIndicator={false}>
        {children}
      </ScrollView>
    </SafeAreaView>
  );
}

export function AppHeader({
  title,
  eyebrow,
  rightLabel,
  onBack,
}: {
  title: string;
  eyebrow?: string;
  rightLabel?: string;
  onBack?: () => void;
}) {
  const hasBackAction = Boolean(onBack);

  return (
    <View style={styles.appHeader}>
      <Pressable onPress={hasBackAction ? onBack : undefined} style={styles.headerIcon}>
        <Ionicons name={hasBackAction ? 'chevron-back' : 'menu'} size={24} color={colors.ink} />
      </Pressable>
      <View style={styles.headerText}>
        {eyebrow ? <Text style={styles.headerEyebrow}>{eyebrow}</Text> : null}
        <Text style={styles.headerTitle} numberOfLines={1}>{title}</Text>
      </View>
      <View style={styles.headerAvatar}>
        <Text style={styles.headerAvatarText}>{rightLabel ?? 'TL'}</Text>
      </View>
    </View>
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
      <View style={styles.emptyIcon}>
        <MaterialCommunityIcons name="clipboard-text-outline" size={26} color={colors.green} />
      </View>
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
  icon,
}: {
  label: string;
  onPress: () => void;
  icon?: IconName;
}) {
  return (
    <Pressable onPress={onPress} style={styles.secondaryButton}>
      {icon ? <MaterialCommunityIcons name={icon} size={18} color={colors.ink} /> : null}
      <Text style={styles.secondaryButtonText}>{label}</Text>
    </Pressable>
  );
}

export function MetricTile({
  label,
  value,
  detail,
  icon = 'chart-box-outline',
}: {
  label: string;
  value: string | number;
  detail?: string;
  icon?: IconName;
}) {
  return (
    <Card style={styles.metricTile}>
      <View style={styles.metricIcon}>
        <MaterialCommunityIcons name={icon} size={22} color={colors.green} />
      </View>
      <Text style={styles.metricLabel} numberOfLines={2}>{label}</Text>
      <Text style={styles.metricValue}>{value}</Text>
      {detail ? <Text style={styles.note}>{detail}</Text> : null}
    </Card>
  );
}

export function SignalRing({
  label,
  value,
  detail,
  tone = 'green',
}: {
  label: string;
  value: string | number;
  detail?: string;
  tone?: 'green' | 'blue' | 'gold';
}) {
  const toneColor = tone === 'blue' ? colors.blue : tone === 'gold' ? colors.gold : colors.green;

  return (
    <View style={styles.signalItem}>
      <View style={[styles.signalRing, { borderColor: toneColor }]}>
        <Text style={styles.signalValue}>{value}</Text>
      </View>
      <Text style={styles.signalLabel}>{label}</Text>
      {detail ? <Text style={styles.signalDetail}>{detail}</Text> : null}
    </View>
  );
}

export function MetricRow({
  icon,
  label,
  value,
  target,
}: {
  icon: IconName;
  label: string;
  value: string | number;
  target?: string | number | null;
}) {
  return (
    <Card style={styles.metricRow}>
      <View style={styles.metricRowIcon}>
        <MaterialCommunityIcons name={icon} size={24} color="#b8c2c4" />
      </View>
      <Text style={styles.metricRowLabel}>{label}</Text>
      <View style={styles.metricRowValueBlock}>
        <Text style={styles.metricRowValue}>{value}</Text>
        {target ? <Text style={styles.metricRowTarget}>{target}</Text> : null}
      </View>
    </Card>
  );
}

export function SessionCard({ session, canOpen = true }: { session: TrainingSessionSummary; canOpen?: boolean }) {
  const preview = session.exercisePreview?.join(' | ') || `${session.exerciseCount ?? 0} exercise(s)`;
  const hasMedia = Boolean(session.mediaCount || session.videoUrl);
  const statusTone = session.completionStatus === 'completed' ? 'green' : session.completionStatus === 'scheduled' ? 'neutral' : 'gold';

  return (
    <Pressable
      disabled={!canOpen}
      onPress={() => router.push({ pathname: '/workout/[id]', params: { id: String(session.id) } })}
      style={({ pressed }) => [styles.sessionCard, pressed && canOpen && styles.pressedCard]}
    >
      <View style={styles.sessionTop}>
        <View style={styles.sessionIcon}>
          <MaterialCommunityIcons name={hasMedia ? 'play-circle-outline' : 'dumbbell'} size={24} color={colors.green} />
        </View>
        <View style={styles.sessionCopy}>
          <Text style={styles.sessionTitle} numberOfLines={2}>{session.title}</Text>
          <Text style={styles.note}>{session.focus ?? 'Training'} - {session.scheduledDate ?? 'No date'}</Text>
        </View>
        <Pill tone={statusTone}>
          {session.completionStatus ?? 'scheduled'}
        </Pill>
      </View>
      <Text style={styles.sessionPreview}>{preview}</Text>
      <View style={styles.sessionFooter}>
        {hasMedia ? (
          <Text style={styles.mediaHint}>
            <MaterialCommunityIcons name="image-multiple-outline" size={13} /> Media attached
          </Text>
        ) : (
          <Text style={styles.mediaMuted}>No media</Text>
        )}
        {canOpen ? <Text style={styles.openHint}>Open</Text> : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.background,
  },
  screenContent: {
    padding: 18,
    paddingBottom: 112,
    gap: 16,
  },
  flushScreen: {
    paddingHorizontal: 0,
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
    padding: 16,
    ...shadow,
  },
  appHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 2,
  },
  headerIcon: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
  },
  headerText: {
    flex: 1,
  },
  headerEyebrow: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  headerTitle: {
    color: colors.ink,
    fontSize: 25,
    fontWeight: '900',
    letterSpacing: -0.8,
  },
  headerAvatar: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.green,
  },
  headerAvatarText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '900',
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
  emptyIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.greenSoft,
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
    flexDirection: 'row',
    gap: 8,
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
  metricIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.greenSoft,
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
    fontSize: 32,
    fontWeight: '900',
    letterSpacing: -1,
  },
  signalItem: {
    alignItems: 'center',
    gap: 7,
    flex: 1,
  },
  signalRing: {
    width: 78,
    height: 78,
    borderRadius: 39,
    borderWidth: 8,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
  },
  signalValue: {
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
  },
  signalLabel: {
    color: colors.ink,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
  },
  signalDetail: {
    color: colors.muted,
    fontSize: 11,
    textAlign: 'center',
  },
  metricRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.panel,
    borderColor: '#334144',
    shadowOpacity: 0,
    elevation: 0,
  },
  metricRowIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#223035',
  },
  metricRowLabel: {
    flex: 1,
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  metricRowValueBlock: {
    alignItems: 'flex-end',
  },
  metricRowValue: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
  },
  metricRowTarget: {
    color: '#8c9698',
    fontSize: 13,
    fontWeight: '800',
  },
  sessionCard: {
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radius.lg,
    padding: 16,
    gap: 10,
  },
  pressedCard: {
    opacity: 0.86,
    transform: [{ scale: 0.99 }],
  },
  sessionTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  sessionIcon: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.greenSoft,
  },
  sessionCopy: {
    flex: 1,
  },
  sessionTitle: {
    color: colors.ink,
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
  mediaMuted: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '800',
  },
  sessionFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  openHint: {
    color: colors.green,
    fontSize: 13,
    fontWeight: '900',
  },
});
