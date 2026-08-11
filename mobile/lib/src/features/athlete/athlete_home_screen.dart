import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class AthleteHomeScreen extends ConsumerWidget {
  const AthleteHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(athleteHomeProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(athleteHomeProvider),
          ),
        ],
      ),
      data: (data) {
        final athlete = data.object('athlete');
        final today = data.maps('today_workouts');
        final upcoming = data.maps('upcoming_workouts');
        final programs = data.maps('programs');
        final progress = data['latest_progress'] is Map
            ? (data['latest_progress'] as Map).cast<String, dynamic>()
            : <String, dynamic>{};
        return RefreshIndicator(
          onRefresh: () => ref.refresh(athleteHomeProvider.future),
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Athlete home',
                title:
                    'Move with purpose, ${athlete.text('name', 'athlete').split(' ').first}.',
                body:
                    'Today is compact on purpose: do the work, log it, then get out of the app.',
              ),
              _TodayCard(workouts: today),
              Row(
                children: [
                  Expanded(
                    child: MetricTile(
                      label: 'Active programs',
                      value: programs.length.toString(),
                      icon: Icons.fitness_center_rounded,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: MetricTile(
                      label: 'Next 7 days',
                      value: upcoming.length.toString(),
                      icon: Icons.calendar_today_rounded,
                      color: ThroughlineColors.cyan,
                    ),
                  ),
                ],
              ),
              const SectionTitle('Latest check-in'),
              if (progress.isEmpty)
                const EmptyPanel(
                  title: 'No progress entry yet',
                  body:
                      'Log a check-in from Progress when you have something useful to record.',
                  icon: Icons.query_stats_outlined,
                )
              else
                PremiumCard(
                  accent: ThroughlineColors.emerald,
                  child: Wrap(
                    spacing: 22,
                    runSpacing: 16,
                    children: [
                      _Value(
                        label: 'Weight',
                        value: progress['weight_kg'] == null
                            ? '—'
                            : '${progress['weight_kg']} kg',
                      ),
                      _Value(
                        label: 'Energy',
                        value: progress['energy'] == null
                            ? '—'
                            : '${progress['energy']}/10',
                      ),
                      _Value(
                        label: 'Sleep',
                        value: progress['sleep_quality'] == null
                            ? '—'
                            : '${progress['sleep_quality']}/10',
                      ),
                      _Value(
                        label: 'Logged',
                        value: progress.text('logged_on', '—'),
                      ),
                    ],
                  ),
                ),
              const SectionTitle('Upcoming'),
              if (upcoming.isEmpty)
                const EmptyPanel(
                  title: 'Nothing scheduled',
                  body: 'Your coach has not scheduled another workout yet.',
                )
              else
                ...upcoming
                    .take(4)
                    .map((workout) => WorkoutListTile(workout: workout)),
            ],
          ),
        );
      },
    );
  }
}

class _TodayCard extends StatelessWidget {
  const _TodayCard({required this.workouts});
  final List<JsonMap> workouts;

  @override
  Widget build(BuildContext context) {
    if (workouts.isEmpty) {
      return const EmptyPanel(
        title: 'No workout today',
        body: 'Recovery is work too. Your upcoming schedule is still below.',
        icon: Icons.self_improvement_rounded,
      );
    }
    final workout = workouts.first;
    final session = workout.object('session');
    final exercises = session.maps('exercises');
    return PremiumCard(
      accent: ThroughlineColors.lime,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.bolt_rounded, color: ThroughlineColors.lime),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  "TODAY'S WORKOUT",
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.2,
                  ),
                ),
              ),
              StatusChip(workout.text('status', 'scheduled')),
            ],
          ),
          const SizedBox(height: 22),
          Text(
            session.text('title', 'Training session'),
            style: Theme.of(
              context,
            ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 5),
          Text(
            '${session.text('focus', 'Training')} · ${exercises.length} exercises',
            style: const TextStyle(color: ThroughlineColors.muted),
          ),
          const SizedBox(height: 20),
          FilledButton.icon(
            onPressed: () => context.push('/workouts/${workout['id']}'),
            icon: const Icon(Icons.play_arrow_rounded),
            label: const Text('Open workout'),
          ),
        ],
      ),
    );
  }
}

class WorkoutListTile extends StatelessWidget {
  const WorkoutListTile({super.key, required this.workout});
  final JsonMap workout;

  @override
  Widget build(BuildContext context) {
    final session = workout.object('session');
    return PremiumCard(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
      child: InkWell(
        onTap: () => context.push('/workouts/${workout['id']}'),
        borderRadius: BorderRadius.circular(18),
        child: Row(
          children: [
            const CircleAvatar(
              backgroundColor: ThroughlineColors.lime,
              foregroundColor: ThroughlineColors.graphite,
              child: Icon(Icons.fitness_center_rounded, size: 18),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    session.text('title', 'Workout'),
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  Text(
                    workout
                        .text('scheduled_for')
                        .replaceFirst('T', ' ')
                        .split('.')
                        .first,
                    style: const TextStyle(
                      color: ThroughlineColors.muted,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded),
          ],
        ),
      ),
    );
  }
}

class _Value extends StatelessWidget {
  const _Value({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => SizedBox(
    width: 120,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            color: ThroughlineColors.muted,
            fontSize: 10,
            letterSpacing: 1.2,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
        ),
      ],
    ),
  );
}
