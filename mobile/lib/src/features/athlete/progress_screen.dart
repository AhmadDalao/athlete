import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class ProgressScreen extends ConsumerWidget {
  const ProgressScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(progressProvider);
    return result.when(
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
          onRefresh: () => ref.refresh(progressProvider.future),
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Progress',
                title: 'Signals, not noise.',
                body: 'Log what helps your coach make a better decision.',
              ),
              FilledButton.icon(
                onPressed: () => _showCheckIn(context, ref),
                icon: const Icon(Icons.add_rounded),
                label: const Text('Log today'),
              ),
              if (entries.isEmpty)
                const EmptyPanel(
                  title: 'No check-ins',
                  body: 'Your first entry will appear here.',
                )
              else ...[
                _TrendStrip(entries: entries),
                const SectionTitle('History'),
                ...entries.take(30).map((entry) => _ProgressRow(entry: entry)),
              ],
            ],
          ),
        );
      },
    );
  }

  Future<void> _showCheckIn(BuildContext context, WidgetRef ref) async {
    final weight = TextEditingController();
    final notes = TextEditingController();
    double energy = 7;
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
                'Today’s check-in',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 18),
              TextField(
                controller: weight,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Weight (kg)'),
              ),
              const SizedBox(height: 14),
              Text(
                'Energy ${energy.round()}/10',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              Slider(
                value: energy,
                min: 1,
                max: 10,
                divisions: 9,
                onChanged: (value) => setModalState(() => energy = value),
              ),
              TextField(
                controller: notes,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Notes for your coach',
                ),
              ),
              const SizedBox(height: 18),
              FilledButton(
                onPressed: () async {
                  await ref
                      .read(apiClientProvider)
                      .post(
                        '/app/progress',
                        data: {
                          'logged_on': DateTime.now()
                              .toIso8601String()
                              .substring(0, 10),
                          'weight_kg': double.tryParse(weight.text),
                          'energy': energy.round(),
                          'notes': notes.text.trim().isEmpty
                              ? null
                              : notes.text.trim(),
                        },
                      );
                  if (context.mounted) Navigator.pop(context, true);
                },
                child: const Text('Save check-in'),
              ),
            ],
          ),
        ),
      ),
    );
    weight.dispose();
    notes.dispose();
    if (saved == true) ref.invalidate(progressProvider);
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
              Text(
                entry.text('notes', 'No note'),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: ThroughlineColors.muted),
              ),
            ],
          ),
        ),
        Text(
          entry['weight_kg'] == null ? '—' : '${entry['weight_kg']} kg',
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
      ],
    ),
  );
}
