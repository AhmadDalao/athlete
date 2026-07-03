import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Linking, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { Card, LoadingState, Pill, PrimaryButton, Screen, SecondaryButton, SectionTitle } from '@/components/mobile-ui';
import { colors, radius } from '@/theme';
import type { WorkoutExecution, WorkoutSetRow } from '@/types/api';

type EditableSetRow = WorkoutSetRow & {
  completed?: boolean;
};

export default function WorkoutExecutionScreen() {
  const { token } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const [workout, setWorkout] = useState<WorkoutExecution | null>(null);
  const [rows, setRows] = useState<EditableSetRow[]>([]);
  const [currentExercise, setCurrentExercise] = useState(0);
  const [timer, setTimer] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [statusText, setStatusText] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    async function load() {
      if (!token || !id) {
        return;
      }

      const response = await apiRequest<WorkoutExecution>(
        `/api/v1/training/sessions/${id}/execution`,
        undefined,
        token,
      );

      if (active) {
        setWorkout(response.data);
        setRows(response.data.setLogs.map((row) => ({ ...row, completed: Boolean(row.completedAt) })));
        setIsLoading(false);
      }
    }

    load().catch(() => {
      if (active) {
        setIsLoading(false);
        setStatusText('This workout is not available for this account.');
      }
    });

    return () => {
      active = false;
    };
  }, [id, token]);

  useEffect(() => {
    if (timer <= 0) {
      return undefined;
    }

    const handle = setInterval(() => setTimer((value) => Math.max(0, value - 1)), 1000);

    return () => clearInterval(handle);
  }, [timer]);

  const exercise = workout?.exercises[currentExercise];
  const exerciseRows = useMemo(
    () => rows.filter((row) => row.exerciseIndex === currentExercise),
    [currentExercise, rows],
  );

  function updateRow(target: EditableSetRow, changes: Partial<EditableSetRow>) {
    setRows((current) =>
      current.map((row) =>
        row.exerciseIndex === target.exerciseIndex && row.setNumber === target.setNumber
          ? { ...row, ...changes }
          : row,
      ),
    );
  }

  async function saveSets() {
    if (!token || !id) {
      return;
    }

    setStatusText('Saving sets...');
    const response = await apiRequest<WorkoutExecution>(`/api/v1/training/sessions/${id}/sets`, {
      method: 'POST',
      body: JSON.stringify({
        sets: rows.map((row) => ({
          exercise_index: row.exerciseIndex,
          set_number: row.setNumber,
          actual_reps: row.actualReps,
          actual_load: row.actualLoad,
          actual_rpe: row.actualRpe,
          completed: Boolean(row.completed),
          notes: row.notes,
        })),
      }),
    }, token);
    setWorkout(response.data);
    setRows(response.data.setLogs.map((row) => ({ ...row, completed: Boolean(row.completedAt) })));
    setStatusText('Saved.');
  }

  async function complete(status: 'completed' | 'partial' | 'missed') {
    if (!token || !id) {
      return;
    }

    await saveSets();
    setStatusText('Saving workout status...');
    const response = await apiRequest<WorkoutExecution>(`/api/v1/training/sessions/${id}/complete`, {
      method: 'POST',
      body: JSON.stringify({
        completion_status: status,
        performed_at: new Date().toISOString(),
        notes: status === 'missed' ? 'Marked missed from mobile.' : 'Saved from mobile.',
      }),
    }, token);
    setWorkout(response.data);
    setRows(response.data.setLogs.map((row) => ({ ...row, completed: Boolean(row.completedAt) })));
    setStatusText(`Workout ${status}.`);
  }

  if (isLoading) {
    return <LoadingState label="Loading workout..." />;
  }

  if (!workout || !exercise) {
    return (
      <Screen>
        <SectionTitle eyebrow="Workout" title="Not available" note={statusText ?? 'This session cannot be opened.'} />
        <SecondaryButton label="Back" onPress={() => router.back()} />
      </Screen>
    );
  }

  const mediaUrl = exercise.mediaUrl ?? workout.session.videoUrl ?? workout.session.mediaItems?.[0]?.url;

  return (
    <Screen>
      <Pressable onPress={() => router.back()}>
        <Text style={styles.back}>Back</Text>
      </Pressable>

      <View style={styles.hero}>
        <Text style={styles.heroEyebrow}>{workout.program.title}</Text>
        <Text style={styles.heroTitle}>{workout.session.title}</Text>
        <Text style={styles.heroNote}>Coach: {workout.coach.name} - {workout.session.scheduledDate}</Text>
      </View>

      <View style={styles.exerciseRail}>
        {workout.exercises.map((item, index) => (
          <Pressable
            key={`${item.name}-${index}`}
            onPress={() => setCurrentExercise(index)}
            style={[styles.railDot, index === currentExercise && styles.railDotActive]}
          >
            <Text style={[styles.railText, index === currentExercise && styles.railTextActive]}>{index + 1}</Text>
          </Pressable>
        ))}
      </View>

      <Card style={styles.exerciseCard}>
        <View style={styles.exerciseHeader}>
          <View style={styles.flexOne}>
            <Text style={styles.exerciseTitle}>{exercise.name}</Text>
            <Text style={styles.note}>{exercise.note ?? exercise.prescription ?? exercise.target ?? 'Complete the prescribed work.'}</Text>
          </View>
          <Pill tone="gold">{exercise.section ?? 'work'}</Pill>
        </View>

        <View style={styles.actionRow}>
          {mediaUrl ? <SecondaryButton label="Media" onPress={() => Linking.openURL(mediaUrl)} /> : null}
          <SecondaryButton label="Journal" onPress={() => setStatusText('Journal notes save with workout status in this MVP.')} />
          <SecondaryButton label="Opt out" onPress={() => complete('missed')} />
        </View>
      </Card>

      <Card style={styles.setCard}>
        <View style={styles.setHeader}>
          <Text style={styles.setHead}>Set</Text>
          <Text style={styles.setHead}>Weight</Text>
          <Text style={styles.setHead}>Reps</Text>
          <Text style={styles.setHead}>RPE</Text>
        </View>

        {exerciseRows.map((row) => (
          <View key={`${row.exerciseIndex}-${row.setNumber}`} style={[styles.setRow, row.completed && styles.completedRow]}>
            <Pressable
              onPress={() => {
                updateRow(row, { completed: !row.completed });
                if (row.targetRestSeconds) {
                  setTimer(row.targetRestSeconds);
                }
              }}
              style={styles.setNumber}
            >
              <Text style={styles.setNumberText}>{row.setNumber}</Text>
            </Pressable>
            <TextInput
              onChangeText={(value) => updateRow(row, { actualLoad: value })}
              placeholder={row.targetLoad ?? '-'}
              style={styles.setInput}
              value={row.actualLoad ?? ''}
            />
            <TextInput
              keyboardType="numbers-and-punctuation"
              onChangeText={(value) => updateRow(row, { actualReps: value })}
              placeholder={row.targetReps ?? '-'}
              style={styles.setInput}
              value={row.actualReps ?? ''}
            />
            <TextInput
              keyboardType="number-pad"
              onChangeText={(value) => updateRow(row, { actualRpe: value ? Number(value) : null })}
              placeholder="-"
              style={styles.rpeInput}
              value={row.actualRpe ? String(row.actualRpe) : ''}
            />
          </View>
        ))}
      </Card>

      {timer > 0 ? (
        <Card style={styles.timerCard}>
          <Text style={styles.timerText}>Rest timer: {timer}s</Text>
        </Card>
      ) : null}

      {statusText ? <Text style={styles.status}>{statusText}</Text> : null}

      <View style={styles.footerActions}>
        <SecondaryButton label="Previous" onPress={() => setCurrentExercise(Math.max(0, currentExercise - 1))} />
        <PrimaryButton label="Save partial" onPress={saveSets} />
        <SecondaryButton
          label="Next"
          onPress={() => setCurrentExercise(Math.min(workout.exercises.length - 1, currentExercise + 1))}
        />
      </View>
      <PrimaryButton label="Complete workout" onPress={() => complete('completed')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  back: {
    color: colors.green,
    fontSize: 16,
    fontWeight: '900',
  },
  hero: {
    backgroundColor: colors.greenDark,
    borderRadius: radius.xl,
    padding: 24,
    gap: 8,
  },
  heroEyebrow: {
    color: '#bfd8cf',
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 34,
    fontWeight: '900',
    letterSpacing: -1.1,
  },
  heroNote: {
    color: '#d8eee5',
    fontSize: 15,
  },
  exerciseRail: {
    flexDirection: 'row',
    gap: 8,
  },
  railDot: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
    borderColor: colors.border,
    borderWidth: 1,
  },
  railDotActive: {
    backgroundColor: colors.green,
    borderColor: colors.green,
  },
  railText: {
    color: colors.muted,
    fontWeight: '900',
  },
  railTextActive: {
    color: '#ffffff',
  },
  exerciseCard: {
    gap: 16,
  },
  exerciseHeader: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'flex-start',
  },
  flexOne: {
    flex: 1,
  },
  exerciseTitle: {
    color: colors.ink,
    fontSize: 25,
    fontWeight: '900',
    letterSpacing: -0.6,
  },
  note: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
  },
  actionRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  setCard: {
    gap: 10,
  },
  setHeader: {
    flexDirection: 'row',
    gap: 8,
  },
  setHead: {
    flex: 1,
    color: colors.muted,
    fontSize: 12,
    fontWeight: '900',
    textAlign: 'center',
    textTransform: 'uppercase',
  },
  setRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 18,
    padding: 8,
  },
  completedRow: {
    backgroundColor: '#dbf3f4',
  },
  setNumber: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 54,
  },
  setNumberText: {
    color: colors.ink,
    fontSize: 20,
    fontWeight: '900',
  },
  setInput: {
    flex: 1,
    minHeight: 54,
    borderRadius: 14,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: '#ffffff',
    color: colors.ink,
    fontSize: 17,
    fontWeight: '800',
    paddingHorizontal: 10,
    textAlign: 'center',
  },
  rpeInput: {
    width: 58,
    minHeight: 54,
    borderRadius: 14,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: '#ffffff',
    color: colors.ink,
    fontSize: 17,
    fontWeight: '800',
    textAlign: 'center',
  },
  timerCard: {
    backgroundColor: colors.blue,
    borderColor: colors.blue,
    alignItems: 'center',
  },
  timerText: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '900',
  },
  status: {
    color: colors.green,
    fontSize: 15,
    fontWeight: '900',
    textAlign: 'center',
  },
  footerActions: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
});
