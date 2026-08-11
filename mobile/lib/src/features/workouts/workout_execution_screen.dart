import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

class WorkoutExecutionScreen extends ConsumerStatefulWidget {
  const WorkoutExecutionScreen({super.key, required this.workoutId});

  final int workoutId;

  @override
  ConsumerState<WorkoutExecutionScreen> createState() =>
      _WorkoutExecutionScreenState();
}

class _WorkoutExecutionScreenState
    extends ConsumerState<WorkoutExecutionScreen> {
  final _notes = TextEditingController();
  final _duration = TextEditingController();
  final _rpe = TextEditingController();
  final List<_SetDraft> _sets = [];
  bool _hydrated = false;
  bool _saving = false;
  int? _syncVersion;

  @override
  void dispose() {
    _notes.dispose();
    _duration.dispose();
    _rpe.dispose();
    for (final set in _sets) {
      set.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final result = ref.watch(workoutProvider(widget.workoutId));
    return Scaffold(
      appBar: AppBar(
        title: const Text('Workout'),
        actions: [
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close_rounded),
          ),
        ],
      ),
      body: result.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ContentColumn(
          children: [
            ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(workoutProvider(widget.workoutId)),
            ),
          ],
        ),
        data: (workout) {
          if (!_hydrated) {
            WidgetsBinding.instance.addPostFrameCallback(
              (_) => _hydrate(workout),
            );
            return const LoadingPanel();
          }
          final session = workout.object('session');
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 132),
            children: [
              PageIntro(
                eyebrow: session.text('focus', 'Training'),
                title: session.text('title', 'Workout'),
                body: workout.text(
                  'coach_notes',
                  session.text(
                    'coach_notes',
                    'Execute the prescription and record what actually happened.',
                  ),
                ),
              ),
              const SizedBox(height: 16),
              if (session.text('media_url').isNotEmpty) ...[
                _MediaHero(url: session.text('media_url')),
                const SizedBox(height: 16),
              ],
              PremiumCard(
                accent: ThroughlineColors.gold,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'SESSION JOURNAL',
                      style: Theme.of(context).textTheme.labelMedium?.copyWith(
                        color: ThroughlineColors.gold,
                        letterSpacing: 1.7,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: _notes,
                      minLines: 2,
                      maxLines: 5,
                      decoration: const InputDecoration(
                        labelText: 'Notes for your coach',
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _duration,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Minutes',
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: _rpe,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Session RPE',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              ..._groupedExercises().entries.map(
                (group) => _ExerciseSection(
                  title: group.key,
                  sets: group.value,
                  onTimer: (seconds) => _showTimer(context, seconds),
                ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 12),
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _saving ? null : () => _save('partial'),
                icon: const Icon(Icons.save_outlined),
                label: const Text('Save draft'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: FilledButton.icon(
                onPressed: _saving ? null : () => _save('completed'),
                icon: _saving
                    ? const SizedBox.square(
                        dimension: 17,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.check_rounded),
                label: const Text('Complete'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _hydrate(JsonMap workout) {
    if (_hydrated || !mounted) return;
    final execution = workout.object('execution');
    final existingSets = execution.maps('sets');
    final exercises = workout.object('session').maps('exercises');
    _notes.text = execution.text('notes');
    _duration.text = execution['duration_minutes']?.toString() ?? '';
    _rpe.text = execution['rpe']?.toString() ?? '';
    _syncVersion = execution['sync_version'] as int?;

    for (
      var exerciseIndex = 0;
      exerciseIndex < exercises.length;
      exerciseIndex++
    ) {
      final exercise = exercises[exerciseIndex];
      final setCount = (exercise['sets'] as int? ?? 1).clamp(1, 20);
      for (var setNumber = 1; setNumber <= setCount; setNumber++) {
        JsonMap? existing;
        for (final candidate in existingSets) {
          if (candidate['exercise_id'] == exercise['id'] &&
              candidate['set_number'] == setNumber) {
            existing = candidate;
            break;
          }
        }
        _sets.add(
          _SetDraft.fromExercise(exercise, exerciseIndex, setNumber, existing),
        );
      }
    }
    setState(() => _hydrated = true);
  }

  Map<String, List<_SetDraft>> _groupedExercises() {
    final groups = <String, List<_SetDraft>>{};
    for (final set in _sets) {
      groups.putIfAbsent(set.exerciseName, () => []).add(set);
    }
    return groups;
  }

  Future<void> _save(String status) async {
    setState(() => _saving = true);
    final payload = <String, dynamic>{
      'status': status,
      'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      'duration_minutes': int.tryParse(_duration.text),
      'rpe': int.tryParse(_rpe.text),
      'sync_version': _syncVersion,
      'sets': _sets.map((set) => set.toJson()).toList(),
    };
    try {
      await ref
          .read(apiClientProvider)
          .put('/app/workouts/${widget.workoutId}/execution', data: payload);
      await ref.read(appDatabaseProvider).removeWorkoutDraft(widget.workoutId);
      ref.invalidate(workoutProvider(widget.workoutId));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              status == 'completed' ? 'Workout completed.' : 'Workout saved.',
            ),
          ),
        );
        if (status == 'completed') Navigator.pop(context);
      }
    } on ApiFailure catch (failure) {
      if (failure.isConflict) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(failure.message),
              backgroundColor: ThroughlineColors.danger,
            ),
          );
        }
      } else {
        await ref
            .read(appDatabaseProvider)
            .saveWorkoutDraft(
              workoutId: widget.workoutId,
              payload: jsonEncode(payload),
            );
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'No connection. Draft saved safely on this device.',
              ),
            ),
          );
        }
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _showTimer(BuildContext context, int seconds) =>
      showModalBottomSheet<void>(
        context: context,
        builder: (context) => _RestTimer(seconds: seconds),
      );
}

