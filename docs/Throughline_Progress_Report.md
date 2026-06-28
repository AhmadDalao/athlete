# Throughline Progress Report

Date: 2026-06-27

## Current build status

The MVP now has these real product surfaces:

- dashboard
- athlete app
- workout execution
- messaging
- progress workspace
- roster workspace
- training workspace
- memberships control
- wearable control
- admin control center
- admin user control
- global workspace search
- admin audit log
- admin email delivery log

The current build supports:

- role-aware access for owners, admins, coaches, and athletes
- owner/admin account control with explicit permission groups and safe owner-only owner creation
- richer signup and onboarding with phone, goal, and contact-preference capture
- live Google sign-in and sign-up with role-aware account creation
- live Apple sign-in and sign-up with the same role-aware account creation path
- live phone OTP sign-up and login with session-safe challenge verification
- coach and admin roster assignment control
- coach program assignment and session planning
- structured workout prescriptions with sets, reps, load, rest, target, and note support
- athlete workout logging with one log per session
- athlete app home at `/app`
- per-set workout execution at `/app/workouts/{trainingSession}`
- workout journal fields for duration, RPE, energy, soreness, stress, sleep quality, and notes
- workout media playback for YouTube, Vimeo, direct video URLs, or external links
- coach-athlete async messaging at `/messages`
- membership lifecycle tracking and cron-safe auditing
- payment event tracking and manual membership operations
- device connections with ingest-key mode and OAuth-ready provider mode
- raw wearable ingest archival
- normalized daily recovery and activity snapshots
- detailed sleep and recovery metrics
- manual athlete check-ins for food, body, hydration, soreness, stress, sleep quality, and energy
- token-authenticated API v1 surfaces for dashboard, roster, training, progress, memberships, wearables, and admin control
- WHOOP OAuth connection scaffolding and cron-safe sync plumbing
- signed WHOOP webhook intake with replay protection and cron-safe processing
- wearable trend analytics for coaches and athletes
- athlete-first premium surfaces for dashboard, training, recovery, and progress views
- phone-first athlete app direction based on the reference app video: today workout, wearable prompt, exercise rows, journal/media actions, and sticky bottom navigation
- admin ops triage for renewals, payment failures, coach load, coverage gaps, and stale progress logging
- KONA-style operational navigation with topbar search, table-first admin logs, CSV exports, and direct next-action cards

## 2026-06-27 athlete app and workout execution slice

This slice turned the athlete app direction into real product routes instead of a dashboard preview.

### Athlete app

- Added `/app` as the athlete-first landing surface.
- Athlete users now land on `/app` after login, registration, Google signup, or phone signup.
- Existing `/dashboard` remains available as the deeper analytics dashboard.
- `/app` now shows:
    - green athlete header with athlete, coach, and training block context
    - readiness / wearable card
    - clean wearable empty state with `/wearables` shortcut
    - today workout card with coach instructions and exercise preview
    - membership plan, status, days remaining, and renewal/end date
    - latest progress snapshot for weight, calories, protein, hydration, energy, and soreness
    - coach assignment card with `/messages` shortcut
    - feed items from system notifications
    - sticky mobile bottom navigation for Feed, Board, Workout, Messages, and Profile

### Workout execution

- Added `/app/workouts/{trainingSession}`.
- Added `workout_set_logs` for set-by-set execution:
    - exercise index/name
    - set number
    - target reps/load/rest
    - actual reps/load/RPE
    - completed timestamp
    - notes
- Added journal columns to `workout_logs`:
    - `energy_score`
    - `soreness_score`
    - `stress_score`
    - `sleep_quality_score`
- The workout screen now has:
    - Workout tab with exercise navigation, rest timer, and per-set execution table
    - Journal tab with session notes and quick subjective scores
    - Media tab with embedded video support
    - Save partial, Complete workout, and Opt out / missed actions
- Completion rules:
    - saving set rows creates or updates a partial/completed workout log
    - all sets completed marks the workout completed
    - opt-out marks the workout missed
    - only the assigned athlete can write execution data

### Coach workflow cleanup

