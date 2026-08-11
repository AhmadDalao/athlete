import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/auth/login_screen.dart';
import 'package:throughline_mobile/src/features/shell/role_home_shell.dart';
import 'package:throughline_mobile/src/features/workouts/workout_execution_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);

  return GoRouter(
    initialLocation: '/launch',
    redirect: (context, state) {
      final location = state.matchedLocation;
      if (auth.status == AuthStatus.loading) {
        return location == '/launch' ? null : '/launch';
      }
      if (auth.status == AuthStatus.signedOut) {
        return location == '/login' ? null : '/login';
      }
      if (location == '/login' || location == '/launch') return '/home';
      return null;
    },
    routes: [
      GoRoute(
        path: '/launch',
        builder: (context, state) => const LaunchScreen(),
      ),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/home',
        builder: (context, state) => const RoleHomeShell(),
      ),
      GoRoute(
        path: '/workouts/:workoutId',
        builder: (context, state) => WorkoutExecutionScreen(
          workoutId: int.parse(state.pathParameters['workoutId']!),
        ),
      ),
    ],
  );
});
