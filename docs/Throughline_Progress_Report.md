# Throughline Progress Report

Date: 2026-06-15

## Current build status

The MVP now has eight real product surfaces:

- dashboard
- progress workspace
- roster workspace
- training workspace
- memberships control
- wearable control
- admin control center
- admin user control

The current build supports:

- role-aware access for admins, coaches, and athletes
- richer signup and onboarding with phone, goal, and contact-preference capture
- live Google sign-in and sign-up with role-aware account creation
- staged Apple and phone signup visibility without lying that those flows are live
- coach and admin roster assignment control
- coach program assignment and session planning
- structured workout prescriptions with sets, reps, load, rest, target, and note support
- athlete workout logging with one log per session
- membership lifecycle tracking and cron-safe auditing
- payment event tracking and manual membership operations
- device connections with ingest-key mode and OAuth-ready provider mode
- raw wearable ingest archival
- normalized daily recovery and activity snapshots
- detailed sleep and recovery metrics
- manual athlete check-ins for food, body, hydration, soreness, stress, sleep quality, and energy
- token-authenticated API v1 surfaces for dashboard, roster, training, progress, memberships, wearables, and admin control
- WHOOP OAuth connection scaffolding and cron-safe sync plumbing
- wearable trend analytics for coaches and athletes
- athlete-first premium surfaces for dashboard, training, recovery, and progress views
- admin ops triage for renewals, payment failures, coach load, coverage gaps, and stale progress logging

## Phase status

- Phase 1 core MVP is functionally complete for dashboards, memberships, training, onboarding, admin control, and generic wearable ingest.
- Phase 2 recovery intelligence is underway and now includes wearable trends, WHOOP scaffolding, and a real progress/check-in layer.
- Still missing before a serious live launch: Apple auth, phone auth, billing provider integration, and provider webhook hardening.

## What this slice added

### Product functions

- Athletes now have a dedicated progress workspace:
    - daily check-ins for weight, body fat, waist, calories, protein, carbs, fat, water, soreness, stress, sleep quality, and energy
    - recent progress timeline and simple alerting
    - a clean split between manual signals and wearable-imported signals
- Coaches and admins now get a progress queue:
    - assigned-athlete food/body summaries
    - latest check-in visibility
    - flags for low energy, poor hydration, low protein, large weight shifts, and missing recent check-ins
- Dashboard and roster pages now surface progress data instead of making coaches dig:
    - latest weight and body/fuel snippets
    - progress quick links
    - coach roster risk flags tied to real check-in data
- Seeded demo data is now useful instead of empty:
    - 6 demo users
    - 3 coach-athlete assignments
    - 21 athlete check-ins across 7 days
    - wearable, membership, training, and payment records that line up with those users

### Seeded demo users

All seeded users use the password `password`.

- `admin@throughline.test`
- `coach@throughline.test`
- `coach2@throughline.test`
- `athlete1@throughline.test`
- `athlete2@throughline.test`
- `athlete3@throughline.test`

Assignment map:

- `coach@throughline.test` -> `athlete1@throughline.test`
- `coach@throughline.test` -> `athlete2@throughline.test`
- `coach2@throughline.test` -> `athlete3@throughline.test`

### Data model added or extended

- `social_accounts`
    - `provider`
    - `provider_user_id`
    - `provider_email`
    - `provider_avatar`
    - `access_token`
    - `refresh_token`
    - `token_expires_at`
    - `provider_payload`
- `device_connections`
    - `auth_type`
    - `access_token`
    - `refresh_token`
    - `token_expires_at`
    - `provider_account_payload`
- `metric_snapshots`
    - `sleep_need_minutes`
    - `sleep_performance_percentage`
    - `sleep_consistency_percentage`
    - `sleep_efficiency_percentage`
    - `rem_sleep_minutes`
    - `slow_wave_sleep_minutes`
    - `respiratory_rate`
    - `blood_oxygen_percent`
    - `skin_temperature_celsius`
