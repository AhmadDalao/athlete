import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_detail_screen.dart';

class CoachAthleteDetailScreen extends ConsumerWidget {
  const CoachAthleteDetailScreen({super.key, required this.athleteId});

  final int athleteId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    final canManagePrograms = user?.can('programs.manage') == true;
    final canManageNotes = user?.can('athletes.notes') == true;
    final canReviewProgress = user?.can('progress.review') == true;
    final result = ref.watch(coachAthleteProvider(athleteId));
    return Scaffold(
      appBar: AppBar(title: const Text('Athlete review')),
      body: result.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ContentColumn(
          children: [
            ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(coachAthleteProvider(athleteId)),
            ),
          ],
        ),
        data: (data) {
          final athlete = data.object('athlete');
          final profile = data.object('profile');
          final programs = data.maps('programs');
          final workouts =
              programs
                  .expand((assignment) => assignment.maps('workouts'))
                  .toList()
                ..sort(
                  (left, right) => right
                      .text('scheduled_for')
                      .compareTo(left.text('scheduled_for')),
                );
          final progress = data.maps('progress');
          final photos = data.maps('photos');
          final records = data.maps('records');
          final notes = data.maps('notes');
          final summary = data.object('progress_summary');
          return RefreshIndicator(
            onRefresh: () =>
                ref.refresh(coachAthleteProvider(athleteId).future),
            child: ContentColumn(
              children: [
                PageIntro(
                  eyebrow: 'Athlete source of truth',
                  title: athlete.text('name', 'Athlete'),
                  body: athlete.text('email'),
                ),
                if (canReviewProgress)
                  _ProgressSummaryPanel(
                    athleteId: athleteId,
                    initialSummary: summary,
                  ),
                PremiumCard(
                  accent: ThroughlineColors.lime,
                  child: Wrap(
                    spacing: 24,
                    runSpacing: 16,
                    children: [
                      _ProfileValue(
                        label: 'Sport',
                        value: profile.text('sport', 'Not set'),
                      ),
                      _ProfileValue(
                        label: 'Level',
                        value: profile.text('level', 'Not set'),
                      ),
                      _ProfileValue(
                        label: 'Goal',
                        value: profile.text('goal', 'Not set'),
                      ),
                      _ProfileValue(
                        label: 'Programs',
                        value: programs.length.toString(),
                      ),
                    ],
                  ),
                ),
                if (canManageNotes || canReviewProgress)
                  Row(
                    children: [
                      if (canManageNotes)
                        Expanded(
                          child: FilledButton.icon(
                            onPressed: () => _addNote(context, ref, athlete),
                            icon: const Icon(Icons.note_add_outlined),
                            label: const Text('Add note'),
                          ),
                        ),
                      if (canManageNotes && canReviewProgress)
                        const SizedBox(width: 10),
                      if (canReviewProgress)
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () =>
                                _uploadPhoto(context, ref, athlete),
                            icon: const Icon(Icons.add_a_photo_outlined),
                            label: const Text('Add photo'),
                          ),
                        ),
                    ],
                  ),
                const SectionTitle('Assigned programs'),
                if (programs.isEmpty)
                  const EmptyPanel(
                    title: 'No program assigned',
                    body: 'Assign a reusable template from Programs.',
                  )
                else
                  ...programs.map((assignment) {
                    final program = assignment.object('program');
                    final completion = assignment.object('completion');
                    return PremiumCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  program.text('title', 'Program'),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                                Text(
                                  '${completion.text('percent', '0')}% complete',
                                  style: const TextStyle(
                                    color: ThroughlineColors.muted,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          StatusChip(assignment.text('status', 'active')),
                          if (canManagePrograms &&
                              assignment['can_edit'] == true)
                            IconButton(
                              onPressed: () => Navigator.push<void>(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => CoachProgramDetailScreen(
                                    programId: program['id'] as int,
                                  ),
                                ),
                              ),
                              icon: const Icon(Icons.chevron_right_rounded),
                              tooltip: 'Open athlete plan',
                            ),
                        ],
                      ),
                    );
                  }),
                const SectionTitle('Workout drill-down'),
                if (workouts.isEmpty)
                  const EmptyPanel(
                    title: 'No scheduled workouts',
                    body: 'Published plan sessions appear here.',
                  )
                else
                  ...workouts.take(30).map((workout) {
                    final session = workout.object('session');
                    final execution = workout.object('execution');
                    final sets = execution.maps('sets');
                    return PremiumCard(
                      padding: EdgeInsets.zero,
                      child: ExpansionTile(
                        title: Text(
                          session.text('title', 'Workout'),
                          style: const TextStyle(fontWeight: FontWeight.w900),
                        ),
                        subtitle: Text(
                          '${workout.text('scheduled_for')} · ${workout.text('status')}',
                        ),
                        trailing: StatusChip(
                          execution.text(
                            'status',
                            workout.text('status', 'scheduled'),
                          ),
                        ),
                        childrenPadding: const EdgeInsets.fromLTRB(
                          18,
                          0,
                          18,
                          18,
                        ),
                        children: [
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'RPE ${execution.text('rpe', '—')} · ${execution.text('duration_minutes', '—')} min · ${sets.where((set) => set['completed'] == true).length}/${sets.length} sets',
                            ),
                          ),
                          if (execution.text('notes').isNotEmpty)
                            Align(
                              alignment: Alignment.centerLeft,
                              child: Padding(
                                padding: const EdgeInsets.only(top: 8),
                                child: Text(execution.text('notes')),
                              ),
                            ),
                          ...sets.map(
                            (set) => ListTile(
                              dense: true,
                              contentPadding: EdgeInsets.zero,
                              leading: Icon(
                                set['completed'] == true
                                    ? Icons.check_circle_rounded
                                    : Icons.radio_button_unchecked,
                              ),
                              title: Text(
                                '${set.text('exercise_name')} · set ${set.text('set_number')}',
                              ),
                              subtitle: Text(
                                '${set.text('actual_reps', '—')} reps × ${set.text('actual_load', '—')} · RPE ${set.text('rpe', '—')}',
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                if (canReviewProgress) const SectionTitle('Recent progress'),
                if (canReviewProgress && progress.isEmpty)
                  const EmptyPanel(
                    title: 'No check-ins yet',
                    body:
                        'Progress entries appear here after the athlete logs them.',
                  )
                else if (canReviewProgress)
                  ...progress
                      .take(10)
                      .map(
                        (entry) => PremiumCard(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  entry.text('logged_on'),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                              Text(
                                '${entry['weight_kg'] ?? '—'} kg',
                                style: const TextStyle(
                                  color: ThroughlineColors.lime,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                              const SizedBox(width: 14),
                              Text(
                                'Energy ${entry['energy'] ?? '—'}/10',
                                style: const TextStyle(
                                  color: ThroughlineColors.muted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                if (canManageNotes) const SectionTitle('Coach notes'),
                if (canManageNotes && notes.isEmpty)
                  const EmptyPanel(
                    title: 'No coach notes',
                    body: 'Private review notes stay attached to this athlete.',
                    icon: Icons.sticky_note_2_outlined,
                  )
                else if (canManageNotes)
                  ...notes.map(
                    (note) => PremiumCard(
                      padding: const EdgeInsets.all(16),
                      accent: note['is_pinned'] == true
                          ? ThroughlineColors.gold
                          : null,
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(
                            note['is_pinned'] == true
                                ? Icons.push_pin_rounded
                                : Icons.notes_rounded,
                            color: note['is_pinned'] == true
                                ? ThroughlineColors.gold
                                : ThroughlineColors.muted,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  note.text('body'),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 5),
                                Text(
                                  '${note.object('coach').text('name', 'Coach')} · ${note.text('visibility')}',
                                  style: const TextStyle(
                                    color: ThroughlineColors.muted,
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (note['can_edit'] == true)
                            PopupMenuButton<String>(
                              onSelected: (action) => _noteAction(
                                context,
                                ref,
                                athlete,
                                note,
                                action,
                              ),
                              itemBuilder: (_) => [
                                PopupMenuItem(
                                  value: 'pin',
                                  child: Text(
                                    note['is_pinned'] == true ? 'Unpin' : 'Pin',
                                  ),
                                ),
                                const PopupMenuItem(
                                  value: 'delete',
                                  child: Text('Delete'),
                                ),
                              ],
                            ),
                        ],
                      ),
                    ),
                  ),
                if (canReviewProgress) const SectionTitle('Progress photos'),
                if (canReviewProgress && photos.isEmpty)
                  const EmptyPanel(
                    title: 'No progress photos',
                    body:
                        'Athlete and coach uploads appear here with permission controls.',
                    icon: Icons.photo_library_outlined,
                  )
                else if (canReviewProgress)
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: photos.length,
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 0.8,
                        ),
                    itemBuilder: (context, index) {
                      final photo = photos[index];
                      return Stack(
                        fit: StackFit.expand,
                        children: [
                          AuthenticatedImage(url: photo.text('url')),
                          Positioned(
                            left: 8,
                            right: 8,
                            bottom: 8,
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.black.withValues(alpha: 0.72),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                '${photo.text('category')} · ${photo.text('taken_on')}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 11,
                                ),
                              ),
                            ),
                          ),
                          if (photo['can_delete'] == true)
                            Positioned(
                              top: 8,
                              right: 8,
                              child: IconButton.filledTonal(
                                onPressed: () =>
                                    _deletePhoto(context, ref, athlete, photo),
                                icon: const Icon(
                                  Icons.delete_outline_rounded,
                                  size: 18,
                                ),
                              ),
                            ),
                        ],
                      );
                    },
                  ),
                if (canReviewProgress) const SectionTitle('Personal records'),
                if (canReviewProgress && records.isEmpty)
                  const EmptyPanel(
                    title: 'No personal records',
                    body: 'Verified strength records will appear here.',
                    icon: Icons.emoji_events_outlined,
                  )
                else if (canReviewProgress)
                  ...records.map(
                    (record) => PremiumCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.emoji_events_outlined,
                            color: ThroughlineColors.gold,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              record.text('exercise_name'),
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                          Text(
                            '${record.text('value')} ${record.text('unit')}',
                            style: const TextStyle(
                              color: ThroughlineColors.lime,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _addNote(
    BuildContext context,
    WidgetRef ref,
    JsonMap athlete,
  ) async {
    final body = TextEditingController();
    var visibility = 'private';
    var pinned = false;
    var saving = false;
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            22,
            20,
            MediaQuery.viewInsetsOf(context).bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Coach note',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: body,
                maxLines: 5,
                decoration: const InputDecoration(labelText: 'Review note'),
              ),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'private', label: Text('Private')),
                  ButtonSegment(value: 'organization', label: Text('Team')),
                ],
                selected: {visibility},
                onSelectionChanged: (value) =>
                    setModalState(() => visibility = value.first),
              ),
              SwitchListTile(
                value: pinned,
                onChanged: (value) => setModalState(() => pinned = value),
                title: const Text('Pin note'),
                contentPadding: EdgeInsets.zero,
              ),
              FilledButton(
                onPressed: saving
                    ? null
                    : () async {
                        if (body.text.trim().isEmpty) return;
                        setModalState(() => saving = true);
                        try {
                          await ref
                              .read(apiClientProvider)
                              .post(
                                '/coach/athletes/${athlete['id']}/notes',
                                data: {
                                  'body': body.text.trim(),
                                  'visibility': visibility,
                                  'is_pinned': pinned,
                                },
                              );
                          if (context.mounted) Navigator.pop(context, true);
                        } catch (error) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(error.toString())),
                            );
                            setModalState(() => saving = false);
                          }
                        }
                      },
                child: saving
                    ? const SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save note'),
              ),
            ],
          ),
        ),
      ),
    );
    body.dispose();
    if (saved == true) ref.invalidate(coachAthleteProvider(athleteId));
  }

  Future<void> _noteAction(
    BuildContext context,
    WidgetRef ref,
    JsonMap athlete,
    JsonMap note,
    String action,
  ) async {
    try {
      if (action == 'pin') {
        await ref
            .read(apiClientProvider)
            .patch(
              '/coach/athletes/${athlete['id']}/notes/${note['id']}',
              data: {'is_pinned': note['is_pinned'] != true},
            );
      } else {
        await ref
            .read(apiClientProvider)
            .delete('/coach/athletes/${athlete['id']}/notes/${note['id']}');
      }
      ref.invalidate(coachAthleteProvider(athleteId));
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }

  Future<void> _uploadPhoto(
    BuildContext context,
    WidgetRef ref,
    JsonMap athlete,
  ) async {
    final image = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 88,
      maxWidth: 1800,
    );
    if (image == null) return;
    try {
      final form = FormData.fromMap({
        'photo': await MultipartFile.fromFile(image.path, filename: image.name),
        'taken_on': DateFormat('yyyy-MM-dd').format(DateTime.now()),
        'category': 'progress',
        'visibility': 'athlete',
      });
      await ref
          .read(apiClientProvider)
          .post('/coach/athletes/${athlete['id']}/photos', data: form);
      ref.invalidate(coachAthleteProvider(athleteId));
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }

  Future<void> _deletePhoto(
    BuildContext context,
    WidgetRef ref,
    JsonMap athlete,
    JsonMap photo,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete progress photo?'),
        content: const Text('This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref
          .read(apiClientProvider)
          .delete('/coach/athletes/${athlete['id']}/photos/${photo['id']}');
      ref.invalidate(coachAthleteProvider(athleteId));
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }
}

class _ProfileValue extends StatelessWidget {
  const _ProfileValue({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => SizedBox(
    width: 130,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            color: ThroughlineColors.muted,
            fontSize: 10,
            letterSpacing: 1.4,
          ),
        ),
        const SizedBox(height: 4),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w900)),
      ],
    ),
  );
}

