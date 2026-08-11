# Throughline Mobile

One Flutter application for Throughline athletes and coaches. The app uses the
Laravel `/api/v1` contract and adapts navigation to the authenticated
organization role.

## Stack

- Flutter and Dart
- Riverpod state management
- GoRouter navigation
- Dio HTTP client
- SecureStorage for Sanctum tokens
- Drift for offline workout drafts
- Video Player and CachedNetworkImage for exercise media

## Run

The production API is the default:

```bash
flutter run
```

Override it for local development. A physical phone must use the computer's LAN
address, not `localhost`:

```bash
flutter run \
  --dart-define=THROUGHLINE_API_BASE=http://192.168.1.20:8000/api/v1
```

## Quality Gates

```bash
dart run build_runner build
flutter analyze
flutter test
flutter build apk --debug
```

## Access Rules

- Athlete accounts see only their assigned programs, schedule, workouts,
  progress, and organization conversations.
- Coach accounts see only their organization roster, programs, schedule, and
  conversations.
- Platform and organization administration remains web-only.
- Every request sends the selected organization in `X-Organization-ID`; Laravel
  still validates authorization for every action.

## Offline Behavior

Workout drafts are stored in the local Drift database when the API cannot be
reached. Server timestamps and sync versions prevent an old mobile draft from
silently overwriting newer server data.
