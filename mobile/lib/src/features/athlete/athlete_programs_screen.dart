import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class AthleteProgramsScreen extends ConsumerWidget {
  const AthleteProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(athleteProgramsProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(athleteProgramsProvider),
          ),
        ],
      ),
      data: (envelope) {
        final programs = envelope.maps('data');
        return RefreshIndicator(
          onRefresh: () => ref.refresh(athleteProgramsProvider.future),
          child: ContentColumn(
            children: [
              const PageIntro(
                eyebrow: 'Assigned work',
                title: 'Programs',
                body:
                    'Every block assigned to you, separated by coach and schedule.',
              ),
              if (programs.isEmpty)
                const EmptyPanel(
                  title: 'No active programs',
                  body: 'Your coach has not assigned a program yet.',
                )
              else
                ...programs.map(
                  (assignment) => _ProgramCard(assignment: assignment),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _ProgramCard extends StatelessWidget {
  const _ProgramCard({required this.assignment});
  final JsonMap assignment;

  @override
  Widget build(BuildContext context) {
    final program = assignment.object('program');
    final coach = program.object('coach');
    final completion = assignment.object('completion');
    return PremiumCard(
      accent: ThroughlineColors.emerald,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: StatusChip(assignment.text('status', 'active'))),
              Text(
                '${completion['percentage'] ?? 0}%',
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            program.text('title', 'Training program'),
            style: Theme.of(
              context,
            ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 5),
          Text(
            program.text('goal', 'No goal specified'),
            style: const TextStyle(color: ThroughlineColors.muted),
          ),
          const SizedBox(height: 18),
          LinearProgressIndicator(
            value: ((completion['percentage'] as num?)?.toDouble() ?? 0) / 100,
            minHeight: 7,
            borderRadius: BorderRadius.circular(99),
            backgroundColor: ThroughlineColors.muted.withValues(alpha: 0.15),
          ),
          const SizedBox(height: 14),
          Text(
            'Coach ${coach.text('name', 'not assigned')} · starts ${assignment.text('starts_on', '—')}',
            style: const TextStyle(
              color: ThroughlineColors.muted,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}
