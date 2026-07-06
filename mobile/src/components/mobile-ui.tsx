import { router, type Href } from 'expo-router';
import { createContext, PropsWithChildren, ReactNode, useContext, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
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
import type { TrainingSessionSummary, Viewer } from '@/types/api';

type IconName = string;

type MobileMenuContextValue = {
  openMenu: () => void;
};

const MobileMenuContext = createContext<MobileMenuContextValue | null>(null);

export function MobileShell({
  children,
  user,
  onSignOut,
}: PropsWithChildren<{
  user: Viewer | null;
  onSignOut: () => Promise<void>;
}>) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const role = user?.primaryRole ?? 'athlete';
  const navItems: Array<{ label: string; hint: string; href: Href; token: string }> = [
    { label: role === 'coach' ? 'Coach home' : 'Home', hint: 'Today and next action', href: '/home', token: 'H' },
    { label: 'Schedule', hint: 'Calendar and daily work', href: '/calendar', token: 'CAL' },
    { label: 'Health', hint: 'Progress and check-ins', href: '/progress', token: 'HLT' },
    { label: 'Devices', hint: 'WHOOP and Health Connect', href: '/wearables', token: 'DEV' },
    { label: 'Messages', hint: 'Coach-athlete thread', href: '/messages', token: 'MSG' },
    { label: 'Profile', hint: 'Account and logout', href: '/profile', token: 'ME' },
  ];

  async function logout() {
    setIsMenuOpen(false);
    await onSignOut();
    router.replace('/login');
  }

  return (
    <MobileMenuContext.Provider value={{ openMenu: () => setIsMenuOpen(true) }}>
      <View style={styles.shellRoot}>
        {children}
        <Modal animationType="fade" transparent visible={isMenuOpen} onRequestClose={() => setIsMenuOpen(false)}>
          <View style={styles.drawerLayer}>
            <Pressable style={styles.drawerScrim} onPress={() => setIsMenuOpen(false)} />
            <View style={styles.drawerPanel}>
              <View style={styles.drawerBrand}>
                <View style={styles.drawerLogo}>
                  <Text style={styles.drawerLogoText}>TL</Text>
                </View>
                <View style={styles.drawerBrandCopy}>
                  <Text style={styles.drawerEyebrow}>{role === 'coach' ? 'Coach app' : 'Athlete app'}</Text>
                  <Text style={styles.drawerName} numberOfLines={1}>{user?.name ?? 'Throughline'}</Text>
                  <Text style={styles.drawerEmail} numberOfLines={1}>{user?.email ?? 'No email'}</Text>
                </View>
              </View>

              <View style={styles.drawerNav}>
                {navItems.map((item) => (
                  <Pressable
                    key={item.label}
                    onPress={() => {
                      setIsMenuOpen(false);
                      router.push(item.href);
                    }}
                    style={({ pressed }) => [styles.drawerItem, pressed && styles.pressedCard]}
                  >
                    <Glyph label={item.token} tone={item.href === '/wearables' ? 'gold' : 'neutral'} />
                    <View style={styles.drawerItemCopy}>
                      <Text style={styles.drawerItemLabel}>{item.label}</Text>
                      <Text style={styles.drawerItemHint}>{item.hint}</Text>
                    </View>
                    <Text style={styles.drawerArrow}>{'>'}</Text>
                  </Pressable>
                ))}
              </View>

              <Pressable onPress={logout} style={styles.drawerLogout}>
                <Glyph label="OUT" tone="danger" />
                <Text style={styles.drawerLogoutText}>Log out</Text>
              </Pressable>
            </View>
          </View>
        </Modal>
      </View>
    </MobileMenuContext.Provider>
  );
}

export function Glyph({
  label,
  tone = 'neutral',
}: {
  label: string;
  tone?: 'neutral' | 'green' | 'gold' | 'danger';
}) {
  return (
    <View
      style={[
        styles.glyph,
        tone === 'green' && styles.glyphGreen,
        tone === 'gold' && styles.glyphGold,
        tone === 'danger' && styles.glyphDanger,
      ]}
    >
      <Text
        style={[
          styles.glyphText,
          tone === 'green' && styles.glyphTextGreen,
          tone === 'gold' && styles.glyphTextGold,
          tone === 'danger' && styles.glyphTextDanger,
        ]}
      >
        {label.slice(0, 3).toUpperCase()}
      </Text>
    </View>
  );
}

