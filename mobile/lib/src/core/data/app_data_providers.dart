import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/providers.dart';

final athleteHomeProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  final envelope = await ref.watch(apiClientProvider).get('/app/home');
  return envelope.object('data');
});

final athleteProgramsProvider = FutureProvider.autoDispose<JsonMap>((
  ref,
) async {
  final envelope = await ref
      .watch(apiClientProvider)
      .get('/app/programs', query: {'per_page': 100});
  return envelope;
});

typedef CalendarQuery = ({String month, String date});

final athleteCalendarProvider = FutureProvider.autoDispose
    .family<JsonMap, CalendarQuery>((ref, query) async {
      final envelope = await ref
          .watch(apiClientProvider)
          .get(
            '/app/calendar',
            query: {'month': query.month, 'date': query.date},
          );
      return envelope.object('data');
    });

final workoutProvider = FutureProvider.autoDispose.family<JsonMap, int>((
  ref,
  workoutId,
) async {
  final envelope = await ref
      .watch(apiClientProvider)
      .get('/app/workouts/$workoutId');
  return envelope.object('data');
});

final progressProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref
      .watch(apiClientProvider)
      .get('/app/progress', query: {'per_page': 100});
});

final progressPhotosProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref
      .watch(apiClientProvider)
      .get('/app/photos', query: {'per_page': 100});
});

final profileProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  final envelope = await ref.watch(apiClientProvider).get('/profile');
  return envelope.object('data');
});

final messagesProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref.watch(apiClientProvider).get('/messages', query: {'per_page': 50});
});

final coachHomeProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  final envelope = await ref.watch(apiClientProvider).get('/coach/home');
  return envelope.object('data');
});

final coachRosterProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref
      .watch(apiClientProvider)
      .get('/coach/roster', query: {'per_page': 100});
});

final coachProgramsProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref
      .watch(apiClientProvider)
      .get('/coach/programs', query: {'per_page': 100});
});

final coachScheduleProvider = FutureProvider.autoDispose<JsonMap>((ref) async {
  return ref
      .watch(apiClientProvider)
      .get('/coach/schedule', query: {'per_page': 100});
});

final coachProgramProvider = FutureProvider.autoDispose.family<JsonMap, int>((
  ref,
  programId,
) async {
  final envelope = await ref
      .watch(apiClientProvider)
      .get('/coach/programs/$programId');
  return envelope.object('data');
});

final coachAthleteProvider = FutureProvider.autoDispose.family<JsonMap, int>((
  ref,
  athleteId,
) async {
  final envelope = await ref
      .watch(apiClientProvider)
      .get('/coach/athletes/$athleteId');
  return envelope.object('data');
});

final coachInvitationsProvider = FutureProvider.autoDispose<JsonMap>((ref) {
  return ref
      .watch(apiClientProvider)
      .get('/coach/invitations', query: {'per_page': 100});
});

final coachExercisesProvider = FutureProvider.autoDispose<JsonMap>((ref) {
  return ref
      .watch(apiClientProvider)
      .get('/coach/exercises', query: {'per_page': 100});
});