class _SetDraft {
  _SetDraft({
    required this.exerciseId,
    required this.exerciseIndex,
    required this.exerciseName,
    required this.setNumber,
    required this.targetReps,
    required this.targetLoad,
    required this.restSeconds,
    required this.actualReps,
    required this.actualLoad,
    required this.rpe,
    required this.completed,
    required this.notes,
    required this.mediaUrl,
    required this.cue,
  });

  factory _SetDraft.fromExercise(
    JsonMap exercise,
    int index,
    int setNumber,
    JsonMap? existing,
  ) => _SetDraft(
    exerciseId: exercise['id'] as int?,
    exerciseIndex: index,
    exerciseName: exercise.text('name', 'Exercise'),
    setNumber: setNumber,
    targetReps: exercise.text('reps'),
    targetLoad: exercise.text('load'),
    restSeconds: exercise['rest_seconds'] as int? ?? 0,
    actualReps: TextEditingController(
      text: existing?['actual_reps']?.toString() ?? '',
    ),
    actualLoad: TextEditingController(
      text: existing?['actual_load']?.toString() ?? '',
    ),
    rpe: TextEditingController(text: existing?['rpe']?.toString() ?? ''),
    completed: existing?['completed'] as bool? ?? false,
    notes: existing?.text('notes') ?? '',
    mediaUrl: exercise.text('media_url'),
    cue: exercise.text('notes'),
  );

  final int? exerciseId;
  final int exerciseIndex;
  final String exerciseName;
  final int setNumber;
  final String targetReps;
  final String targetLoad;
  final int restSeconds;
  final TextEditingController actualReps;
  final TextEditingController actualLoad;
  final TextEditingController rpe;
  bool completed;
  final String notes;
  final String mediaUrl;
  final String cue;

  JsonMap toJson() => {
    'exercise_id': exerciseId,
    'exercise_index': exerciseIndex,
    'exercise_name': exerciseName,
    'set_number': setNumber,
    'target_reps': targetReps.isEmpty ? null : targetReps,
    'target_load': targetLoad.isEmpty ? null : targetLoad,
    'target_rest_seconds': restSeconds,
    'actual_reps': num.tryParse(actualReps.text),
    'actual_load': num.tryParse(actualLoad.text),
    'rpe': int.tryParse(rpe.text),
    'notes': notes.isEmpty ? null : notes,
    'completed': completed,
  };

  void dispose() {
    actualReps.dispose();
    actualLoad.dispose();
    rpe.dispose();
  }
}

class _ExerciseSection extends StatefulWidget {
  const _ExerciseSection({
    required this.title,
    required this.sets,
    required this.onTimer,
  });
  final String title;
  final List<_SetDraft> sets;
  final ValueChanged<int> onTimer;

  @override
  State<_ExerciseSection> createState() => _ExerciseSectionState();
}

