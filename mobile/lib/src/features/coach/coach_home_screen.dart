import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_athlete_detail_screen.dart';

class CoachHomeScreen extends ConsumerWidget {
  const CoachHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    final canViewAthletes = user?.can('athletes.view') == true;
    final canManagePrograms = user?.can('programs.manage') == true;
    final canManageSchedule = user?.can('schedule.manage') == true;
    final canReviewProgress = user?.can('progress.review') == true;
    final result = ref.watch(coachHomeProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(coachHomeProvider),
          ),
        ],
      ),
      data: (data) {
        final coach = data.object('coach');
        final summary = data.object('summary');
        final athletes = data.maps('athletes');
        final schedule = data.maps('schedule');
        return RefreshIndicator(
          onRefresh: () => ref.refresh(coachHomeProvider.future),
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Coach command',
                title:
                    'Good decisions, ${coach.text('name', 'coach').split(' ').first}.',
                body:
                    'The next athlete action should be obvious without digging through a dashboard.',
              ),
              _CoachSummary(
                summary: summary,
                showAthletes: canViewAthletes,
                showPrograms: canManagePrograms,
                showSchedule: canManageSchedule,
                showReviews: canReviewProgress,
              ),
              if (canViewAthletes) const SectionTitle('Athletes in focus'),
              if (canViewAthletes && athletes.isEmpty)
                const EmptyPanel(
                  title: 'No assigned athletes',
                  body: 'Invite or assign an athlete from the web workspace.',
                )
              else if (canViewAthletes)
                ...athletes.map(
                  (athlete) => _AthleteTile(
                    athlete: athlete,
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => CoachAthleteDetailScreen(
                          athleteId: athlete['id'] as int,
                        ),
                      ),
                    ),
                  ),
                ),
              if (canManageSchedule) const SectionTitle('Next seven days'),
              if (canManageSchedule && schedule.isEmpty)
                const EmptyPanel(
                  title: 'No scheduled work',
                  body: 'Build and assign a program from Programs.',
                )
              else if (canManageSchedule)
                ...schedule
                    .take(5)
                    .map((workout) => _ScheduleTile(workout: workout)),
            ],
          ),
        );
      },
    );
  }
}

class _CoachSummary extends StatelessWidget {
  const _CoachSummary({
    required this.summary,
    required this.showAthletes,
    required this.showPrograms,
    required this.showSchedule,
    required this.showReviews,
  });
  final JsonMap summary;
  final bool showAthletes;
  final bool showPrograms;
  final bool showSchedule;
  final bool showReviews;

  @override
  Widget build(BuildContext context) => GridView.count(
    crossAxisCount: 2,
    shrinkWrap: true,
    physics: const NeverScrollableScrollPhysics(),
    mainAxisSpacing: 12,
    crossAxisSpacing: 12,
    childAspectRatio: 1.45,
    children: [
      if (showAthletes)
        MetricTile(
          label: 'Athletes',
          value: summary.text('active_athletes', '0'),
          icon: Icons.groups_2_rounded,
        ),
      if (showPrograms)
        MetricTile(
          label: 'Programs',
          value: summary.text('active_programs', '0'),
          icon: Icons.fitness_center_rounded,
          color: ThroughlineColors.cyan,
        ),
      if (showSchedule)
        MetricTile(
          label: 'This week',
          value: summary.text('workouts_this_week', '0'),
          icon: Icons.calendar_month_rounded,
          color: ThroughlineColors.gold,
        ),
      if (showReviews)
        MetricTile(
          label: 'Review',
          value: summary.text('pending_reviews', '0'),
          icon: Icons.rate_review_rounded,
          color: ThroughlineColors.emerald,
        ),
    ],
  );
}

class _AthleteTile extends StatelessWidget {
  const _AthleteTile({required this.athlete, required this.onTap});
  final JsonMap athlete;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => PremiumCard(
    padding: EdgeInsets.zero,
    child: ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      leading: CircleAvatar(
        backgroundColor: ThroughlineColors.lime,
        foregroundColor: ThroughlineColors.graphite,
        child: Text(athlete.text('name', 'A').substring(0, 1).toUpperCase()),
      ),
      title: Text(
        athlete.text('name'),
        style: const TextStyle(fontWeight: FontWeight.w900),
      ),
      subtitle: Text(
        athlete.text('email'),
        style: const TextStyle(color: ThroughlineColors.muted, fontSize: 12),
      ),
      trailing: const Icon(Icons.chevron_right_rounded),
      onTap: onTap,
    ),
  );
}

class _ScheduleTile extends StatelessWidget {
  const _ScheduleTile({required this.workout});
  final JsonMap workout;

  @override
  Widget build(BuildContext context) => PremiumCard(
    padding: const EdgeInsets.all(16),
    child: Row(
      children: [
        const Icon(Icons.bolt_rounded, color: ThroughlineColors.gold),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                workout.object('session').text('title', 'Workout'),
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
              Text(
                workout.object('athlete').text('name', 'Athlete'),
                style: const TextStyle(color: ThroughlineColors.muted),
              ),
            ],
          ),
        ),
        StatusChip(workout.text('status', 'scheduled')),
      ],
    ),
  );
}
