import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_home_screen.dart';

class AthleteProgramDetailScreen extends ConsumerWidget {
  const AthleteProgramDetailScreen({
    super.key,
    required this.assignmentId,
    required this.title,
  });

  final int assignmentId;
  final String title;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(athleteProgramProvider(assignmentId));
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: result.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ContentColumn(
          children: [
            ErrorPanel(
              error: error,
              onRetry: () =>
                  ref.invalidate(athleteProgramProvider(assignmentId)),
            ),
          ],
        ),
        data: (assignment) => RefreshIndicator(
          onRefresh: () =>
              ref.refresh(athleteProgramProvider(assignmentId).future),
          child: _ProgramContent(assignment: assignment),
        ),
      ),
    );
  }
}

class _ProgramContent extends StatelessWidget {
  const _ProgramContent({required this.assignment});

  final JsonMap assignment;

  @override
  Widget build(BuildContext context) {
    final program = assignment.object('program');
    final coach = program.object('coach');
    final completion = assignment.object('completion');
    final percent = completion['percent'] as int? ?? 0;
    final phases = program.maps('phases');
    final workouts = assignment.maps('workouts');

    return ContentColumn(
      children: [
        PageIntro(
          eyebrow: 'Assigned program',
          title: program.text('title', 'Training program'),
          body:
              'Coach ${coach.text('name', 'not assigned')} · ${program.text('goal', 'Structured training plan')}',
        ),
        PremiumCard(
          accent: ThroughlineColors.lime,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(child: StatusChip(assignment.text('status'))),
                  Text(
                    '$percent%',
                    style: const TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              LinearProgressIndicator(
                value: percent / 100,
                minHeight: 8,
                borderRadius: BorderRadius.circular(99),
                backgroundColor: ThroughlineColors.muted.withValues(
                  alpha: 0.15,
                ),
              ),
              const SizedBox(height: 16),
              Wrap(
                spacing: 22,
                runSpacing: 12,
                children: [
                  _Fact(
                    label: 'Completed',
                    value:
                        '${completion['completed'] ?? 0}/${completion['total'] ?? workouts.length}',
                  ),
                  _Fact(
                    label: 'Starts',
                    value: _shortDate(assignment.text('starts_on')),
                  ),
                  _Fact(
                    label: 'Ends',
                    value: _shortDate(assignment.text('ends_on')),
                  ),
                  _Fact(
                    label: 'Timezone',
                    value: assignment.text('timezone', 'Local'),
                  ),
                ],
              ),
            ],
          ),
        ),
        if (phases.isNotEmpty) ...[
          const SectionTitle('Program phases'),
          PremiumCard(
            child: Column(
              children: [
                for (var index = 0; index < phases.length; index++) ...[
                  _PhaseRow(index: index, phase: phases[index]),
                  if (index < phases.length - 1) const Divider(height: 28),
                ],
              ],
            ),
          ),
        ],
        const SectionTitle('Program calendar'),
        if (workouts.isEmpty)
          const EmptyPanel(
            title: 'No workouts scheduled',
            body: 'Your coach has not added sessions to this program yet.',
          )
        else
          ..._calendarWidgets(workouts),
      ],
    );
  }

  List<Widget> _calendarWidgets(List<JsonMap> workouts) {
    final widgets = <Widget>[];
    String? lastMonth;
    for (final workout in workouts) {
      final date = DateTime.tryParse(workout.text('scheduled_for'))?.toLocal();
      final month = date == null
          ? 'Schedule'
          : DateFormat('MMMM yyyy').format(date);
      if (month != lastMonth) {
        widgets.add(SectionTitle(month));
        lastMonth = month;
      }
      widgets.add(WorkoutListTile(workout: workout));
    }
    return widgets;
  }

  String _shortDate(String value) {
    final date = DateTime.tryParse(value);
    return date == null ? '—' : DateFormat('d MMM yyyy').format(date);
  }
}

class _PhaseRow extends StatelessWidget {
  const _PhaseRow({required this.index, required this.phase});

  final int index;
  final JsonMap phase;

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      CircleAvatar(
        radius: 18,
        backgroundColor: index == 0
            ? ThroughlineColors.lime
            : ThroughlineColors.emerald,
        foregroundColor: ThroughlineColors.graphite,
        child: Text(
          '${index + 1}',
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
      ),
      const SizedBox(width: 14),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              phase.text('title', 'Phase ${index + 1}'),
              style: const TextStyle(fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 3),
            Text(
              '${phase['duration_weeks'] ?? '—'} weeks · ${phase.text('description', 'No phase notes')}',
              style: const TextStyle(
                color: ThroughlineColors.muted,
                height: 1.4,
              ),
            ),
          ],
        ),
      ),
    ],
  );
}

class _Fact extends StatelessWidget {
  const _Fact({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => SizedBox(
    width: 125,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            color: ThroughlineColors.muted,
            fontSize: 10,
            letterSpacing: 1.1,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 4),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w800)),
      ],
    ),
  );
}