class _ExerciseSectionState extends State<_ExerciseSection> {
  @override
  Widget build(BuildContext context) {
    final first = widget.sets.first;
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: PremiumCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    widget.title,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                if (first.mediaUrl.isNotEmpty)
                  IconButton(
                    onPressed: () => launchUrl(
                      Uri.parse(first.mediaUrl),
                      mode: LaunchMode.externalApplication,
                    ),
                    icon: const Icon(Icons.play_circle_outline_rounded),
                  ),
              ],
            ),
            if (first.cue.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                first.cue,
                style: const TextStyle(
                  color: ThroughlineColors.muted,
                  height: 1.4,
                ),
              ),
            ],
            const SizedBox(height: 16),
            const Row(
              children: [
                SizedBox(
                  width: 36,
                  child: Text(
                    'SET',
                    style: TextStyle(
                      color: ThroughlineColors.muted,
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                ),
                Expanded(
                  child: Text(
                    'REPS',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: ThroughlineColors.muted,
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                ),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'LOAD',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: ThroughlineColors.muted,
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                ),
                SizedBox(width: 8),
                SizedBox(
                  width: 62,
                  child: Text(
                    'RPE',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: ThroughlineColors.muted,
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            for (final set in widget.sets) ...[
              Row(
                children: [
                  SizedBox(
                    width: 36,
                    child: Checkbox(
                      value: set.completed,
                      onChanged: (value) =>
                          setState(() => set.completed = value ?? false),
                    ),
                  ),
                  Expanded(
                    child: TextField(
                      controller: set.actualReps,
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      decoration: InputDecoration(
                        hintText: set.targetReps.isEmpty ? '—' : set.targetReps,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextField(
                      controller: set.actualLoad,
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      decoration: InputDecoration(
                        hintText: set.targetLoad.isEmpty ? '—' : set.targetLoad,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    width: 62,
                    child: TextField(
                      controller: set.rpe,
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      decoration: const InputDecoration(hintText: '—'),
                    ),
                  ),
                ],
              ),
              if (set.restSeconds > 0)
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton.icon(
                    onPressed: () => widget.onTimer(set.restSeconds),
                    icon: const Icon(Icons.timer_outlined, size: 18),
                    label: Text('${set.restSeconds}s rest'),
                  ),
                ),
              const SizedBox(height: 8),
            ],
          ],
        ),
      ),
    );
  }
}

class _MediaHero extends StatefulWidget {
  const _MediaHero({required this.url});
  final String url;

  @override
  State<_MediaHero> createState() => _MediaHeroState();
}

class _MediaHeroState extends State<_MediaHero> {
  VideoPlayerController? _controller;

  bool get _isDirectVideo => RegExp(
    r'\.(mp4|mov|m4v)(\?.*)?$',
    caseSensitive: false,
  ).hasMatch(widget.url);

  @override
  void initState() {
    super.initState();
    if (_isDirectVideo) {
      _controller = VideoPlayerController.networkUrl(Uri.parse(widget.url))
        ..initialize().then((_) {
          if (mounted) setState(() {});
        });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    if (controller != null && controller.value.isInitialized) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: AspectRatio(
          aspectRatio: controller.value.aspectRatio,
          child: Stack(
            fit: StackFit.expand,
            children: [
              VideoPlayer(controller),
              Center(
                child: IconButton.filled(
                  onPressed: () => setState(
                    () => controller.value.isPlaying
                        ? controller.pause()
                        : controller.play(),
                  ),
                  icon: Icon(
                    controller.value.isPlaying
                        ? Icons.pause_rounded
                        : Icons.play_arrow_rounded,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }
    return PremiumCard(
      accent: ThroughlineColors.cyan,
      child: Column(
        children: [
          const Icon(
            Icons.ondemand_video_rounded,
            size: 42,
            color: ThroughlineColors.cyan,
          ),
          const SizedBox(height: 10),
          const Text(
            'Exercise media',
            style: TextStyle(fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: () => launchUrl(
              Uri.parse(widget.url),
              mode: LaunchMode.externalApplication,
            ),
            icon: const Icon(Icons.open_in_new_rounded),
            label: const Text('Open video'),
          ),
        ],
      ),
    );
  }
}

class _RestTimer extends StatefulWidget {
  const _RestTimer({required this.seconds});
  final int seconds;

  @override
  State<_RestTimer> createState() => _RestTimerState();
}

class _RestTimerState extends State<_RestTimer> {
  late int _remaining = widget.seconds;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remaining <= 1) {
        timer.cancel();
        setState(() => _remaining = 0);
      } else {
        setState(() => _remaining--);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(28),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Text(
          'REST TIMER',
          style: TextStyle(
            color: ThroughlineColors.muted,
            letterSpacing: 2,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 18),
        Text(
          '$_remaining',
          style: Theme.of(context).textTheme.displayLarge?.copyWith(
            fontWeight: FontWeight.w900,
            color: ThroughlineColors.lime,
          ),
        ),
        const SizedBox(height: 12),
        FilledButton(
          onPressed: () => Navigator.pop(context),
          child: Text(_remaining == 0 ? 'Continue' : 'Skip rest'),
        ),
      ],
    ),
  );
}
