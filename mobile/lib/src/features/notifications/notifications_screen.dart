import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/athlete/athlete_programs_screen.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_athlete_detail_screen.dart';
import 'package:throughline_mobile/src/features/messages/messages_screen.dart';

final notificationsProvider = FutureProvider.autoDispose<JsonMap>((ref) {
  return ref
      .watch(apiClientProvider)
      .get('/notifications', query: {'per_page': 50});
});

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(notificationsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton.icon(
            onPressed: notifications.isLoading
                ? null
                : () => _markAllRead(context, ref),
            icon: const Icon(Icons.done_all_rounded),
            label: const Text('Read all'),
          ),
        ],
      ),
      body: notifications.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ErrorPanel(
          error: error,
          onRetry: () => ref.invalidate(notificationsProvider),
        ),
        data: (envelope) {
          final rows = envelope.maps('data');
          if (rows.isEmpty) {
            return const EmptyPanel(
              icon: Icons.notifications_none_rounded,
              title: 'Nothing new',
              body:
                  'Program changes, workout results, and messages will appear here.',
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.refresh(notificationsProvider.future),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(18, 16, 18, 30),
              itemCount: rows.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, index) => _NotificationTile(
                notification: rows[index],
                onTap: () => _open(context, ref, rows[index]),
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _open(
    BuildContext context,
    WidgetRef ref,
    JsonMap notification,
  ) async {
    try {
      await ref
          .read(apiClientProvider)
          .patch('/notifications/${notification.text('id')}/read');
      ref.invalidate(notificationsProvider);
    } catch (error) {
      if (!context.mounted) return;
      _showError(context, error);
      return;
    }

    if (!context.mounted) return;
    final action = notification.object('action');
    final actionId = action['id'] as int?;
    final user = ref.read(authControllerProvider).user;

    switch (action.text('type')) {
      case 'scheduled_workout' when user?.isAthlete == true && actionId != null:
        context.push('/workouts/$actionId');
      case 'athlete_workout' when user?.isCoach == true:
        final athleteId = notification.object('context')['athlete_id'] as int?;
        if (athleteId != null) {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => CoachAthleteDetailScreen(athleteId: athleteId),
            ),
          );
        }
      case 'program_assignment' when user?.isAthlete == true:
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const AthleteProgramsScreen()),
        );
      case 'conversation':
        Navigator.of(
          context,
        ).push(MaterialPageRoute(builder: (_) => const MessagesScreen()));
    }
  }

  Future<void> _markAllRead(BuildContext context, WidgetRef ref) async {
    try {
      await ref.read(apiClientProvider).post('/notifications/read-all');
      ref.invalidate(notificationsProvider);
    } catch (error) {
      if (!context.mounted) return;
      _showError(context, error);
    }
  }

  void _showError(BuildContext context, Object error) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Could not update notifications. $error')),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.notification, required this.onTap});

  final JsonMap notification;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final unread = notification['read_at'] == null;
    final category = notification.text('category', 'update');
    final date = DateTime.tryParse(notification.text('created_at'));

    return PremiumCard(
      padding: EdgeInsets.zero,
      accent: unread ? ThroughlineColors.lime : null,
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        leading: CircleAvatar(
          backgroundColor: _color(category).withValues(alpha: 0.16),
          foregroundColor: _color(category),
          child: Icon(_icon(category)),
        ),
        title: Text(
          notification.text('title', 'Throughline update'),
          style: TextStyle(
            fontWeight: unread ? FontWeight.w900 : FontWeight.w700,
          ),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 5),
          child: Text(notification.text('body')),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            if (unread)
              Container(
                width: 8,
                height: 8,
                decoration: const BoxDecoration(
                  color: ThroughlineColors.lime,
                  shape: BoxShape.circle,
                ),
              ),
            if (date != null) ...[
              const SizedBox(height: 7),
              Text(
                DateFormat('MMM d').format(date.toLocal()),
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: ThroughlineColors.muted,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  static IconData _icon(String category) => switch (category) {
    'program' => Icons.fitness_center_rounded,
    'schedule' => Icons.calendar_month_rounded,
    'workout' => Icons.task_alt_rounded,
    'message' => Icons.forum_rounded,
    _ => Icons.notifications_rounded,
  };

  static Color _color(String category) => switch (category) {
    'program' => ThroughlineColors.lime,
    'schedule' => ThroughlineColors.gold,
    'workout' => ThroughlineColors.emerald,
    'message' => ThroughlineColors.cyan,
    _ => ThroughlineColors.muted,
  };
}