export function TabMark({ label, focused }: { label: string; focused: boolean }) {
  return (
    <View style={[styles.tabMark, focused && styles.tabMarkActive]}>
      <Text style={[styles.tabMarkText, focused && styles.tabMarkTextActive]}>{label}</Text>
    </View>
  );
}

export function Screen({
  children,
  footer,
  padded = true,
}: PropsWithChildren<{ footer?: ReactNode; padded?: boolean }>) {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <View style={styles.screenFrame}>
        <ScrollView
          contentContainerStyle={[
            styles.screenContent,
            footer ? styles.screenContentWithFooter : null,
            !padded && styles.flushScreen,
          ]}
          showsVerticalScrollIndicator={false}
        >
          {children}
        </ScrollView>
        {footer ? <View style={styles.screenFooter}>{footer}</View> : null}
      </View>
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
  const menu = useContext(MobileMenuContext);
  const hasBackAction = Boolean(onBack);

  return (
    <View style={styles.appHeader}>
      <Pressable onPress={hasBackAction ? onBack : menu?.openMenu} style={styles.headerIcon}>
        <Text style={styles.headerIconText}>{hasBackAction ? '<' : 'MENU'}</Text>
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
      <Glyph label="OK" tone="green" />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.note}>{body}</Text>
    </Card>
  );
}

export function ErrorState({
  title = 'Could not load',
  body,
  onRetry,
}: {
  title?: string;
  body: string;
  onRetry?: () => void;
}) {
  return (
    <Card style={styles.empty}>
      <Glyph label="!" tone="danger" />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.note}>{body}</Text>
      {onRetry ? <PrimaryButton label="Try again" onPress={onRetry} /> : null}
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
      {icon ? <Glyph label={iconLabel(icon)} /> : null}
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
      <Glyph label={iconLabel(icon)} tone="green" />
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
      <Glyph label={iconLabel(icon)} />
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
        <Glyph label={hasMedia ? 'VID' : 'SET'} tone="green" />
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
        {hasMedia ? <Text style={styles.mediaHint}>Media attached</Text> : <Text style={styles.mediaMuted}>No media</Text>}
        {canOpen ? <Text style={styles.openHint}>Open</Text> : null}
      </View>
    </Pressable>
  );
}

function iconLabel(icon: IconName) {
  const labels: Record<string, string> = {
    'account-badge-outline': 'ID',
    'account-circle-outline': 'ME',
    'account-group-outline': 'ATH',
    'alert-circle-outline': '!',
    'bed-outline': 'SLP',
    'calendar-check-outline': 'CAL',
    'calendar-clock': 'CAL',
    'calendar-clock-outline': 'CAL',
    'chart-box-outline': 'CHT',
    'check-circle-outline': 'OK',
    'check-decagram-outline': 'OK',
    'clipboard-check-outline': 'LOG',
    'clipboard-text-outline': 'LOG',
    'close-circle-outline': 'X',
    'food-steak': 'FOOD',
    'heart-pulse': 'HR',
    'phone-outline': 'TEL',
    'play-box-outline': 'VID',
    'scale-bathroom': 'KG',
    'shoe-print': 'STEP',
    target: 'GO',
    'timer-sand': 'TIME',
    'watch-variant': 'DEV',
    waveform: 'HRV',
  };

  return labels[icon] ?? icon.slice(0, 3);
}