- `athlete_check_ins`
    - `logged_date`
    - `weight_kg`
    - `body_fat_percentage`
    - `waist_cm`
    - `calories_consumed`
    - `protein_grams`
    - `carbs_grams`
    - `fat_grams`
    - `water_liters`
    - `meals_logged_count`
    - `energy_score`
    - `soreness_score`
    - `stress_score`
    - `sleep_quality_score`
    - `notes`

## Key backend functions and why they exist

| File / class                                                | Function                                                                             | Why it exists                                                                                        | User-facing effect                                                                                        |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/DashboardController.php`              | `__invoke()`                                                                         | Composes role-aware dashboard props from memberships, training, wearables, and progress data.        | Each role lands on a dashboard that actually reflects their job.                                          |
| `app/Http/Controllers/DashboardController.php`              | `coachOverview()` / `athleteOverview()`                                              | Layers manual progress analytics on top of training and wearable summaries.                          | Coaches and athletes can see food, body, recovery, and plan context in one place.                         |
| `app/Http/Controllers/ProgressIndexController.php`          | `__invoke()`                                                                         | Builds the role-aware progress workspace with check-ins, alerts, and summary cards.                  | Athlete, coach, and admin users all get the progress data they should see and none they should not.       |
| `app/Http/Controllers/AthleteCheckInStoreController.php`    | `__invoke()`                                                                         | Validates and creates one athlete check-in per day.                                                  | Athletes can log daily food/body/recovery context without trashing data consistency.                      |
| `app/Http/Controllers/AthleteCheckInUpdateController.php`   | `__invoke()`                                                                         | Lets an athlete safely revise their own daily check-in.                                              | Check-ins stay editable without letting people stomp each other's data.                                   |
| `app/Http/Controllers/RosterIndexController.php`            | `__invoke()`                                                                         | Builds the roster workspace with assignment, membership, recovery, training, and progress context.   | Coaches and admins can manage real roster relationships from one page instead of guessing across screens. |
| `app/Http/Controllers/RosterAssignmentStoreController.php`  | `__invoke()`                                                                         | Creates or reactivates coach-athlete assignments from validated roster input.                        | New roster relationships can start from the UI without backend cleanup.                                   |
| `app/Http/Controllers/RosterAssignmentUpdateController.php` | `__invoke()`                                                                         | Updates assignment status, goal, notes, and lifecycle dates with role-aware access checks.           | Coaches can maintain their roster cleanly, and admins can repair bad state centrally.                     |
| `app/Http/Controllers/TrainingIndexController.php`          | `__invoke()`                                                                         | Builds the training workspace by role.                                                               | Coaches and athletes see the right programs, sessions, and workout logs.                                  |
| `app/Http/Controllers/TrainingIndexController.php`          | `normalizeExercises()`                                                               | Makes exercise JSON predictable before it reaches React.                                             | Training prescriptions render cleanly instead of forcing frontend hacks.                                  |
| `app/Support/TrainingExerciseParser.php`                    | `parse()` / `parseLine()` / `buildPrescription()`                                    | Converts coach text input into structured exercise rows and stable display strings.                  | Coaches can build sessions quickly without a bloated form builder.                                        |
| `app/Http/Controllers/WearableIndexController.php`          | `__invoke()`                                                                         | Builds wearable connection cards, latest metrics, analytics summaries, and WHOOP integration status. | Coaches and athletes get a real wearable control surface instead of a key dump.                           |
| `app/Http/Controllers/Api/DeviceMetricIngestController.php` | `__invoke()`                                                                         | Validates inbound normalized metrics and enforces ingest-key authentication.                         | External metric data can enter the app without manual database edits.                                     |
| `app/Services/DeviceMetricIngestionService.php`             | `ingest()`                                                                           | Writes raw ingest records, upserts normalized snapshots, and refreshes connection sync state.        | One ingest request updates both the audit layer and dashboard-ready metrics.                              |
| `app/Services/AthleteProgressAnalyticsService.php`          | `forUser()`                                                                          | Aggregates manual check-ins, training compliance, and alerts across a dashboard-ready time window.   | Progress pages and dashboard cards can show useful athlete trend context instead of dumb raw rows.        |
| `app/Services/MetricAnalyticsService.php`                   | `forUser()` / `forConnection()`                                                      | Aggregates wearable history into daily trend summaries.                                              | Athlete and coach views can show 7-day trends, averages, and alerts.                                      |
| `app/Services/Whoop/WhoopApiClient.php`                     | `authorizationUrl()` / `exchangeAuthorizationCode()` / `refreshAccessToken()`        | Encapsulates WHOOP OAuth token flow.                                                                 | The app has a real provider-connect path instead of a placeholder.                                        |
| `app/Services/Whoop/WhoopApiClient.php`                     | `fetchCycles()` / `fetchRecoveries()` / `fetchSleepActivities()` / `fetchWorkouts()` | Pulls WHOOP collections needed for daily recovery and training detail.                               | WHOOP data can become dashboard-ready snapshots.                                                          |
| `app/Services/Whoop/WhoopSyncService.php`                   | `syncConnection()` / `syncEligibleConnections()`                                     | Normalizes WHOOP data by day and syncs eligible connections through cron-safe paths.                 | WHOOP-backed metrics can stay fresh on cheap hosting.                                                     |
| `app/Services/SocialAuthService.php`                        | `isProviderEnabled()` / `syncUserFromProvider()`                                     | Centralizes provider readiness checks and Google account linking / creation rules.                   | Social auth stays coherent instead of splintering across controllers.                                     |
| `app/Http/Controllers/Admin/ControlCenterController.php`    | `__invoke()`                                                                         | Centralizes renewal risk, payment issues, device issues, coach load, and athlete coverage gaps.      | Admins get one ops page instead of hunting through disconnected views.                                    |
| `app/Models/CoachAthleteAssignment.php`                     | `syncLifecycleDates()`                                                               | Keeps started and ended dates aligned with assignment status.                                        | Archived assignments close cleanly instead of leaving fake live relationships behind.                     |
| `app/Models/DeviceConnection.php`                           | `usesOauth()` / `tokenExpiresSoon()`                                                 | Encodes connection-mode logic and token-expiry checks in one place.                                  | OAuth-backed providers can be treated differently from ingest-key providers.                              |
| `app/Models/MetricSnapshot.php`                             | `sleepNeedHours()` / `sleepDebtHours()` / `readinessBand()`                          | Adds recovery-friendly helpers on top of normalized raw values.                                      | UI can show understandable recovery states instead of recalculating them everywhere.                      |
| `app/Support/AuthMethodCatalog.php`                         | `guestMethods()`                                                                     | Builds auth-method metadata for login and register pages based on real provider readiness.           | Guest auth pages show what is actually live instead of stale placeholders.                                |

## Frontend surfaces and why they exist

| Page                                          | Purpose                                                                                                                | Why it matters                                                                                              |
| --------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `resources/js/pages/dashboard.tsx`            | Shared command center with role-specific panels, deeper recovery detail, and clearer next-session training visibility. | This is the page that decides whether the product feels serious.                                            |
| `resources/js/pages/progress/index.tsx`       | Athlete progress board plus coach/admin progress workspace for check-ins, food, body, and alert review.                | This is where wearable data stops floating around uselessly and actually meets coaching decisions.          |
| `resources/js/pages/roster/index.tsx`         | Coach/admin roster management with assignment editing plus membership, training, recovery, and progress context.       | The product can finally manage coach-athlete relationships from the UI instead of just assuming they exist. |
| `resources/js/pages/training/index.tsx`       | Coach program builder plus a dedicated athlete execution surface for sessions, prescriptions, and logs.                | This is where coaching intent actually becomes executable athlete work.                                     |
| `resources/js/pages/wearables/index.tsx`      | Device connection health, latest recovery metrics, 7-day trends, and a dedicated athlete recovery board.               | Wearable data is a product differentiator, so it needs a first-class control view.                          |
| `resources/js/pages/memberships/index.tsx`    | Membership queue, billing health, payment history, and manual operations.                                              | Subscription drift is one of the fastest ways to kill trust.                                                |
| `resources/js/pages/admin/control-center.tsx` | Admin operations hub for renewals, payment queue, device failures, coach load, and ops playbooks.                      | This is the difference between metrics and actual operational control.                                      |
| `resources/js/pages/admin/users/index.tsx`    | Admin user control and onboarding metadata editing.                                                                    | Shared-hosting MVP or not, account operations still need a real interface.                                  |
| `resources/js/pages/auth/login.tsx`           | Email login plus live Google login entry when configured.                                                              | Existing users get a clean social-auth path without breaking standard login.                                |
| `resources/js/pages/auth/register.tsx`        | Rich onboarding flow with role-aware live Google signup and staged Apple/phone visibility.                             | It captures useful context now and makes Google usable without misclassifying accounts.                     |

## Regression testing completed

### Automated

- `php artisan migrate:fresh --seed`
- `php artisan test`
- `npm run build`
- `npm run lint`
- `./vendor/bin/pint` on changed backend files
- `npx prettier --check` on changed frontend files
- targeted dashboard and progress tests

Result summary:

- 79 tests passed
- 657 assertions passed
- migrations and reseeding passed cleanly
- production build passed

### Local UI smoke checks

Local verification was run against `http://127.0.0.1:8003` with Playwright automation:

