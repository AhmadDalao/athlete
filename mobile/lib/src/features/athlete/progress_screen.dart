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

class ProgressScreen extends ConsumerWidget {
  const ProgressScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final progress = ref.watch(progressProvider);
    final photos = ref.watch(progressPhotosProvider);

    return progress.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(progressProvider),
          ),
        ],
      ),
      data: (envelope) {
        final entries = envelope.maps('data');
        return RefreshIndicator(
          onRefresh: () async {
            await Future.wait([
              ref.refresh(progressProvider.future),
              ref.refresh(progressPhotosProvider.future),
            ]);
          },
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Progress',
                title: 'Signals, not noise.',
                body:
                    'Log the daily context that helps your coach make a better decision.',
                action: IconButton.filledTonal(
                  tooltip: 'Add progress photo',
                  onPressed: () => _uploadPhoto(context, ref),
                  icon: const Icon(Icons.add_a_photo_outlined),
                ),
              ),
              FilledButton.icon(
                onPressed: () => _showCheckIn(context, ref),
                icon: const Icon(Icons.add_rounded),
                label: const Text('Log today'),
              ),
              if (entries.isEmpty)
                const EmptyPanel(
                  title: 'No check-ins',
                  body: 'Your first daily entry will appear here.',
                )
              else ...[
                _TrendStrip(entries: entries),
                const SectionTitle('Recent check-ins'),
                ...entries.take(14).map((entry) => _ProgressRow(entry: entry)),
              ],
              const SectionTitle('Progress photos'),
              photos.when(
                loading: () => const LoadingPanel(),
                error: (error, _) => ErrorPanel(
                  error: error,
                  onRetry: () => ref.invalidate(progressPhotosProvider),
                ),
                data: (photoEnvelope) {
                  final records = photoEnvelope.maps('data');
                  if (records.isEmpty) {
                    return const EmptyPanel(
                      title: 'No progress photos',
                      body:
                          'Add private visual checkpoints without exposing them publicly.',
                      icon: Icons.photo_library_outlined,
                    );
                  }
                  return GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: records.length,
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 0.78,
                        ),
                    itemBuilder: (context, index) => _PhotoTile(
                      photo: records[index],
                      onDelete: records[index]['can_delete'] == true
                          ? () => _deletePhoto(context, ref, records[index])
                          : null,
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _showCheckIn(BuildContext context, WidgetRef ref) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => const _CheckInSheet(),
    );
    if (saved == true) ref.invalidate(progressProvider);
  }

  Future<void> _uploadPhoto(BuildContext context, WidgetRef ref) async {
    final image = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 88,
      maxWidth: 1800,
    );
    if (image == null || !context.mounted) return;

    var category = 'progress';
    final selected = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Photo angle',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 12),
              for (final value in const [
                'progress',
                'front',
                'side',
                'back',
                'other',
              ])
                ListTile(
                  title: Text(value[0].toUpperCase() + value.substring(1)),
                  onTap: () => Navigator.pop(context, value),
                ),
            ],
          ),
        ),
      ),
    );
    if (selected == null || !context.mounted) return;
    category = selected;

    try {
      final form = FormData.fromMap({
        'photo': await MultipartFile.fromFile(image.path, filename: image.name),
        'taken_on': DateFormat('yyyy-MM-dd').format(DateTime.now()),
        'category': category,
      });
      await ref.read(apiClientProvider).post('/app/photos', data: form);
      ref.invalidate(progressPhotosProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Progress photo uploaded.')),
        );
      }
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
    JsonMap photo,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete photo?'),
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
      await ref.read(apiClientProvider).delete('/app/photos/${photo['id']}');
      ref.invalidate(progressPhotosProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Progress photo deleted.')),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }
}

class _CheckInSheet extends ConsumerStatefulWidget {
  const _CheckInSheet();

  @override
  ConsumerState<_CheckInSheet> createState() => _CheckInSheetState();
}

class _CheckInSheetState extends ConsumerState<_CheckInSheet> {
  final weight = TextEditingController();
  final calories = TextEditingController();
  final protein = TextEditingController();
  final hydration = TextEditingController();
  final notes = TextEditingController();
  double energy = 7;
  double soreness = 4;
  double sleepQuality = 7;
  bool saving = false;