const styles = StyleSheet.create({
  shellRoot: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
    backgroundColor: colors.background,
  },
  screenFrame: {
    flex: 1,
  },
  screenContent: {
    padding: 18,
    paddingBottom: 112,
    gap: 16,
  },
  screenContentWithFooter: {
    paddingBottom: 154,
  },
  screenFooter: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: 18,
    paddingTop: 12,
    paddingBottom: 18,
    backgroundColor: 'rgba(5, 8, 9, 0.96)',
    borderTopColor: colors.border,
    borderTopWidth: 1,
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
    backgroundColor: colors.cardRaised,
    borderColor: colors.border,
    borderWidth: 1,
  },
  headerIconText: {
    color: colors.ink,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.6,
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
    color: colors.panelDark,
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
    backgroundColor: colors.panel,
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
    backgroundColor: 'rgba(248, 198, 75, 0.14)',
    color: colors.gold,
  },
  glyph: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.panel,
    borderColor: colors.border,
    borderWidth: 1,
  },
  glyphGreen: {
    backgroundColor: colors.greenSoft,
    borderColor: 'rgba(168, 255, 47, 0.45)',
  },
  glyphGold: {
    backgroundColor: 'rgba(248, 198, 75, 0.14)',
    borderColor: 'rgba(248, 198, 75, 0.45)',
  },
  glyphDanger: {
    backgroundColor: 'rgba(255, 107, 87, 0.14)',
    borderColor: 'rgba(255, 107, 87, 0.45)',
  },
  glyphText: {
    color: colors.ink,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  glyphTextGreen: {
    color: colors.green,
  },
  glyphTextGold: {
    color: colors.gold,
  },
  glyphTextDanger: {
    color: colors.danger,
  },
  tabMark: {
    minWidth: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.panel,
  },
  tabMarkActive: {
    minWidth: 52,
    backgroundColor: colors.green,
  },
  tabMarkText: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
  },
  tabMarkTextActive: {
    color: colors.panelDark,
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
    color: colors.panelDark,
    fontSize: 16,
    fontWeight: '900',
  },
  secondaryButton: {
    flexDirection: 'row',
    gap: 8,
    borderRadius: 18,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: colors.cardRaised,
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
    backgroundColor: colors.panelDark,
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
  metricRowLabel: {
    flex: 1,
    color: colors.ink,
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  metricRowValueBlock: {
    alignItems: 'flex-end',
  },
  metricRowValue: {
    color: colors.ink,
    fontSize: 28,
    fontWeight: '900',
  },
  metricRowTarget: {
    color: colors.panelMuted,
    fontSize: 13,
    fontWeight: '800',
  },
  sessionCard: {
    backgroundColor: colors.card,
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
  drawerLayer: {
    flex: 1,
    flexDirection: 'row',
  },
  drawerScrim: {
    ...StyleSheet.absoluteFill,
    backgroundColor: 'rgba(0, 0, 0, 0.62)',
  },
  drawerPanel: {
    width: '82%',
    maxWidth: 340,
    backgroundColor: colors.panelDark,
    paddingTop: 54,
    paddingHorizontal: 18,
    paddingBottom: 24,
    gap: 20,
    borderTopRightRadius: 34,
    borderBottomRightRadius: 34,
    ...shadow,
  },
  drawerBrand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    paddingBottom: 18,
  },
  drawerLogo: {
    width: 58,
    height: 58,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.green,
  },
  drawerLogoText: {
    color: colors.panelDark,
    fontSize: 18,
    fontWeight: '900',
  },
  drawerBrandCopy: {
    flex: 1,
  },
  drawerEyebrow: {
    color: colors.gold,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1.6,
    textTransform: 'uppercase',
  },
  drawerName: {
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
  },
  drawerEmail: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  drawerNav: {
    gap: 8,
  },
  drawerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 22,
    padding: 10,
  },
  drawerItemCopy: {
    flex: 1,
  },
  drawerItemLabel: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
  },
  drawerItemHint: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  drawerArrow: {
    color: colors.muted,
    fontSize: 24,
    fontWeight: '900',
  },
  drawerLogout: {
    marginTop: 'auto',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 22,
    backgroundColor: 'rgba(255, 107, 87, 0.12)',
    padding: 12,
  },
  drawerLogoutText: {
    color: colors.danger,
    fontSize: 17,
    fontWeight: '900',
  },
});
