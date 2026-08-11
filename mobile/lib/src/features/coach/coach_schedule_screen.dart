import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
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
        return RefreshIndicator(
          onRefresh: () => ref.refresh(coachScheduleProvider.future),
          child: ContentColumn(
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
                    padding: EdgeInsets.zero,
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 10,
                      ),
                      leading: const CircleAvatar(
                        backgroundColor: ThroughlineColors.gold,
                        foregroundColor: ThroughlineColors.graphite,
                        child: Icon(Icons.event_rounded),
                      ),
                      title: Text(
                        workout.object('session').text('title', 'Workout'),
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      subtitle: Text(
                        '${workout.object('athlete').text('name', 'Athlete')} · ${workout.text('scheduled_for').split('T').first}',
                        style: const TextStyle(color: ThroughlineColors.muted),
                      ),
                      trailing: const Icon(Icons.edit_calendar_rounded),
                      onTap: () => _reschedule(context, ref, workout),
                    ),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _reschedule(
    BuildContext context,
    WidgetRef ref,
    JsonMap workout,
  ) async {
    final current =
        DateTime.tryParse(workout.text('scheduled_for')) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 730)),
    );
    if (selected == null) return;
    try {
      await ref
          .read(apiClientProvider)
          .patch(
            '/coach/schedule/${workout['id']}/reschedule',
            data: {
              'scheduled_for': selected.toIso8601String().split('T').first,
            },
          );
      ref.invalidate(coachScheduleProvider);
      ref.invalidate(coachHomeProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Workout rescheduled.')));
      }
    } on ApiFailure catch (failure) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(failure.message)));
      }
    }
  }
}