- The coach program/session form now uses a structured exercise builder table.
- Coaches enter exercise name, sets, reps/time, load, rest, target, and notes as rows.
- The old pipe-text parser still works through the advanced paste area.
- The backend data shape remains compatible with existing `training_sessions.exercises` JSON.

### Messaging V1

- Added `coach_athlete_messages`.
- Added `/messages` for simple async coach-athlete messaging.
- Athletes can message assigned active coaches.
- Coaches can message assigned active athletes.
- Unassigned users cannot message random coaches or athletes.
- This is intentionally not real-time for MVP; normal Inertia post/refresh keeps it shared-hosting safe.

### API additions

- Added mobile-ready execution endpoints:
    - `GET /api/v1/training/sessions/{trainingSession}/execution`
    - `POST /api/v1/training/sessions/{trainingSession}/sets`
    - `POST /api/v1/training/sessions/{trainingSession}/complete`
- These use existing token abilities:
    - `training:read`
    - `training:write`

### Regression result

- `npx eslint resources/js --max-warnings=0`: passed.
- `npm run build:athlete`: passed.
- `php artisan test`: 131 tests passed, 1223 assertions.

## 2026-06-27 owner/admin permissions and athlete app direction

This slice added the owner/admin control model requested after comparing Throughline with the existing inventory admin workflow.

### Owner and admin control

- Added `owner` as a first-class role above `admin`.
- Added explicit user permissions grouped by:
    - overview
    - people
    - performance
    - commercial and API
    - system
- Added `position` / title on user accounts so admins can label users as Owner / General Manager, Head Coach, Support Admin, and similar roles.
- Admin users can create and edit users, roles, position, onboarding metadata, and permissions from `/admin/users`.
- Owner accounts always receive every permission.
- Normal admins cannot create owner accounts or edit existing owner accounts.
- The system blocks removal of the last owner account so the platform cannot lock itself out.
- The hardening command `throughline:security:lock-demo-users` now creates a real owner/admin account and clears explicit permissions because owners inherit all permissions.
- Seed data now makes the primary demo platform account an owner/admin account.

### User tracking tables

- `/admin/users` remains table-first and now shows:
    - user name and email
    - roles
    - signup channel
    - position
    - permission count
    - contact verification state
    - subscription state and dates
    - memberships, devices, and active athlete count
    - direct profile and edit actions
- `/admin/users/{user}` now shows the user's full access permission table in addition to subscriptions, payments, devices, roster links, and training programs.

### Athlete-facing app direction

- The athlete dashboard now includes a phone-first workout slice at the top of the page.
- It follows the reference video direction:
    - green athlete header
    - welcome state
    - exertion/readiness score
    - wearable connection prompt
    - today workout
    - exercise rows with sets, reps/time, load, rest, target, notes, and media state
    - journal, media, opt-out actions
    - bottom-nav model for Feed, Board, Workout, Chat, and Profile
- The existing deeper athlete analytics remain below it. The athlete sees today's work first, then recovery, food/body, training history, membership, and coach context.

### Regression result

- `npm run build:athlete`: passed.
- `php artisan test`: 123 tests passed, 1146 assertions.

### Important implementation notes

- This is still a web MVP, not a native mobile app.
- Apple Health remains a later native-app path; the current web surface is ready for WHOOP and generic wearable data already modeled in the app.
- Permissions currently use explicit per-user rows when customized. If no custom rows exist, users fall back to role defaults.

## Phase status

- Phase 1 core MVP is functionally complete for dashboards, memberships, training, onboarding, admin control, and generic wearable ingest.
- Phase 2 hardening is now functionally complete for social auth, phone OTP auth, wearable sync review, signed Stripe webhooks, and signed WHOOP webhooks.
- Phase 3 dashboard-first UX completion is now functionally in place:
    - `/dashboard` is split into role-specific compositions behind the same route
    - memberships and wearables now sit on the same shared workspace system as roster and training
    - admin control center and admin users now align with the same light-only product language
- Still missing before a serious live launch: richer CMS-style admin controls, more operator tooling around retries/reconnects, and a fresh full live browser validation pass on production-like data.

## 2026-06-23 table-first workspace correction

