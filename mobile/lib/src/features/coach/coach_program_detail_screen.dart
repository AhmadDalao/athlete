import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_editor_screen.dart';

class CoachProgramDetailScreen extends ConsumerWidget {
  const CoachProgramDetailScreen({super.key, required this.programId});

  final int programId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachProgramProvider(programId));
    return Scaffold(
      appBar: AppBar(
        title: const Text('Program builder'),
        actions: [
          if (result.valueOrNull != null)
            IconButton(
              onPressed: () => _edit(context, ref, result.valueOrNull!),
              icon: const Icon(Icons.edit_rounded),
              tooltip: 'Edit program',
            ),
        ],
      ),
      body: result.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ContentColumn(
          children: [
            ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(coachProgramProvider(programId)),
            ),
          ],
        ),
        data: (program) {
          final phases = program.maps('phases');
          final sessions = program.maps('sessions');
          final assignments = program.maps('assignments');
          return RefreshIndicator(
            onRefresh: () =>
                ref.refresh(coachProgramProvider(programId).future),
            child: ContentColumn(
              children: [
                PageIntro(
                  eyebrow: 'Program template',
                  title: program.text('title', 'Program'),
                  body: program.text('goal', 'No goal has been set.'),
                ),
                PremiumCard(
                  accent: ThroughlineColors.cyan,
                  child: Row(
                    children: [
                      StatusChip(program.text('status', 'draft')),
                      const Spacer(),
                      Text(
                        '${sessions.length} sessions',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(width: 14),
                      Text(
                        '${assignments.length} assigned',
                        style: const TextStyle(color: ThroughlineColors.muted),
                      ),
                    ],
                  ),
                ),
                SectionTitle(
                  'Phases',
                  action: TextButton.icon(
                    onPressed: () => _addPhase(context, ref),
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Add'),
                  ),
                ),
                if (phases.isEmpty)
                  const EmptyPanel(
                    title: 'No phases',
                    body: 'Add a block to organize the template.',
                  )
                else
                  PremiumCard(
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: phases
                          .map(
                            (phase) => Chip(label: Text(phase.text('title'))),
                          )
                          .toList(),
                    ),
                  ),
                SectionTitle(
                  'Sessions',
                  action: TextButton.icon(
                    onPressed: () => _openSession(context, ref, phases),
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Add'),
                  ),
                ),
                if (sessions.isEmpty)
                  const EmptyPanel(
                    title: 'No sessions',
                    body: 'Add the first scheduled training day.',
                  )
                else
                  ...sessions.map(
                    (session) => PremiumCard(
                      padding: EdgeInsets.zero,
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 18,
                          vertical: 10,
                        ),
                        leading: CircleAvatar(
                          backgroundColor: ThroughlineColors.gold.withValues(
                            alpha: 0.18,
                          ),
                          foregroundColor: ThroughlineColors.gold,
                          child: Text('+${session['day_offset'] ?? 0}'),
                        ),
                        title: Text(
                          session.text('title'),
                          style: const TextStyle(fontWeight: FontWeight.w900),
                        ),
                        subtitle: Text(
                          '${session.maps('exercises').length} exercises · ${session['estimated_minutes'] ?? '—'} min',
                        ),
                        trailing: const Icon(Icons.edit_rounded),
                        onTap: () => _openSession(
                          context,
                          ref,
                          phases,
                          session: session,
                        ),
                      ),
                    ),
                  ),
                SectionTitle(
                  'Assignments',
                  action: TextButton.icon(
                    onPressed: () => _assign(context, ref),
                    icon: const Icon(Icons.person_add_alt_1_rounded),
                    label: const Text('Assign'),
                  ),
                ),
                if (assignments.isEmpty)
                  const EmptyPanel(
                    title: 'Not assigned yet',
                    body:
                        'Assign this template to an athlete when it is ready.',
                  )
                else
                  ...assignments.map(
                    (assignment) => PremiumCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  assignment
                                      .object('athlete')
                                      .text('name', 'Athlete'),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                                Text(
                                  'Starts ${assignment.text('starts_on')}',
                                  style: const TextStyle(
                                    color: ThroughlineColors.muted,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          StatusChip(assignment.text('status', 'active')),
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

  Future<void> _edit(
    BuildContext context,
    WidgetRef ref,
    JsonMap program,
  ) async {
    final changed = await Navigator.push<int>(
      context,
      MaterialPageRoute(
        builder: (_) => CoachProgramEditorScreen(program: program),
      ),
    );
    if (changed != null) ref.invalidate(coachProgramProvider(programId));
  }

  Future<void> _addPhase(BuildContext context, WidgetRef ref) async {
    final title = TextEditingController();
    final weeks = TextEditingController();
    final created = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Add phase'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: title,
              decoration: const InputDecoration(labelText: 'Phase title'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: weeks,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Duration in weeks'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () async {
              if (title.text.trim().isEmpty) return;
              try {
                await ref
                    .read(apiClientProvider)
                    .post(
                      '/coach/programs/$programId/phases',
                      data: {
                        'title': title.text.trim(),
                        'duration_weeks': int.tryParse(weeks.text),
                      },
                    );
                if (context.mounted) Navigator.pop(context, true);
              } on ApiFailure catch (failure) {
                if (context.mounted) {
                  ScaffoldMessenger.of(
                    context,
                  ).showSnackBar(SnackBar(content: Text(failure.message)));
                }
              }
            },
            child: const Text('Add phase'),
          ),
        ],
      ),
    );
    title.dispose();
    weeks.dispose();
    if (created == true) ref.invalidate(coachProgramProvider(programId));
  }

  Future<void> _openSession(
    BuildContext context,
    WidgetRef ref,
    List<JsonMap> phases, {
    JsonMap? session,
  }) async {
    final saved = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (_) => _SessionEditor(
          programId: programId,
          phases: phases,
          session: session,
        ),
      ),
    );
    if (saved == true) ref.invalidate(coachProgramProvider(programId));
  }

  Future<void> _assign(BuildContext context, WidgetRef ref) async {
    final roster = await ref.read(coachRosterProvider.future);
    if (!context.mounted) return;
    final athletes = roster.maps('data');
    if (athletes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Invite or assign an athlete before assigning a program.',
          ),
        ),
      );
      return;
    }
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) =>
          _AssignmentSheet(programId: programId, athletes: athletes),
    );
    if (saved == true) ref.invalidate(coachProgramProvider(programId));
  }
}

