import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { EmptyState, LoadingState, Pill, Screen, SectionTitle, SessionCard } from '@/components/mobile-ui';
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

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    setIsLoading(true);
    const response = await apiRequest<CalendarPayload>(
      `/api/v1/app/calendar?month=${month}&date=${selectedDate}`,
      undefined,
      token,
    );
    setCalendar(response.data);
    setIsLoading(false);
  }, [month, selectedDate, token]);

  useFocusEffect(
    useCallback(() => {
      let active = true;

      load().catch(() => {
        if (active) {
          setIsLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [load]),
  );

  if (isLoading || !calendar) {
    return <LoadingState label="Loading schedule..." />;
  }

  return (
    <Screen>
      <SectionTitle
        eyebrow="Calendar"
        title="Daily schedule"
        note="Pick a day to see only the workouts assigned to that date."
      />

      <View style={styles.monthControls}>
        <Pressable onPress={() => setMonth(addMonths(month, -1))} style={styles.monthButton}>
          <Text style={styles.monthButtonText}>Prev</Text>
        </Pressable>
        <Text style={styles.monthLabel}>{calendar.monthLabel}</Text>
        <Pressable onPress={() => setMonth(addMonths(month, 1))} style={styles.monthButton}>
          <Text style={styles.monthButtonText}>Next</Text>
        </Pressable>
      </View>

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
            <Text style={[styles.dayMeta, day.isSelected && styles.daySelectedText]}>
              {day.sessionCount ? `${day.sessionCount} session` : 'Rest'}
            </Text>
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

      <Pressable onPress={() => router.push('/home')} style={styles.backButton}>
        <Text style={styles.backButtonText}>Back to home</Text>
      </Pressable>
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
    borderRadius: 16,
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  monthButtonText: {
    color: colors.ink,
    fontWeight: '900',
  },
  monthLabel: {
    flex: 1,
    color: colors.ink,
    fontSize: 18,
    fontWeight: '900',
    textAlign: 'center',
  },
  legend: {
    flexDirection: 'row',
    gap: 8,
  },
  calendarGrid: {
    borderRadius: radius.lg,
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
    padding: 12,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  weekday: {
    width: '13.4%',
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    textAlign: 'center',
    textTransform: 'uppercase',
  },
  dayCell: {
    width: '13.4%',
    minHeight: 58,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    borderColor: colors.border,
    borderWidth: 1,
  },
  dayCellMuted: {
    opacity: 0.35,
  },
  dayCellWorkout: {
    backgroundColor: colors.greenSoft,
    borderColor: '#bcebd5',
  },
  dayCellSelected: {
    backgroundColor: colors.green,
    borderColor: colors.green,
  },
  dayNumber: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
  },
  dayMeta: {
    color: colors.muted,
    fontSize: 9,
    fontWeight: '800',
    textAlign: 'center',
  },
  daySelectedText: {
    color: '#ffffff',
  },
  backButton: {
    alignItems: 'center',
    paddingVertical: 12,
  },
  backButtonText: {
    color: colors.green,
    fontWeight: '900',
  },
});