This slice corrected the main UX problem raised during review: operational data must be shown as rows, not hidden inside large cards.

### Updated

- Dashboard:
    - admin urgent renewals, payment issues, and device issues now render in one operations queue table
    - admin signup mix and platform reference data now render as tables
    - coach attention, scheduled sessions, missing coverage, roster reference, and weekly briefs now render as tables
    - athlete device connections, next-session exercises, upcoming sessions, recent workout logs, membership status, and coach assignments now render as tables
- Workspace pages:
    - roster assignments now render as a coach-athlete tracking table
    - memberships now uses a membership queue table and recent payment activity table
    - wearables now uses review queue and connection directory tables
    - training programs now render as a program table with nested session and exercise prescription tables
    - progress now uses tables for athlete check-ins and coach/admin athlete progress queues
    - notifications now uses a notification table
    - API access now uses token and managed connection tables
    - admin control center queues now use tables for renewals, signup mix, payments, devices, athlete gaps, and coach load

### Rule now used in the UI

- Summaries, metrics, and quick navigation can stay as cards.
- Any repeatable operational record list must be a table: users, athletes, memberships, payments, devices, sessions, check-ins, logs, notifications, and queues.

### Regression result

- `npm run build:athlete`: passed.
- `php artisan test`: 120 tests passed, 1117 assertions.
- `npx eslint resources/js --max-warnings=0`: passed.

### Production deploy result

- Deployed to `https://athlete.ahmaddalao.com`.
- Laravel config, route, and view caches rebuilt.
- No database changes or migrations were run for this slice.
- Live smoke passed:
    - `/` and `/login` return HTTP 200
    - protected workspace routes redirect to `/login`
    - new dashboard, roster, membership, wearable, training, progress, notification, API access, and control-center asset chunks return HTTP 200

## 2026-06-23 KONA-style operations pass

The reference pattern from the KONA inventory system is now applied where it makes sense for Throughline:

- A global header search now routes to `/search`.
- Search is role-aware:
    - admins can search users, athletes, training, memberships, wearables, and notifications
    - coaches can search their assigned athletes and owned work
    - athletes only see their own data
- Admin sidebar now exposes:
    - Audit log
    - Email logs
    - Website control
- `/admin/audit-log` now provides:
    - simple metric cards
    - search, action, entity, and date filters
    - table-first system activity
    - CSV export
- `/admin/email-logs` now provides:
    - total attempts, sent, attempting, and failed counters
    - search, status, type, and date filters
    - delivery attempts table
    - CSV export
    - direct link to Website control / email settings
- Admin dashboard next-actions now include Audit log and Email logs so operators do not hunt for accountability tools.

Important implementation note:

- Laravel exposes generic mail sending and sent events, but not one universal failed event. The MVP records each send attempt and updates it when Laravel confirms delivery. Specific workflow failure logging can be added later where we own the failure path.

## What this slice added

### Billing and access cleanup

- Stripe checkout and billing portal are now wired into the memberships surface.
- Signed Stripe webhooks now sync memberships and record invoice outcomes into `payment_events`.
- New billing metadata is stored on users, plans, and memberships so provider-managed access is not hidden behind manual notes.
- Webhook replay protection now exists through `billing_webhook_events`.
- Post-login routing is now role-based:
    - admins land in control center
    - coaches land in roster
    - athletes stay on dashboard
- Sidebar navigation now puts the real primary workspace first instead of pretending every role starts from the same place.
- A dedicated API access page now exists for authenticated users:
    - create personal bearer tokens from the UI
    - revoke old tokens
    - view manageable ingest endpoints and keys
    - copy curl examples for token issue, dashboard reads, and device ingest
- The shared dashboard entry is cleaner now:
    - stronger hero section
    - clearer quick actions
    - direct API access shortcut
    - less “where the hell do I click first” energy

### Phase 2 hardening and Phase 3 start

- Device connections now store sync review state:
    - `last_sync_started_at`
    - `last_error_at`
    - `last_error_message`
    - `sync_failures_count`
- WHOOP sync failures now leave a real repair trail instead of silently downgrading the connection and hoping someone notices.
- The wearables surface now includes a connection review queue with:
    - severity
    - issue summary
    - repair recommendation
    - stale-hour visibility
    - recent failure count
