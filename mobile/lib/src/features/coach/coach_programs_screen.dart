import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class CoachProgramsScreen extends ConsumerWidget {
  const CoachProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachProgramsProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(coachProgramsProvider),
          ),
        ],
      ),
      data: (envelope) {
        final programs = envelope.maps('data');
        return ContentColumn(
          children: [
            const PageIntro(
              eyebrow: 'Program library',
              title: 'Build once. Assign well.',
              body:
                  'Mobile editing comes next; this view is already scoped to your own templates.',
            ),
            if (programs.isEmpty)
              const EmptyPanel(
                title: 'No programs',
                body:
                    'Create the first reusable program in the web coach workspace.',
              )
            else
              ...programs.map(
                (program) => PremiumCard(
                  accent: ThroughlineColors.cyan,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          StatusChip(program.text('status', 'draft')),
                          const Spacer(),
                          Text(
                            '${program['assignments_count'] ?? 0} assigned',
                            style: const TextStyle(
                              color: ThroughlineColors.muted,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text(
                        program.text('title'),
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      Text(
                        program.text('goal', 'No goal'),
                        style: const TextStyle(color: ThroughlineColors.muted),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        '${program['sessions_count'] ?? 0} sessions · ${program['estimated_weeks'] ?? '—'} weeks',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}
