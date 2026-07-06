import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Image, Linking, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { WebView } from 'react-native-webview';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, Glyph, LoadingState, Pill, Screen, SecondaryButton, SectionTitle } from '@/components/mobile-ui';
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
  const [isSaving, setIsSaving] = useState(false);
  const [statusText, setStatusText] = useState<string | null>(null);
  const [journalNotes, setJournalNotes] = useState('');

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

  async function saveSets(): Promise<boolean> {
    if (!token || !id) {
      setStatusText('You need to log in again before saving this workout.');

      return false;
    }

    setIsSaving(true);
    setStatusText('Saving sets...');

    try {
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

      return true;
    } catch (error) {
      setStatusText(apiErrorMessage(error, 'Could not save sets.'));

      return false;
    } finally {
      setIsSaving(false);
    }
  }

  async function complete(status: 'completed' | 'partial' | 'missed') {
    if (!token || !id) {
      return;
    }

    const saved = await saveSets();

    if (!saved) {
      return;
    }

    setIsSaving(true);
    setStatusText('Saving workout status...');

    try {
      const response = await apiRequest<WorkoutExecution>(`/api/v1/training/sessions/${id}/complete`, {
        method: 'POST',
        body: JSON.stringify({
          completion_status: status,
          performed_at: new Date().toISOString(),
          notes: journalNotes.trim() || (status === 'missed' ? 'Marked missed from mobile.' : 'Saved from mobile.'),
        }),
      }, token);
      setWorkout(response.data);
      setRows(response.data.setLogs.map((row) => ({ ...row, completed: Boolean(row.completedAt) })));
      setStatusText(`Workout ${status}.`);
    } catch (error) {
      setStatusText(apiErrorMessage(error, 'Could not update workout status.'));
    } finally {
      setIsSaving(false);
    }
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
    <Screen
      footer={
        <View style={styles.footerActions}>
          <Pressable
            onPress={() => setCurrentExercise(Math.max(0, currentExercise - 1))}
            style={[styles.footerCircle, currentExercise === 0 && styles.footerCircleDisabled]}
            disabled={currentExercise === 0}
          >
            <Text style={styles.footerIcon}>{'<'}</Text>
          </Pressable>
          <Pressable
            disabled={isSaving}
            onPress={() => {
              void saveSets();
            }}
            style={[styles.footerPrimary, isSaving && styles.footerPrimaryDisabled]}
          >
            <Text style={styles.footerPrimaryText}>{isSaving ? 'Saving' : 'Save'}</Text>
          </Pressable>
          <Pressable
            disabled={isSaving}
            onPress={() => {
              void complete('completed');
            }}
            style={[styles.footerPrimary, isSaving && styles.footerPrimaryDisabled]}
          >
            <Text style={styles.footerPrimaryText}>Complete</Text>
          </Pressable>
          <Pressable
            onPress={() => setCurrentExercise(Math.min(workout.exercises.length - 1, currentExercise + 1))}
            style={[
              styles.footerCircle,
              currentExercise === workout.exercises.length - 1 && styles.footerCircleDisabled,
            ]}
            disabled={currentExercise === workout.exercises.length - 1}
          >
            <Text style={styles.footerIcon}>{'>'}</Text>
          </Pressable>
        </View>
      }
    >
      <AppHeader title="Workout" eyebrow={workout.session.scheduledDate ?? 'Training'} onBack={() => router.back()} />

      <View style={styles.hero}>
        <Text style={styles.heroEyebrow}>{workout.program.title}</Text>
        <Text style={styles.heroTitle}>{workout.session.title}</Text>
        <Text style={styles.heroNote}>Coach: {workout.coach.name} - {workout.session.scheduledDate}</Text>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.exerciseRail}>
        {workout.exercises.map((item, index) => {
          const isActive = index === currentExercise;

          return (
            <Pressable
              key={`${item.name}-${index}`}
              onPress={() => setCurrentExercise(index)}
              style={[styles.railItem, isActive && styles.railItemActive]}
            >
              <Text style={[styles.railNumber, isActive && styles.railTextActive]}>{index + 1}</Text>
              <Text style={[styles.railLabel, isActive && styles.railTextActive]} numberOfLines={1}>{item.name}</Text>
            </Pressable>
          );
        })}
      </ScrollView>

      {mediaUrl ? <MovementMedia title={exercise.name} url={mediaUrl} /> : null}

      <Card style={styles.exerciseCard}>
        <View style={styles.exerciseHeader}>
          <View style={styles.flexOne}>
            <Text style={styles.exerciseTitle}>{exercise.name}</Text>
            <Text style={styles.note}>{exercise.note ?? exercise.prescription ?? exercise.target ?? 'Complete the prescribed work.'}</Text>
          </View>
          <View style={styles.exerciseBadges}>
            <Pill tone="gold">{exercise.section ?? 'work'}</Pill>
            {exercise.supersetLabel ? <Pill>{exercise.supersetLabel}</Pill> : null}
          </View>
        </View>

        <View style={styles.actionRow}>
          {mediaUrl ? <SecondaryButton label="Open media" icon="play-box-outline" onPress={() => Linking.openURL(mediaUrl)} /> : null}
          <SecondaryButton
            label="Opt out"
            icon="close-circle-outline"
            onPress={() => {
              void complete('missed');
            }}
          />
        </View>
      </Card>

      <Card style={styles.journalCard}>
        <Text style={styles.exerciseTitle}>Journal</Text>
        <Text style={styles.note}>Add quick context for your coach. These notes save when you complete or opt out.</Text>
        <TextInput
          multiline
          onChangeText={setJournalNotes}
          placeholder="Energy, soreness, pain, substitution, or anything your coach should know."
          style={styles.journalInput}
          value={journalNotes}
        />
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
              maxLength={2}
              onChangeText={(value) => {
                const actualRpe = normalizeRpeInput(value);

                if (Number(value) > 10) {
                  setStatusText('RPE is capped at 10.');
                }

                updateRow(row, { actualRpe });
              }}
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

    </Screen>
  );
}