- The admin control center now exposes the same sync review context in its device queue so ops can see why a connection is degraded.
- Coach dashboards and roster cards now include weekly athlete briefs:
    - recovery average
    - sleep trend
    - compliance signal
    - latest energy / soreness context
    - a simple stable / medium / high priority label
- Phase 3 started with a public coach discovery page:
    - public `/coaches` route
    - searchable coach list
    - live roster/program/device-backed coach signals
    - direct handoff into the contact page for coach inquiries
- The frontend foundation has now been overhauled so the product stops feeling like mixed prototypes:
    - dark mode removed in favor of one consistent light system
    - grouped sidebar navigation by role instead of one long flat list
    - cleaner workspace header with obvious page title, role chip, and support/account shortcuts
    - updated button, card, input, select, textarea, and badge primitives
    - lighter auth, marketing, and contact surfaces with a consistent visual language
    - refreshed athlete/workspace hero sections so detail-heavy screens still feel navigable
    - deeper UX cleanup on the heavy workspaces:
        - roster now leads with clear operating context, assignment coverage, and direct next actions
        - training now separates program composition from program review instead of cramming both into one undifferentiated slab

### Phase 3 dashboard-first UX completion

- `resources/js/pages/dashboard.tsx` is no longer the giant mixed-role dumping ground:
    - the shared page now owns the hero, role switch, and composition wiring only
    - admin, coach, athlete, and mixed-role athlete-reference surfaces live in dedicated `resources/js/pages/dashboard-view/*` files
- The admin dashboard now reads like an operations board:
    - renewals
    - payment issues
    - device issues
    - user mix
    - direct next actions
- The coach dashboard now reads like a decision board:
    - who needs attention
    - what is scheduled
    - what is missing
    - what to open next
- The memberships workspace now follows the shared hero / metrics / panel system:
    - athlete billing state is surfaced first
    - operator technical detail is still present, but visually lower
    - recent payment activity is separated from the core membership state
- The wearables workspace now follows the same hierarchy:
    - connection health and WHOOP state first
    - broken / stale review queue second
    - full connection directory after that
    - webhook URLs, curl samples, and sync commands pushed down into advanced detail
- Admin consistency cleanup also landed on:
    - `resources/js/pages/admin/control-center.tsx`
    - `resources/js/pages/admin/users/index.tsx`

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
- `platform_audit_logs`
    - `actor_user_id`
    - `action`
    - `entity_type`
    - `entity_id`
    - `summary`
    - `metadata`
    - `ip_address`
    - `user_agent`