class _ProgressSummaryPanel extends ConsumerStatefulWidget {
  const _ProgressSummaryPanel({
    required this.athleteId,
    required this.initialSummary,
  });

  final int athleteId;
  final JsonMap initialSummary;

  @override
  ConsumerState<_ProgressSummaryPanel> createState() =>
      _ProgressSummaryPanelState();
}

class _ProgressSummaryPanelState extends ConsumerState<_ProgressSummaryPanel> {
  DateTime? _from;
  DateTime? _to;

  @override
  Widget build(BuildContext context) {
    final filtered = _from != null || _to != null;
    final result = filtered
        ? ref.watch(
            coachAthleteProgressProvider((
              athleteId: widget.athleteId,
              from: _date(_from),
              to: _date(_to),
            )),
          )
        : null;
    final summary = result?.valueOrNull ?? widget.initialSummary;
    final adherence = summary.object('adherence');
    final sets = summary.object('sets');
    final averages = summary.object('averages');
    final counts = summary.object('counts');
    final workouts = summary.maps('workout_trend');
    final period = summary.object('period');

    return PremiumCard(
      accent: ThroughlineColors.emerald,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'Performance ${period.text('from')} to ${period.text('to')}',
                  style: const TextStyle(fontWeight: FontWeight.w900),
                ),
              ),
              if (result?.isLoading == true)
                const SizedBox.square(
                  dimension: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 18,
            runSpacing: 14,
            children: [
              _SummaryMetric(
                label: 'Adherence',
                value: '${adherence.text('percent', '0')}%',
              ),
              _SummaryMetric(
                label: 'Sets',
                value: '${sets.text('percent', '0')}%',
              ),
              _SummaryMetric(label: 'Load', value: sets.text('volume', '0')),
              _SummaryMetric(
                label: 'Avg RPE',
                value: averages.text('rpe', '—'),
              ),
              _SummaryMetric(
                label: 'Duration',
                value: averages['duration_minutes'] == null
                    ? '—'
                    : '${averages['duration_minutes']} min',
              ),
              _SummaryMetric(
                label: 'Recovery',
                value:
                    'E ${averages.text('energy', '—')} · S ${averages.text('sleep_quality', '—')}',
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            '${counts.text('check_ins', '0')} check-ins · ${counts.text('records', '0')} records · ${counts.text('photos', '0')} photos',
            style: const TextStyle(color: ThroughlineColors.muted),
          ),
          if (workouts.isNotEmpty) ...[
            const SizedBox(height: 12),
            ...workouts.reversed
                .take(4)
                .map(
                  (workout) => Padding(
                    padding: const EdgeInsets.only(top: 5),
                    child: Text(
                      '${workout.text('date')} · ${workout.text('status')} · ${workout.text('sets_completed', '0')}/${workout.text('sets_total', '0')} sets · load ${workout.text('volume', '0')}',
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ),
          ],
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              OutlinedButton.icon(
                onPressed: () => _pickDate(true),
                icon: const Icon(Icons.first_page_rounded),
                label: Text(_from == null ? 'From' : _date(_from)!),
              ),
              OutlinedButton.icon(
                onPressed: () => _pickDate(false),
                icon: const Icon(Icons.last_page_rounded),
                label: Text(_to == null ? 'To' : _date(_to)!),
              ),
              if (filtered)
                TextButton(
                  onPressed: () => setState(() {
                    _from = null;
                    _to = null;
                  }),
                  child: const Text('Last 90 days'),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _pickDate(bool start) async {
    final initial = start ? _from : _to;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _from = picked;
      } else {
        _to = picked;
      }
    });
  }

  String? _date(DateTime? date) =>
      date == null ? null : DateFormat('yyyy-MM-dd').format(date);
}

class _SummaryMetric extends StatelessWidget {
  const _SummaryMetric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => SizedBox(
    width: 92,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            color: ThroughlineColors.muted,
            fontSize: 10,
            letterSpacing: 1.1,
          ),
        ),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w900)),
      ],
    ),
  );
}