function MovementMedia({ title, url }: { title: string; url: string }) {
  const embedUrl = getEmbedUrl(url);

  if (isImageUrl(url)) {
    return (
      <View style={styles.mediaHero}>
        <Image source={{ uri: url }} style={styles.mediaImage} />
        <View style={styles.mediaOverlay}>
          <Glyph label="IMG" />
          <Text style={styles.mediaTitle} numberOfLines={2}>{title}</Text>
        </View>
      </View>
    );
  }

  if (embedUrl) {
    return (
      <View style={styles.mediaHero}>
        <WebView
          allowsFullscreenVideo
          mediaPlaybackRequiresUserAction
          source={{ uri: embedUrl }}
          style={styles.mediaWebView}
        />
      </View>
    );
  }

  if (isVideoUrl(url)) {
    return (
      <View style={styles.mediaHero}>
        <WebView
          allowsFullscreenVideo
          mediaPlaybackRequiresUserAction
          originWhitelist={['*']}
          source={{
            html: `<html><body style="margin:0;background:#102820;"><video controls playsinline style="width:100%;height:100%;object-fit:cover;"><source src="${url}"></video></body></html>`,
          }}
          style={styles.mediaWebView}
        />
      </View>
    );
  }

  return (
    <Pressable onPress={() => Linking.openURL(url)} style={styles.mediaFallback}>
      <Glyph label="URL" />
      <Text style={styles.mediaTitle} numberOfLines={2}>{title}</Text>
      <Text style={styles.mediaNote}>Open movement media</Text>
    </Pressable>
  );
}

function getEmbedUrl(url: string) {
  const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?/]+)/);

  if (youtubeMatch?.[1]) {
    return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
  }

  const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);

  if (vimeoMatch?.[1]) {
    return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
  }

  return null;
}