- `email_delivery_logs`
    - `status`
    - `mailer`
    - `mailable`
    - `message_id`
    - `recipient`
    - `subject`
    - `source`
    - `error`
    - `metadata`
    - `attempted_at`
    - `sent_at`
    - `failed_at`

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
| `app/Services/Whoop/WhoopWebhookService.php`                | `handleWebhook()` / `processPendingEvents()` / `processEvent()`                      | Verifies signed WHOOP webhook traffic, deduplicates by `trace_id`, and triggers targeted sync work.  | WHOOP changes can reach the platform quickly without turning shared hosting into a timeout factory.       |
| `app/Services/SocialAuthService.php`                        | `isProviderEnabled()` / `syncUserFromProvider()`                                     | Centralizes provider readiness checks and social account linking / creation rules.                   | Google and Apple auth stay coherent instead of splintering across controllers.                            |
| `app/Services/PhoneAuthService.php`                         | `startChallenge()` / `verifyChallenge()` / `isReady()`                               | Owns OTP challenge issuance, delivery, verification, and phone-auth readiness rules.                 | Phone sign-up and login are real flows now instead of future-tense UI copy.                               |
| `app/Http/Controllers/Admin/ControlCenterController.php`    | `__invoke()`                                                                         | Centralizes renewal risk, payment issues, device issues, coach load, and athlete coverage gaps.      | Admins get one ops page instead of hunting through disconnected views.                                    |
| `app/Http/Controllers/SearchIndexController.php`            | `__invoke()`                                                                         | Runs one role-aware global search across users, training, memberships, wearables, and notifications. | Users can find platform records quickly without learning every module first.                              |
| `app/Http/Controllers/Admin/AuditLogIndexController.php`    | `__invoke()` / `export()`                                                            | Lists, filters, paginates, and exports recorded platform activity.                                  | Admins can answer who changed what without touching the database.                                         |
| `app/Http/Controllers/Admin/EmailLogIndexController.php`    | `__invoke()` / `export()`                                                            | Lists, filters, paginates, and exports recorded email delivery attempts.                            | Admins can debug password reset and workflow email delivery without guessing.                             |
| `app/Services/PlatformAuditLogger.php`                      | `record()`                                                                           | Writes non-blocking audit records for important product actions.                                    | User, billing, roster, training, API token, notification, and check-in changes become traceable.          |
| `app/Services/EmailDeliveryLogger.php`                      | `recordSending()` / `recordSent()`                                                   | Captures Laravel mail attempts and updates them after successful send confirmation.                  | Email delivery has an operational trail instead of disappearing into mailer internals.                    |
| `app/Models/CoachAthleteAssignment.php`                     | `syncLifecycleDates()`                                                               | Keeps started and ended dates aligned with assignment status.                                        | Archived assignments close cleanly instead of leaving fake live relationships behind.                     |
| `app/Models/DeviceConnection.php`                           | `usesOauth()` / `tokenExpiresSoon()`                                                 | Encodes connection-mode logic and token-expiry checks in one place.                                  | OAuth-backed providers can be treated differently from ingest-key providers.                              |
| `app/Models/MetricSnapshot.php`                             | `sleepNeedHours()` / `sleepDebtHours()` / `readinessBand()`                          | Adds recovery-friendly helpers on top of normalized raw values.                                      | UI can show understandable recovery states instead of recalculating them everywhere.                      |
| `app/Support/AuthMethodCatalog.php`                         | `guestMethods()`                                                                     | Builds auth-method metadata for login and register pages based on real provider readiness.           | Guest auth pages show what is actually live instead of stale placeholders.                                |

## Frontend surfaces and why they exist

| Page                                                                          | Purpose                                                                                                                | Why it matters                                                                                              |
| ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `resources/js/pages/dashboard.tsx` plus `resources/js/pages/dashboard-view/*` | Shared dashboard shell plus decomposed role-specific compositions for admin, coach, athlete, and mixed-role reference. | This is the page that decides whether the product feels serious, and now it is no longer one giant file.    |
| `resources/js/pages/search/index.tsx`                                      | Global search surface with grouped results and direct open actions.                                    | Operators, coaches, and athletes can find the right record without navigating the whole app manually.       |
| `resources/js/pages/admin/audit-log.tsx`                                   | KONA-style audit table with filters, counters, pagination, and export.                                  | Admin accountability becomes a normal workflow, not a database chore.                                      |
| `resources/js/pages/admin/email-logs.tsx`                                  | KONA-style delivery log with filters, counters, pagination, settings link, and export.                   | Support can see whether reset/onboarding/workflow email attempted or sent.                                 |
| `resources/js/pages/progress/index.tsx`                                       | Athlete progress board plus coach/admin progress workspace for check-ins, food, body, and alert review.                | This is where wearable data stops floating around uselessly and actually meets coaching decisions.          |
| `resources/js/pages/roster/index.tsx`                                         | Coach/admin roster management with assignment editing plus membership, training, recovery, and progress context.       | The product can finally manage coach-athlete relationships from the UI instead of just assuming they exist. |
| `resources/js/pages/training/index.tsx`                                       | Coach program builder plus a dedicated athlete execution surface for sessions, prescriptions, and logs.                | This is where coaching intent actually becomes executable athlete work.                                     |
| `resources/js/pages/wearables/index.tsx`                                      | Device connection health, latest recovery metrics, 7-day trends, and a dedicated athlete recovery board.               | Wearable data is a product differentiator, so it needs a first-class control view.                          |
| `resources/js/pages/memberships/index.tsx`                                    | Membership queue, billing health, payment history, and manual operations.                                              | Subscription drift is one of the fastest ways to kill trust.                                                |
| `resources/js/pages/admin/control-center.tsx`                                 | Admin operations hub for renewals, payment queue, device failures, coach load, and ops playbooks.                      | This is the difference between metrics and actual operational control.                                      |
| `resources/js/pages/admin/users/index.tsx`                                    | Admin user control and onboarding metadata editing.                                                                    | Shared-hosting MVP or not, account operations still need a real interface.                                  |
| `resources/js/pages/auth/login.tsx`                                           | Email login plus live Google, Apple, and phone entry points when configured.                                           | Existing users get one clean access surface instead of fragmented login paths.                              |
| `resources/js/pages/auth/register.tsx`                                        | Rich onboarding flow with role-aware Google, Apple, and phone entry paths.                                             | It captures useful context now without misclassifying accounts during signup.                               |
| `resources/js/pages/auth/phone.tsx`                                           | Dedicated two-step phone auth surface for OTP request and verification.                                                | Phone auth is understandable now instead of being bolted awkwardly onto the email form.                     |