- athlete progress page loaded and showed `Athlete progress board`, `Weight trend`, `Protein trend`, and `Log today`
- coach progress page loaded and showed `Progress workspace`, `Athlete progress queue`, and `Visible athletes`
- admin progress page loaded and showed `Admin progress control` and `Missing recent check-in`
- coach roster page loaded and showed the new `Food and body` summary block
- athlete dashboard loaded and showed `Fuel and body`, `Latest manual check-in`, and `Open progress`

## Files added in the latest progress slice

- `app/Http/Controllers/AthleteCheckInStoreController.php`
- `app/Http/Controllers/AthleteCheckInUpdateController.php`
- `app/Http/Controllers/ProgressIndexController.php`
- `app/Http/Requests/AthleteCheckInUpsertRequest.php`
- `app/Models/AthleteCheckIn.php`
- `app/Services/AthleteProgressAnalyticsService.php`
- `database/migrations/2026_06_15_010000_create_athlete_check_ins_table.php`
- `resources/js/pages/progress/index.tsx`
- `tests/Feature/ProgressIndexTest.php`
- `tests/Feature/ApiAuthenticationTest.php`
- `tests/Feature/ApiSurfaceTest.php`

## Files updated in this slice

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/RosterIndexController.php`
- `app/Models/User.php`
- `config/throughline.php`
- `docs/Throughline_API.md`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/lib/navigation.ts`
- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/roster/index.tsx`
- `routes/web.php`
- `tests/Feature/DashboardTest.php`

## Operational notes

- Shared hosting is still the deployment target, so cron-safe design still matters more than fake real-time complexity.
- API tokens now run through Laravel Sanctum. Expired tokens should be pruned by the scheduled `sanctum:prune-expired --hours=24` command.
- WHOOP uses OAuth 2.0 and periodic sync, not ingest-key posting.
- Generic providers can still use the normalized ingest API.
- Raw ingest and normalized snapshots remain intentionally separate.
- Wearables are not enough on their own. Food intake, weight, soreness, stress, and subjective energy need a manual input path, which is why `athlete_check_ins` exists.
- Training planning stays simple on purpose: one athlete per program, one workout log per session, no overbuilt real-time stack.
- Google auth is live when configured. Apple and phone signup remain staged until their verification and abuse controls are real.

## Recommended next slice

The next serious slice is still billing/provider hardening first.

Priority order:

1. Billing provider integration with real subscription webhooks and payment reconciliation.
2. Apple and phone auth with verification, abuse controls, and recovery flows.
3. WHOOP webhook hardening, connection-failure review, and sync retry visibility.