function isImageUrl(url: string) {
  return /\.(png|jpe?g|gif|webp|avif)(\?.*)?$/i.test(url);
}

function isVideoUrl(url: string) {
  return /\.(mp4|mov|m4v|webm)(\?.*)?$/i.test(url);
}

function normalizeRpeInput(value: string) {
  const parsed = Number.parseInt(value, 10);

  if (!Number.isFinite(parsed)) {
    return null;
  }

  return Math.max(1, Math.min(10, parsed));
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: colors.greenDark,
    borderRadius: radius.xl,
    padding: 20,
    gap: 8,
    borderColor: 'rgba(168, 255, 47, 0.28)',
    borderWidth: 1,
  },
  heroEyebrow: {
    color: colors.green,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  heroTitle: {
    color: colors.ink,
    fontSize: 30,
    fontWeight: '900',
    letterSpacing: -1.1,
  },
  heroNote: {
    color: colors.panelMuted,
    fontSize: 15,
  },
  exerciseRail: {
    gap: 8,
    paddingRight: 18,
  },
  railItem: {
    minWidth: 86,
    maxWidth: 136,
    borderRadius: 20,
    paddingHorizontal: 12,
    paddingVertical: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderWidth: 1,
  },
  railItemActive: {
    backgroundColor: colors.green,
    borderColor: colors.green,
  },
  railNumber: {
    color: colors.muted,
    fontSize: 16,
    fontWeight: '900',
  },
  railLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '800',
  },
  railTextActive: {
    color: colors.panelDark,
  },
  mediaHero: {
    minHeight: 168,
    borderRadius: radius.xl,
    backgroundColor: colors.panel,
    overflow: 'hidden',
  },
  mediaImage: {
    minHeight: 212,
    width: '100%',
  },
  mediaOverlay: {
    position: 'absolute',
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    justifyContent: 'flex-end',
    gap: 8,
    padding: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.25)',
  },
  mediaWebView: {
    height: 212,
    backgroundColor: colors.panel,
  },
  mediaFallback: {
    minHeight: 168,
    borderRadius: radius.xl,
    padding: 20,
    justifyContent: 'flex-end',
    gap: 8,
    backgroundColor: colors.panel,
  },
  mediaTitle: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
    letterSpacing: -0.7,
  },
  mediaNote: {
    color: '#d6e5e5',
    fontSize: 14,
    fontWeight: '800',
  },
  exerciseCard: {
    gap: 16,
  },
  journalCard: {
    gap: 12,
  },
  journalInput: {
    minHeight: 104,
    borderRadius: 18,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: colors.panelDark,
    color: colors.ink,
    fontSize: 16,
    lineHeight: 22,
    padding: 14,
    textAlignVertical: 'top',
  },
  exerciseHeader: {
    gap: 12,
    alignItems: 'flex-start',
  },
  flexOne: {
    flex: 1,
  },
  exerciseTitle: {
    color: colors.ink,
    fontSize: 24,
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
  exerciseBadges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
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
    backgroundColor: 'rgba(168, 255, 47, 0.14)',
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
    backgroundColor: colors.panelDark,
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
    backgroundColor: colors.panelDark,
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
    color: colors.panelDark,
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
    gap: 10,
    alignItems: 'center',
  },
  footerCircle: {
    width: 52,
    height: 52,
    borderRadius: 26,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.cardRaised,
    borderColor: colors.border,
    borderWidth: 1,
  },
  footerCircleDisabled: {
    opacity: 0.35,
  },
  footerPrimary: {
    flex: 1,
    minHeight: 52,
    borderRadius: 26,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.green,
  },
  footerPrimaryDisabled: {
    opacity: 0.55,
  },
  footerPrimaryText: {
    color: colors.panelDark,
    fontSize: 15,
    fontWeight: '900',
  },
  footerIcon: {
    color: colors.ink,
    fontSize: 30,
    fontWeight: '900',
  },
});