## Regression testing completed

### Automated

- `php artisan test`
- `npm run build:athlete`
- full PHP syntax sweep with `php -l` across app, routes, config, tests, and migrations
- targeted Prettier verification on the redesigned frontend files

Result summary:

- 107 tests passed
- 922 assertions passed
- production build passed

### Local UI smoke checks

Earlier local browser smoke existed for progress, roster, and athlete dashboard flows.

For this exact dashboard-first UX completion slice, a fresh browser rerun is still pending because in-app browser control was not available in the current execution thread.

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
- `docs/Throughline_Progress_Report.md`
- `docs/Throughline_Project_Plan.md`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/lib/navigation.ts`
- `resources/js/pages/admin/control-center.tsx`
- `resources/js/pages/admin/users/index.tsx`
- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/dashboard-view/admin-dashboard-composition.tsx`
- `resources/js/pages/dashboard-view/athlete-dashboard-experience.tsx`
- `resources/js/pages/dashboard-view/athlete-reference-composition.tsx`
- `resources/js/pages/dashboard-view/coach-dashboard-composition.tsx`
- `resources/js/pages/dashboard-view/helpers.tsx`
- `resources/js/pages/dashboard-view/types.ts`
- `resources/js/pages/memberships/index.tsx`
- `resources/js/pages/roster/index.tsx`
- `resources/js/pages/wearables/index.tsx`
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

The next serious slice is live-operability polish, not another dashboard rewrite.

Priority order:

1. Provider-hardening and operator retry tooling for WHOOP and billing failures.
2. Richer admin controls for content, support actions, and safer account ops.
3. Fresh live browser validation on production-like seeded data across admin, coach, and athlete roles.

## 2026-06-23 dashboard operations update

This slice moved the admin dashboard closer to the Kona reference: white workspace shell, simpler sidebar, compact metric surfaces, and operational tables instead of only large summary cards.

### Added

- Admin dashboard recent users table: shows user name, role, signup channel, created date, membership state, device count, and profile link.
- Admin dashboard athlete table: shows athlete, assigned coaches, subscription status, subscribed date, days remaining, devices, programs, and last check-in.
- Admin dashboard subscription table: shows plan, status, start date, renewal/end date, days remaining, value, and owner profile link.
- Admin user profile page at `admin/users/{user}`: shows profile/contact data, role state, signup metadata, subscription history, payment activity, devices, coach/athlete relationships, and training programs.
- Roster frontend fallback for missing weekly brief data so one incomplete payload cannot blank the whole roster page.

### Why it matters

- Admins can now answer “who are our users, who are our athletes, and when did they subscribe?” directly from the dashboard.
- User profiles give support/admin a single place to inspect subscription and activity context before making account changes.
- The dashboard now reads like an operations board instead of a marketing page.

### Regression result

- `php artisan test`: 109 tests passed, 958 assertions.
- `npm run build:athlete`: passed.
- `npx eslint resources/js --max-warnings=0`: passed.
- `npm run format:check`: passed.
- Live server smoke: public home, login, coaches, contact, dashboard redirect, manifest assets, authenticated admin dashboard payload, and admin user profile payload passed.

