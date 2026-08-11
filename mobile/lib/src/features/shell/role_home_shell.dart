import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_calendar_screen.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_home_screen.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_programs_screen.dart';
import 'package:throughline_mobile/src/features/athlete/progress_screen.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_home_screen.dart';
import 'package:throughline_mobile/src/features/coach/coach_programs_screen.dart';
import 'package:throughline_mobile/src/features/coach/coach_roster_screen.dart';
import 'package:throughline_mobile/src/features/coach/coach_schedule_screen.dart';
import 'package:throughline_mobile/src/features/messages/messages_screen.dart';
import 'package:throughline_mobile/src/features/profile/more_screen.dart';

class RoleHomeShell extends ConsumerStatefulWidget {
  const RoleHomeShell({super.key});

  @override
  ConsumerState<RoleHomeShell> createState() => _RoleHomeShellState();
}

class _RoleHomeShellState extends ConsumerState<RoleHomeShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;
    if (user == null) return const SizedBox.shrink();
    if (!user.supportsMobile) return _UnsupportedRole(userName: user.name);

    final coach = user.isCoach;
    final pages = coach
        ? const [
            CoachHomeScreen(),
            CoachRosterScreen(),
            CoachProgramsScreen(),
            CoachScheduleScreen(),
            MessagesScreen(),
            MoreScreen(),
          ]
        : const [
            AthleteHomeScreen(),
            AthleteCalendarScreen(),
            AthleteProgramsScreen(),
            ProgressScreen(),
            MessagesScreen(),
            MoreScreen(),
          ];
    final destinations = coach
        ? const [
            _Destination(Icons.home_rounded, 'Home'),
            _Destination(Icons.groups_2_rounded, 'Roster'),
            _Destination(Icons.fitness_center_rounded, 'Programs'),
            _Destination(Icons.calendar_month_rounded, 'Schedule'),
            _Destination(Icons.forum_rounded, 'Messages'),
            _Destination(Icons.menu_rounded, 'More'),
          ]
        : const [
            _Destination(Icons.home_rounded, 'Home'),
            _Destination(Icons.calendar_month_rounded, 'Schedule'),
            _Destination(Icons.fitness_center_rounded, 'Workouts'),
            _Destination(Icons.query_stats_rounded, 'Progress'),
            _Destination(Icons.forum_rounded, 'Messages'),
            _Destination(Icons.menu_rounded, 'More'),
          ];
    AppOrganization? activeOrganization;
    for (final organization in auth.organizations) {
      if (organization.id == auth.activeOrganizationId) {
        activeOrganization = organization;
        break;
      }
    }

    return Scaffold(
      appBar: AppBar(
        leading: Builder(
          builder: (context) => IconButton(
            onPressed: Scaffold.of(context).openDrawer,
            icon: const Icon(Icons.menu_rounded),
            tooltip: 'Open navigation',
          ),
        ),
        title: const ThroughlineMark(compact: true),
        actions: [
          if (activeOrganization != null)
            Padding(
              padding: const EdgeInsets.only(right: 14),
              child: Center(
                child: Text(
                  activeOrganization.name,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: ThroughlineColors.muted,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
        ],
      ),
      drawer: _AppDrawer(
        currentIndex: _index,
        destinations: destinations,
        onSelect: (value) {
          setState(() => _index = value);
          Navigator.of(context).pop();
        },
      ),
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        labelBehavior: NavigationDestinationLabelBehavior.onlyShowSelected,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: [
          for (final destination in destinations)
            NavigationDestination(
              icon: Icon(destination.icon),
              label: destination.label,
            ),
        ],
      ),
    );
  }
}

class _AppDrawer extends ConsumerWidget {
  const _AppDrawer({
    required this.currentIndex,
    required this.destinations,
    required this.onSelect,
  });

  final int currentIndex;
  final List<_Destination> destinations;
  final ValueChanged<int> onSelect;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const ThroughlineMark(),
                  const SizedBox(height: 24),
                  Text(
                    auth.user?.name ?? '',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  Text(
                    auth.user?.email ?? '',
                    style: const TextStyle(color: ThroughlineColors.muted),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.all(12),
                itemCount: destinations.length,
                itemBuilder: (context, index) {
                  final destination = destinations[index];
                  return ListTile(
                    selected: currentIndex == index,
                    selectedTileColor: ThroughlineColors.lime.withValues(
                      alpha: 0.14,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    leading: Icon(destination.icon),
                    title: Text(
                      destination.label,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    onTap: () => onSelect(index),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: OutlinedButton.icon(
                onPressed: ref.read(authControllerProvider.notifier).logout,
                icon: const Icon(Icons.logout_rounded),
                label: const Text('Sign out'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Destination {
  const _Destination(this.icon, this.label);
  final IconData icon;
  final String label;
}

class _UnsupportedRole extends ConsumerWidget {
  const _UnsupportedRole({required this.userName});
  final String userName;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    body: SafeArea(
      child: ContentColumn(
        children: [
          const ThroughlineMark(),
          PageIntro(
            eyebrow: 'Web-only account',
            title: 'Hello, $userName.',
            body:
                'Platform administration stays on the web. This app is intentionally limited to coaches and athletes.',
          ),
          FilledButton.icon(
            onPressed: ref.read(authControllerProvider.notifier).logout,
            icon: const Icon(Icons.logout),
            label: const Text('Sign out'),
          ),
        ],
      ),
    ),
  );
}
