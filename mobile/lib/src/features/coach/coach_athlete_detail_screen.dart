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

class CoachAthleteDetailScreen extends ConsumerWidget {
  const CoachAthleteDetailScreen({super.key, required this.athleteId});

  final int athleteId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
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
          final progress = data.maps('progress');
          final photos = data.maps('photos');
          final records = data.maps('records');
          final notes = data.maps('notes');
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
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => _addNote(context, ref, athlete),
                        icon: const Icon(Icons.note_add_outlined),
                        label: const Text('Add note'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _uploadPhoto(context, ref, athlete),
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
                        ],
                      ),
                    );
                  }),
                const SectionTitle('Recent progress'),
                if (progress.isEmpty)
                  const EmptyPanel(
                    title: 'No check-ins yet',
                    body:
                        'Progress entries appear here after the athlete logs them.',
                  )
                else
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
                const SectionTitle('Coach notes'),
                if (notes.isEmpty)
                  const EmptyPanel(
                    title: 'No coach notes',
                    body: 'Private review notes stay attached to this athlete.',
                    icon: Icons.sticky_note_2_outlined,
                  )
                else
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
                const SectionTitle('Progress photos'),
                if (photos.isEmpty)
                  const EmptyPanel(
                    title: 'No progress photos',
                    body:
                        'Athlete and coach uploads appear here with permission controls.',
                    icon: Icons.photo_library_outlined,
                  )
                else
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
                const SectionTitle('Personal records'),
                if (records.isEmpty)
                  const EmptyPanel(
                    title: 'No personal records',
                    body: 'Verified strength records will appear here.',
                    icon: Icons.emoji_events_outlined,
                  )
                else
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