## 2026-06-23 admin control and workout media update

This slice filled the missing operational controls that were still not product-ready.

### Added

- Notification center at `/notifications`:
    - admins can publish messages to everyone, one role, or one user
    - users can mark one notification or all visible notifications as read
    - sidebar/header shows unread count
- System settings at `/admin/system-settings`:
    - editable platform name and support email
    - editable mail identity fields for sender/reply-to planning
    - editable homepage hero text
    - editable dashboard/system notice text for future operator messaging
- Admin user creation:
    - admins can create athletes, coaches, or admins from `/admin/users`
    - temporary password, contact preference, roles, signup channel, and verified-email state are controlled in the form
- Workout video support:
    - coaches can attach a YouTube, Vimeo, MP4, WebM, or external video URL when creating the first session or adding later sessions
    - athletes see the player in their Training page
    - the athlete dashboard shows when a next session has a video attached
    - API training/session payloads now include `videoUrl`

### Frontend stability checks

- Static route-name scan passed: every frontend `route('...')` call maps to an actual Laravel named route.
- Playwright navigation smoke passed on an isolated SQLite test server:
    - login
    - dashboard
    - admin users
    - system settings
    - notifications
    - training
    - memberships
    - wearables
    - roster
    - mobile-sized system settings and training
- No console errors or failed application asset/page requests were detected in that smoke.
- Mobile responsive sweep found and fixed horizontal overflow in dashboard, memberships, and wearables.
- Root cause:
    - shared sidebar/content flex containers were missing `min-w-0`
    - long WHOOP/webhook/ingest code strings on wearables were widening the page
- Fix:
    - app shell, content shell, and workspace primitives now shrink correctly
    - wearables technical strings now wrap inside cards instead of pushing the viewport

### Regression result

- `php artisan test`: 113 tests passed, 1014 assertions.
- `npm run build:athlete`: passed.
- `npx eslint resources/js --max-warnings=0`: passed.
- `npm run format:check`: passed.
- PHP syntax sweep across app, routes, config, database, and tests passed.
- Final Playwright navigation smoke passed on rebuilt assets for admin, coach, and athlete across desktop and mobile widths.

### Production deploy result

- Deployed to `https://athlete.ahmaddalao.com`.
- Production DB backup created at `/home/u867436826/db-backups/athlete-20260623112837.sql.gz`.
- Three new migrations applied: platform settings, system notifications, and training session video URL.
- Laravel config, route, and view caches rebuilt.
- Live smoke passed for public pages, protected dashboard redirect, live manifest, new route names, and public browser console checks.

## 2026-06-28 athlete app and operational overhaul deployment

This release shipped the athlete app workflow and the latest operational dashboard/backend slice to production at `https://athlete.ahmaddalao.com`.

### Shipped

- Athlete app routes for `/app` and workout execution at `/app/workouts/{trainingSession}`.
- Per-set workout logging with completion, partial, missed, journal, and media support.
- Coach-athlete messaging route at `/messages`.
- API execution endpoints for future mobile/app integration.
- Admin user profiles, owner/admin permission controls, system settings, notifications, API access, billing scaffolding, and table-first workspace improvements.
- Light-mode dashboard compositions and updated athlete/coach/admin navigation.

### Production deploy result

- GitHub `main` pushed at commit `c230a02`.
- Production DB backup created at `/home/u867436826/db-backups/athlete-20260628064145.sql.gz`.
- Production source snapshot created at `/home/u867436826/db-backups/athlete-source-20260628064049.tar.gz`.
- Four migrations applied: owner permissions, workout set logs, workout journal fields, and coach-athlete messages.
- Composer autoload refreshed, Laravel caches cleared, and config/routes/views rebuilt.
- Live manifest checksum matched local and both remote build directories.

### Regression result

- Local pre-push checks passed: `npm run build:athlete`, `npx eslint resources/js --max-warnings=0`, and `php artisan test`.
- Full Laravel suite passed: 131 tests, 1223 assertions.
- Production smoke passed for public pages, protected redirects, route cache, manifest assets, admin/coach/athlete authenticated route rendering, and mobile public browser console checks.
