import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
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
              ],
            ),
          );
        },
      ),
    );
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