class _SessionEditor extends ConsumerStatefulWidget {
  const _SessionEditor({
    required this.programId,
    required this.phases,
    this.session,
  });

  final int programId;
  final List<JsonMap> phases;
  final JsonMap? session;

  @override
  ConsumerState<_SessionEditor> createState() => _SessionEditorState();
}

class _SessionEditorState extends ConsumerState<_SessionEditor> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _focus;
  late final TextEditingController _offset;
  late final TextEditingController _minutes;
  late final TextEditingController _notes;
  late final TextEditingController _media;
  final List<_ExerciseDraft> _exercises = [];
  int? _phaseId;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final session = widget.session ?? <String, dynamic>{};
    _title = TextEditingController(text: session.text('title'));
    _focus = TextEditingController(text: session.text('focus'));
    _offset = TextEditingController(
      text: session['day_offset']?.toString() ?? '0',
    );
    _minutes = TextEditingController(
      text: session['estimated_minutes']?.toString() ?? '',
    );
    _notes = TextEditingController(text: session.text('coach_notes'));
    _media = TextEditingController(text: session.text('media_url'));
    _phaseId = session['program_phase_id'] as int?;
    final existing = session.maps('exercises');
    _exercises.addAll(
      existing.isEmpty
          ? [_ExerciseDraft()]
          : existing.map(_ExerciseDraft.fromJson),
    );
  }

  @override
  void dispose() {
    _title.dispose();
    _focus.dispose();
    _offset.dispose();
    _minutes.dispose();
    _notes.dispose();
    _media.dispose();
    for (final exercise in _exercises) {
      exercise.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.session == null ? 'Add session' : 'Edit session'),
      actions: [
        TextButton(
          onPressed: _saving ? null : _save,
          child: const Text('SAVE'),
        ),
      ],
    ),
    body: Form(
      key: _formKey,
      child: ContentColumn(
        children: [
          PremiumCard(
            child: Column(
              children: [
                TextFormField(
                  controller: _title,
                  decoration: const InputDecoration(labelText: 'Session title'),
                  validator: _required,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _focus,
                  decoration: const InputDecoration(labelText: 'Focus'),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _offset,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Day offset',
                        ),
                        validator: _required,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _minutes,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: 'Minutes'),
                      ),
                    ),
                  ],
                ),
                if (widget.phases.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    initialValue: _phaseId,
                    decoration: const InputDecoration(labelText: 'Phase'),
                    items: widget.phases
                        .map(
                          (phase) => DropdownMenuItem(
                            value: phase['id'] as int,
                            child: Text(phase.text('title')),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => _phaseId = value,
                  ),
                ],
                const SizedBox(height: 12),
                TextField(
                  controller: _media,
                  keyboardType: TextInputType.url,
                  decoration: const InputDecoration(
                    labelText: 'Session video or image URL',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _notes,
                  minLines: 2,
                  maxLines: 5,
                  decoration: const InputDecoration(
                    labelText: 'Coach instructions',
                  ),
                ),
              ],
            ),
          ),
          SectionTitle(
            'Exercises',
            action: TextButton.icon(
              onPressed: () => setState(() => _exercises.add(_ExerciseDraft())),
              icon: const Icon(Icons.add_rounded),
              label: const Text('Add'),
            ),
          ),
          ..._exercises.indexed.map(
            (entry) => _ExerciseEditor(
              index: entry.$1,
              draft: entry.$2,
              onRemove: _exercises.length == 1
                  ? null
                  : () => setState(() {
                      final removed = _exercises.removeAt(entry.$1);
                      removed.dispose();
                    }),
            ),
          ),
          if (_error != null)
            Text(
              _error!,
              style: const TextStyle(color: ThroughlineColors.danger),
            ),
          FilledButton.icon(
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check_rounded),
            label: Text(
              widget.session == null ? 'Add session' : 'Save session',
            ),
          ),
        ],
      ),
    ),
  );

  String? _required(String? value) =>
      value == null || value.trim().isEmpty ? 'Required.' : null;

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    final payload = {
      'title': _title.text.trim(),
      'focus': _focus.text.trim().isEmpty ? null : _focus.text.trim(),
      'day_offset': int.tryParse(_offset.text) ?? 0,
      'estimated_minutes': int.tryParse(_minutes.text),
      'program_phase_id': _phaseId,
      'coach_notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      'media_url': _media.text.trim().isEmpty ? null : _media.text.trim(),
      'exercises': _exercises.map((exercise) => exercise.toJson()).toList(),
    };
    try {
      final api = ref.read(apiClientProvider);
      if (widget.session == null) {
        await api.post(
          '/coach/programs/${widget.programId}/sessions',
          data: payload,
        );
      } else {
        await api.put(
          '/coach/programs/${widget.programId}/sessions/${widget.session!['id']}',
          data: payload,
        );
      }
      if (mounted) Navigator.pop(context, true);
    } on ApiFailure catch (failure) {
      if (mounted) setState(() => _error = failure.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ExerciseEditor extends StatelessWidget {
  const _ExerciseEditor({
    required this.index,
    required this.draft,
    this.onRemove,
  });

  final int index;
  final _ExerciseDraft draft;
  final VoidCallback? onRemove;

  @override
  Widget build(BuildContext context) => PremiumCard(
    accent: ThroughlineColors.emerald,
    child: Column(
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                'Exercise ${index + 1}',
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ),
            if (onRemove != null)
              IconButton(
                onPressed: onRemove,
                icon: const Icon(Icons.delete_outline_rounded),
              ),
          ],
        ),
        TextFormField(
          controller: draft.name,
          decoration: const InputDecoration(labelText: 'Exercise name'),
          validator: (value) =>
              value == null || value.trim().isEmpty ? 'Required.' : null,
        ),
        const SizedBox(height: 10),
        TextField(
          controller: draft.section,
          decoration: const InputDecoration(labelText: 'Section'),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: draft.sets,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Sets'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: TextField(
                controller: draft.reps,
                decoration: const InputDecoration(labelText: 'Reps/time'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: TextField(
                controller: draft.rest,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Rest sec'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: draft.load,
                decoration: const InputDecoration(labelText: 'Load'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: TextField(
                controller: draft.unit,
                decoration: const InputDecoration(labelText: 'Unit'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        TextField(
          controller: draft.media,
          keyboardType: TextInputType.url,
          decoration: const InputDecoration(labelText: 'Video or image URL'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: draft.note,
          decoration: const InputDecoration(labelText: 'Coaching cue'),
        ),
      ],
    ),
  );
}

class _ExerciseDraft {
  _ExerciseDraft({JsonMap? source})
    : name = TextEditingController(text: source?.text('name')),
      section = TextEditingController(
        text: source?.text('section', 'Main work') ?? 'Main work',
      ),
      sets = TextEditingController(text: source?['sets']?.toString() ?? '1'),
      reps = TextEditingController(text: source?.text('reps')),
      rest = TextEditingController(
        text: source?['rest_seconds']?.toString() ?? '',
      ),
      load = TextEditingController(text: source?.text('load')),
      unit = TextEditingController(text: source?.text('unit')),
      media = TextEditingController(text: source?.text('media_url')),
      note = TextEditingController(text: source?.text('note'));

  factory _ExerciseDraft.fromJson(JsonMap source) =>
      _ExerciseDraft(source: source);

  final TextEditingController name;
  final TextEditingController section;
  final TextEditingController sets;
  final TextEditingController reps;
  final TextEditingController rest;
  final TextEditingController load;
  final TextEditingController unit;
  final TextEditingController media;
  final TextEditingController note;

  JsonMap toJson() => {
    'name': name.text.trim(),
    'section': section.text.trim(),
    'sets': int.tryParse(sets.text) ?? 1,
    'reps': reps.text.trim(),
    'rest_seconds': int.tryParse(rest.text),
    'load': load.text.trim(),
    'unit': unit.text.trim(),
    'media_url': media.text.trim().isEmpty ? null : media.text.trim(),
    'note': note.text.trim(),
  };

  void dispose() {
    name.dispose();
    section.dispose();
    sets.dispose();
    reps.dispose();
    rest.dispose();
    load.dispose();
    unit.dispose();
    media.dispose();
    note.dispose();
  }
}

class _AssignmentSheet extends ConsumerStatefulWidget {
  const _AssignmentSheet({required this.programId, required this.athletes});

  final int programId;
  final List<JsonMap> athletes;

  @override
  ConsumerState<_AssignmentSheet> createState() => _AssignmentSheetState();
}

class _AssignmentSheetState extends ConsumerState<_AssignmentSheet> {
  int? _athleteId;
  DateTime _startsOn = DateTime.now();
  bool _saving = false;

  @override
  Widget build(BuildContext context) => SafeArea(
    child: Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        20,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Assign program',
            style: Theme.of(
              context,
            ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<int>(
            initialValue: _athleteId,
            decoration: const InputDecoration(labelText: 'Athlete'),
            items: widget.athletes
                .map(
                  (athlete) => DropdownMenuItem(
                    value: athlete['id'] as int,
                    child: Text(athlete.text('name')),
                  ),
                )
                .toList(),
            onChanged: (value) => setState(() => _athleteId = value),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _pickDate,
            icon: const Icon(Icons.calendar_month_rounded),
            label: Text(
              'Starts ${_startsOn.toIso8601String().split('T').first}',
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _saving || _athleteId == null ? null : _save,
            child: Text(_saving ? 'Assigning...' : 'Assign and build schedule'),
          ),
        ],
      ),
    ),
  );

  Future<void> _pickDate() async {
    final date = await showDatePicker(
      context: context,
      initialDate: _startsOn,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 730)),
    );
    if (date != null) setState(() => _startsOn = date);
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await ref
          .read(apiClientProvider)
          .post(
            '/coach/programs/${widget.programId}/assignments',
            data: {
              'athlete_id': _athleteId,
              'starts_on': _startsOn.toIso8601String().split('T').first,
            },
          );
      ref.invalidate(coachScheduleProvider);
      if (mounted) Navigator.pop(context, true);
    } on ApiFailure catch (failure) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(failure.message)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}
