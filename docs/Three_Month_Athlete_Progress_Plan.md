# Throughline Three-Month Athlete Plan

This document is the reference plan for the seeded athlete experience and the
web/mobile parity contract. The demo is intentionally realistic enough to test
calendar density, multiple assignments, phases, workout execution, recovery,
and progress trends.

## Demo access

All demo accounts use the password `password`.

| Role | Email | Purpose |
| --- | --- | --- |
| Athlete | `athlete@throughline.test` | Three simultaneous 12-week assignments |
| Athlete | `maya.athlete@throughline.test` | Eight-week acceleration plan |
| Athlete | `omar.athlete@throughline.test` | Ten-week 10K plan |
| Coach | `coach@throughline.test` | Strength coach and multi-athlete roster |
| Coach | `noura.coach@throughline.test` | Speed and conditioning coach |

Demo data is local/test data. Never run `migrate:fresh --seed` against a
production database.

## Primary athlete schedule

The primary athlete receives three programs that cover the next 12 weeks:

| Program | Frequency | Purpose |
| --- | --- | --- |
| 12-Week Strength Architecture | Monday, Wednesday, Friday | Primary strength and power progression |
| Mobility & Recovery Track | Tuesday, Sunday | Maintain movement quality and readiness |
| Aerobic Engine Builder | Thursday | Build conditioning without compromising strength |

Saturday is deliberately left open. Six hard training days would be stupid;
the mobility and recovery sessions are low intensity and should reduce fatigue.

## Phase progression

### Weeks 1-4: Foundation

- Establish clean movement positions and honest RPE logging.
- Finish most strength sets with two or three reps in reserve.
- Build a repeatable recovery and check-in habit.
- Progress only when technique remains stable across all prescribed sets.

### Weeks 5-8: Build

- Increase strength load gradually while keeping one or two reps in reserve.
- Introduce controlled threshold work in the aerobic program.
- Increase usable range in mobility sessions without forcing end positions.
- Coach reviews completion, average RPE, soreness, sleep, and missed sessions
  before increasing volume.

### Weeks 9-12: Performance

- Reduce unnecessary volume and preserve high-quality heavy work.
- Test repeatable strength, not reckless one-rep-max attempts.
- Consolidate aerobic capacity with one focused conditioning exposure weekly.
- Finish with a coach review of adherence, performance, recovery, and the next
  block recommendation.

## Progress review cadence

- Athletes record weight, protein, hydration, sleep, soreness, and energy at
  least three times per week.
- Coaches review missed workouts and soreness immediately.
- Every fourth week is a formal review point for completion percentage, average
  RPE, progress trend, and phase readiness.
- A phase does not advance automatically when pain, repeated missed sessions,
  or declining recovery signals suggest the athlete is not ready.

## API and client contract

| Capability | API | Web | Mobile |
| --- | --- | --- | --- |
| Keep signed in | Expiring remembered token | Laravel remember cookie | Secure retained token |
| Biometric unlock | Token remains server-authorized | Not applicable to browser sessions | Fingerprint/face protects retained token |
| Assigned program list | `/api/v1/app/programs` | Athlete home program cards | Programs tab |
| Program phases and completion | `/api/v1/app/programs/{assignment}` | Program detail | Program detail |
| Monthly calendar | `/api/v1/app/calendar` | Athlete home calendar | Schedule tab |
| Workout execution | `/api/v1/app/workouts/{workout}` | Workout detail | Workout execution |
| Progress history | `/api/v1/app/progress` | Progress panel | Progress tab |

Biometric unlock is device-specific and therefore has no browser clone. The
web equivalent is the existing `Remember me` session. All training features
must remain available on both web and mobile before a release is considered
complete.

## Release checks

1. Run the full Laravel test suite and formatting checks.
2. Run Flutter analysis and tests.
3. Seed an isolated SQLite database and confirm three primary assignments,
   workouts spanning at least three calendar months, and historical progress.
4. Verify the web athlete home and every program detail page.
5. Verify mobile password login, keep-signed-in restart, biometric unlock,
   password fallback, program detail, three calendar months, and workout open.
6. Build the Android APK with the intended API URL and test on a physical phone.
