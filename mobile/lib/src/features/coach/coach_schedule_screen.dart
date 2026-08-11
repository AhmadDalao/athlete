import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class CoachScheduleScreen extends ConsumerWidget {
  const CoachScheduleScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachScheduleProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(coachScheduleProvider),
          ),
        ],
      ),
      data: (envelope) {
        final workouts = envelope.maps('data');
        return ContentColumn(
          children: [
            const PageIntro(
              eyebrow: 'Coach schedule',
              title: 'Assigned work',
              body:
                  'A direct list of what your athletes are expected to execute.',
            ),
            if (workouts.isEmpty)
              const EmptyPanel(
                title: 'Nothing scheduled',
                body: 'Assign a program to generate dated workouts.',
              )
            else
              ...workouts.map(
                (workout) => PremiumCard(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const CircleAvatar(
                        backgroundColor: ThroughlineColors.gold,
                        foregroundColor: ThroughlineColors.graphite,
                        child: Icon(Icons.event_rounded),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              workout
                                  .object('session')
                                  .text('title', 'Workout'),
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            Text(
                              '${workout.object('athlete').text('name', 'Athlete')} · ${workout.text('scheduled_for').split('T').first}',
                              style: const TextStyle(
                                color: ThroughlineColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      StatusChip(workout.text('status', 'scheduled')),
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
