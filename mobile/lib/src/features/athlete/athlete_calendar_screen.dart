import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_home_screen.dart';

class AthleteCalendarScreen extends ConsumerStatefulWidget {
  const AthleteCalendarScreen({super.key});

  @override
  ConsumerState<AthleteCalendarScreen> createState() =>
      _AthleteCalendarScreenState();
}

class _AthleteCalendarScreenState extends ConsumerState<AthleteCalendarScreen> {
  DateTime _selected = DateTime.now();
  late DateTime _month = DateTime(_selected.year, _selected.month);

  CalendarQuery get _query => (
    month: DateFormat('yyyy-MM').format(_month),
    date: DateFormat('yyyy-MM-dd').format(_selected),
  );

  @override
  Widget build(BuildContext context) {
    final result = ref.watch(athleteCalendarProvider(_query));
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(athleteCalendarProvider(_query)),
          ),
        ],
      ),
      data: (data) => ContentColumn(
        children: [
          const PageIntro(
            eyebrow: 'Schedule',
            title: 'Calendar',
            body: 'Pick a day. Only workouts assigned to you appear here.',
          ),
          PremiumCard(
            child: Column(
              children: [
                Row(
                  children: [
                    IconButton(
                      onPressed: () => _moveMonth(-1),
                      icon: const Icon(Icons.chevron_left_rounded),
                    ),
                    Expanded(
                      child: Text(
                        DateFormat('MMMM yyyy').format(_month),
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                          fontSize: 18,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => _moveMonth(1),
                      icon: const Icon(Icons.chevron_right_rounded),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    for (final label in const [
                      'S',
                      'M',
                      'T',
                      'W',
                      'T',
                      'F',
                      'S',
                    ])
                      Expanded(
                        child: Text(
                          label,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: ThroughlineColors.muted,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                _MonthGrid(
                  month: _month,
                  selected: _selected,
                  days: data.maps('days'),
                  onSelect: _selectDay,
                ),
              ],
            ),
          ),
          SectionTitle(DateFormat('EEEE, d MMMM').format(_selected)),
          if (data.maps('selected_day_workouts').isEmpty)
            const EmptyPanel(
              title: 'No workout',
              body: 'This day is clear.',
              icon: Icons.event_available_rounded,
            )
          else
            ...data
                .maps('selected_day_workouts')
                .map((workout) => WorkoutListTile(workout: workout)),
        ],
      ),
    );
  }

  void _moveMonth(int amount) {
    final next = DateTime(_month.year, _month.month + amount);
    setState(() {
      _month = next;
      _selected = DateTime(next.year, next.month, 1);
    });
  }

  void _selectDay(DateTime day) => setState(() => _selected = day);
}

class _MonthGrid extends StatelessWidget {
  const _MonthGrid({
    required this.month,
    required this.selected,
    required this.days,
    required this.onSelect,
  });

  final DateTime month;
  final DateTime selected;
  final List<JsonMap> days;
  final ValueChanged<DateTime> onSelect;

  @override
  Widget build(BuildContext context) {
    final leading = DateTime(month.year, month.month).weekday % 7;
    final cells = <Widget>[
      for (var index = 0; index < leading; index++) const SizedBox.shrink(),
      for (final day in days)
        _DayCell(day: day, selected: selected, onSelect: onSelect),
    ];
    return GridView.count(
      crossAxisCount: 7,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 5,
      crossAxisSpacing: 5,
      children: cells,
    );
  }
}

class _DayCell extends StatelessWidget {
  const _DayCell({
    required this.day,
    required this.selected,
    required this.onSelect,
  });
  final JsonMap day;
  final DateTime selected;
  final ValueChanged<DateTime> onSelect;

  @override
  Widget build(BuildContext context) {
    final date = DateTime.parse(day.text('date'));
    final active = DateUtils.isSameDay(date, selected);
    final count = day['workout_count'] as int? ?? 0;
    return InkWell(
      onTap: () => onSelect(date),
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        decoration: BoxDecoration(
          color: active ? ThroughlineColors.lime : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: count > 0
                ? ThroughlineColors.emerald
                : Theme.of(context).dividerColor,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              '${date.day}',
              style: TextStyle(
                color: active ? ThroughlineColors.graphite : null,
                fontWeight: FontWeight.w900,
              ),
            ),
            if (count > 0)
              Container(
                margin: const EdgeInsets.only(top: 3),
                width: 5,
                height: 5,
                decoration: const BoxDecoration(
                  color: ThroughlineColors.emerald,
                  shape: BoxShape.circle,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