  @override
  void dispose() {
    weight.dispose();
    calories.dispose();
    protein.dispose();
    hydration.dispose();
    notes.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => SizedBox(
    height: MediaQuery.sizeOf(context).height * 0.88,
    child: ListView(
      padding: EdgeInsets.fromLTRB(
        20,
        22,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 24,
      ),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                'Today’s check-in',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.close_rounded),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _numberField(weight, 'Weight', 'kg', decimal: true),
            ),
            const SizedBox(width: 12),
            Expanded(child: _numberField(calories, 'Calories', 'kcal')),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(child: _numberField(protein, 'Protein', 'g')),
            const SizedBox(width: 12),
            Expanded(child: _numberField(hydration, 'Hydration', 'ml')),
          ],
        ),
        const SizedBox(height: 18),
        _score('Energy', energy, (value) => setState(() => energy = value)),
        _score(
          'Soreness',
          soreness,
          (value) => setState(() => soreness = value),
        ),
        _score(
          'Sleep quality',
          sleepQuality,
          (value) => setState(() => sleepQuality = value),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: notes,
          maxLines: 4,
          decoration: const InputDecoration(labelText: 'Notes for your coach'),
        ),
        const SizedBox(height: 18),
        FilledButton(
          onPressed: saving ? null : _save,
          child: saving
              ? const SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Save check-in'),
        ),
      ],
    ),
  );

  Widget _numberField(
    TextEditingController controller,
    String label,
    String suffix, {
    bool decimal = false,
  }) => TextField(
    controller: controller,
    keyboardType: TextInputType.numberWithOptions(decimal: decimal),
    decoration: InputDecoration(labelText: label, suffixText: suffix),
  );

  Widget _score(String label, double value, ValueChanged<double> onChanged) =>
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '$label ${value.round()}/10',
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
          Slider(
            value: value,
            min: 1,
            max: 10,
            divisions: 9,
            onChanged: onChanged,
          ),
        ],
      );

  Future<void> _save() async {
    setState(() => saving = true);
    try {
      await ref
          .read(apiClientProvider)
          .post(
            '/app/progress',
            data: {
              'logged_on': DateFormat('yyyy-MM-dd').format(DateTime.now()),
              'weight_kg': double.tryParse(weight.text),
              'calories_kcal': int.tryParse(calories.text),
              'protein_g': int.tryParse(protein.text),
              'hydration_ml': int.tryParse(hydration.text),
              'energy': energy.round(),
              'soreness': soreness.round(),
              'sleep_quality': sleepQuality.round(),
              'notes': notes.text.trim().isEmpty ? null : notes.text.trim(),
            },
          );
      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) {
        setState(() => saving = false);
      }
    }
  }
}

class _TrendStrip extends StatelessWidget {
  const _TrendStrip({required this.entries});
  final List<JsonMap> entries;

  @override
  Widget build(BuildContext context) {
    final latest = entries.first;
    return Row(
      children: [
        Expanded(
          child: MetricTile(
            label: 'Weight',
            value: latest['weight_kg'] == null
                ? '—'
                : '${latest['weight_kg']} kg',
            icon: Icons.monitor_weight_outlined,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: MetricTile(
            label: 'Energy',
            value: latest['energy'] == null ? '—' : '${latest['energy']}/10',
            icon: Icons.bolt_rounded,
            color: ThroughlineColors.gold,
          ),
        ),
      ],
    );
  }
}

class _ProgressRow extends StatelessWidget {
  const _ProgressRow({required this.entry});
  final JsonMap entry;

  @override
  Widget build(BuildContext context) => PremiumCard(
    padding: const EdgeInsets.all(16),
    child: Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                entry.text('logged_on'),
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 4),
              Text(
                'Protein ${entry['protein_g'] ?? '—'}g · Water ${entry['hydration_ml'] ?? '—'}ml · Sleep ${entry['sleep_quality'] ?? '—'}/10',
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: ThroughlineColors.muted,
                  fontSize: 12,
                ),
              ),
              if (entry.text('notes').isNotEmpty)
                Text(
                  entry.text('notes'),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        Text(
          entry['weight_kg'] == null ? '—' : '${entry['weight_kg']} kg',
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
      ],
    ),
  );
}

class _PhotoTile extends StatelessWidget {
  const _PhotoTile({required this.photo, this.onDelete});
  final JsonMap photo;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) => Stack(
    fit: StackFit.expand,
    children: [
      AuthenticatedImage(url: photo.text('url')),
      Positioned(
        left: 8,
        right: 8,
        bottom: 8,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.black.withValues(alpha: 0.72),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '${photo.text('category', 'progress')} · ${photo.text('taken_on')}',
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
      if (onDelete != null)
        Positioned(
          top: 8,
          right: 8,
          child: IconButton.filledTonal(
            onPressed: onDelete,
            icon: const Icon(Icons.delete_outline_rounded, size: 18),
          ),
        ),
    ],
  );
}
