# Throughline Native Mobile App Contract

Date: 2026-07-03

## Decision

Throughline mobile is a real React Native app built with Expo development builds.

It is not a WebView wrapper. The Laravel/Inertia web app remains the business/admin backend. The native app is for athletes and coaches.

## Stack

- Mobile app path: `mobile/`
- Framework: Expo + React Native + TypeScript
- Routing: Expo Router
- Auth token storage: Expo SecureStore
- Backend: existing Laravel API at `https://athlete.ahmaddalao.com/api/v1`
- Health sync target: authenticated Laravel mobile wearable sync endpoint
- Admin dashboard: web-only

## Role Split

Athlete mobile users can:

- log in
- view readiness and latest wearable data
- view today workouts
- view calendar and daily schedule
- open assigned programs
- execute assigned workouts
- log sets, reps, load, RPE, rest, and completion state
- read progress and wearable summaries
- message assigned coaches
- view profile basics

Coach mobile users can:

- log in
- view their coach board
- view assigned athletes
- view coach-owned programs
- view schedule and pending workout logs
- message assigned athletes

Owners/admins do not use the native app. They use the web backend.

## API Endpoints

Existing endpoint kept:

- `POST /api/v1/auth/tokens`

New mobile-focused endpoints:

- `GET /api/v1/app/home`
- `GET /api/v1/app/calendar?month=YYYY-MM&date=YYYY-MM-DD`
- `GET /api/v1/app/programs/{trainingProgram}`
- `GET /api/v1/messages`
- `POST /api/v1/messages`
- `POST /api/v1/wearables/mobile-sync`

Existing workout execution endpoints reused:

- `GET /api/v1/training/sessions/{trainingSession}/execution`
- `POST /api/v1/training/sessions/{trainingSession}/sets`
- `POST /api/v1/training/sessions/{trainingSession}/complete`

## API Abilities

Added abilities:

- `messages:read`
- `messages:write`
- `wearable:write`

Athletes receive:

- profile, training, progress, membership, wearable read
- training write
- progress write
- wearable write
- messages read/write

Coaches receive:

- profile, dashboard, roster, training, progress, membership, wearable read
- messages read/write

## Health Sync

Mobile health data uses bearer-token auth, not public ingest keys.

Supported mobile providers:

- `apple_health`
- `health_connect`

`POST /api/v1/wearables/mobile-sync` upserts a mobile `DeviceConnection`, then reuses `DeviceMetricIngestionService` so mobile, WHOOP, and API-key ingests land in the same metric snapshot system.

Accepted metrics include:

- steps
- calories burned
- sleep minutes
- active minutes
- resting heart rate
- HRV
- respiratory rate
- readiness
- strain
- training load

## Native Module Status

The app has the health sync abstraction in `mobile/src/health/health-sync.ts`.

Expo Go cannot read HealthKit or Health Connect. Real device testing needs Expo development builds with native health modules wired behind that abstraction.

Next native module work:

- iOS: add HealthKit reader and permission flow
- Android: add Health Connect reader and permission flow
- call `syncMobileHealthRecords()` with normalized daily records

## Local Commands

Install mobile dependencies:

```bash
npm --prefix mobile install
```

Run mobile app:

```bash
npm --prefix mobile run start
```

Run checks:

```bash
npm --prefix mobile run typecheck
npm --prefix mobile run lint
```

Run native development builds:

```bash
npm --prefix mobile run ios
npm --prefix mobile run android
```

## Production Notes

The backend API can be deployed to Hostinger now.

The native app is not deployed to App Store or Google Play yet. Before production release, create:

- Apple Developer account access
- Google Play Console access
- app identifiers and signing profiles
- Expo/EAS build setup or local native build pipeline
- deep link/universal link return path for WHOOP OAuth
- real-device HealthKit and Health Connect smoke tests
