import { useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, EmptyState, ErrorState, Pill, Screen, SectionTitle, SessionCard } from '@/components/mobile-ui';
import { colors, radius } from '@/theme';
import type { CalendarPayload } from '@/types/api';

function toMonthKey(date: Date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function toDateKey(date: Date) {
  return `${toMonthKey(date)}-${String(date.getDate()).padStart(2, '0')}`;
}

function addMonths(month: string, offset: number) {
  const [year, monthIndex] = month.split('-').map(Number);
  return toMonthKey(new Date(year, monthIndex - 1 + offset, 1));
}

export default function CalendarScreen() {
  const { token, user } = useAuth();
  const today = toDateKey(new Date());
  const [month, setMonth] = useState(toMonthKey(new Date()));
  const [selectedDate, setSelectedDate] = useState(today);
  const [calendar, setCalendar] = useState<CalendarPayload | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const hasLoadedRef = useRef(false);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    if (hasLoadedRef.current) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }
    setError(null);

    try {
      const response = await apiRequest<CalendarPayload>(
        `/api/v1/app/calendar?month=${month}&date=${selectedDate}`,
        undefined,
        token,
      );
      setCalendar(response.data);
      hasLoadedRef.current = true;
    } catch (loadError) {
      setError(apiErrorMessage(loadError, 'Could not load schedule.'));
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, [month, selectedDate, token]);

  function moveMonth(offset: number) {
    const nextMonth = addMonths(month, offset);
    setMonth(nextMonth);
    setSelectedDate(`${nextMonth}-01`);
  }

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  if (isLoading && !calendar) {
    return (
      <Screen>
        <AppHeader title="Schedule" eyebrow="Workout calendar" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />
        <SectionTitle eyebrow="Calendar" title="Loading schedule" note="Preparing your assigned sessions." />
      </Screen>
    );
  }

  if (error && !calendar) {
    return (
      <Screen>
        <AppHeader title="Schedule" eyebrow="Workout calendar" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />
        <ErrorState body={error} onRetry={load} />
      </Screen>
    );
  }

  if (!calendar) {
    return (
      <Screen>
        <AppHeader title="Schedule" eyebrow="Workout calendar" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />
        <SectionTitle eyebrow="Calendar" title="Loading schedule" note="Preparing your assigned sessions." />
      </Screen>
    );
  }

  return (
    <Screen>
      <AppHeader title="Schedule" eyebrow="Workout calendar" rightLabel={user?.name?.slice(0, 2).toUpperCase() ?? 'TL'} />

      <SectionTitle
        eyebrow="Calendar"
        title="Daily schedule"
        note="Pick a day to see only the workouts assigned to that date."
      />

      <View style={styles.monthControls}>
        <Pressable onPress={() => moveMonth(-1)} style={styles.monthButton}>
          <Text style={styles.monthArrow}>{'<'}</Text>
        </Pressable>
        <Text style={styles.monthLabel}>{calendar.monthLabel}</Text>
        <Pressable onPress={() => moveMonth(1)} style={styles.monthButton}>
          <Text style={styles.monthArrow}>{'>'}</Text>
        </Pressable>
      </View>
      {isRefreshing ? <Text style={styles.refreshing}>Updating schedule...</Text> : null}
      {error ? <ErrorState title="Schedule problem" body={error} onRetry={load} /> : null}

      <View style={styles.legend}>
        <Pill>Rest</Pill>
        <Pill tone="green">Workout</Pill>
      </View>

      <View style={styles.calendarGrid}>
        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((label) => (
          <Text key={label} style={styles.weekday}>{label}</Text>
        ))}
        {calendar.days.map((day) => (
          <Pressable
            key={day.date}
            onPress={() => {
              setSelectedDate(day.date);
              setMonth(day.date.slice(0, 7));
            }}
            style={[
              styles.dayCell,
              !day.isCurrentMonth && styles.dayCellMuted,
              day.isSelected && styles.dayCellSelected,
              day.sessionCount > 0 && !day.isSelected && styles.dayCellWorkout,
            ]}
          >
            <Text style={[styles.dayNumber, day.isSelected && styles.daySelectedText]}>{day.dayNumber}</Text>
            {day.sessionCount ? (
              <View style={[styles.dayDot, day.isSelected && styles.dayDotSelected]}>
                <Text style={[styles.dayDotText, day.isSelected && styles.daySelectedDotText]}>{day.sessionCount}</Text>
              </View>
            ) : null}
          </Pressable>
        ))}
      </View>

      <SectionTitle eyebrow="Selected day" title={calendar.selectedDate} />
      {calendar.selectedDaySessions.length ? (
        calendar.selectedDaySessions.map((session) => (
          <SessionCard key={session.id} session={session} canOpen={user?.primaryRole === 'athlete'} />
        ))
      ) : (
        <EmptyState title="No assigned training" body="This day is clear. Move to another day or wait for your coach to assign work." />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  monthControls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  monthButton: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: colors.cardRaised,
    borderColor: colors.border,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  monthLabel: {
    flex: 1,
    color: colors.ink,
    fontSize: 18,
    fontWeight: '900',
    textAlign: 'center',
  },
  monthArrow: {
    color: colors.ink,
    fontSize: 24,
    fontWeight: '900',
  },
  refreshing: {
    color: colors.green,
    fontSize: 12,
    fontWeight: '900',
    textAlign: 'center',
    textTransform: 'uppercase',
  },
  legend: {
    flexDirection: 'row',
    gap: 8,
  },
  calendarGrid: {
    borderRadius: radius.lg,
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderWidth: 1,
    padding: 10,
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  weekday: {
    width: '14.2857%',
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    textAlign: 'center',
    textTransform: 'uppercase',
  },
  dayCell: {
    width: '14.2857%',
    minHeight: 50,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    borderColor: 'transparent',
    borderWidth: 1,
  },
  dayCellMuted: {
    opacity: 0.35,
  },
  dayCellWorkout: {
    backgroundColor: colors.greenSoft,
    borderColor: 'rgba(168, 255, 47, 0.45)',
  },
  dayCellSelected: {
    backgroundColor: colors.green,
    borderColor: colors.green,
  },
  dayNumber: {
    color: colors.ink,
    fontSize: 16,
    fontWeight: '900',
  },
  dayDot: {
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.greenSoft,
  },
  dayDotSelected: {
    backgroundColor: colors.panelDark,
  },
  dayDotText: {
    color: colors.green,
    fontSize: 10,
    fontWeight: '900',
    textAlign: 'center',
  },
  daySelectedDotText: {
    color: colors.green,
  },
  daySelectedText: {
    color: colors.panelDark,
  },
});
