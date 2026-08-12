# Throughline Platform Guide

Last updated: 2026-08-12

## Architecture

Throughline is one multi-tenant coaching platform with two clients:

- Web: Laravel 12, PHP 8.2, Blade, Livewire 4, Bootstrap 5, Font Awesome, and Vite.
- Mobile: Flutter with Riverpod, GoRouter, Dio, SecureStorage, Drift, cached images, image picking, and native video playback.
- Backend: Laravel services, policies/permissions, database notifications, database-backed jobs, scheduled commands, and Sanctum.
- Data: MySQL on Hostinger and SQLite for automated tests.
- Realtime model: Livewire AJAX and regular API refresh. No Redis, WebSockets, or fake realtime claims.

The previous React/Inertia application is archived. Production data is migrated forward; releases never use destructive fresh migrations.

## Roles And Tenancy

- Platform owner: full access through `Gate::before`; cannot be locked out.
- Platform admin: platform access according to explicit permissions.
- Organization owner: all non-platform permissions inside owned organizations.
- Organization admin: people, coaching, schedules, reports, and communication inside assigned organizations.
- Coach: only assigned athletes, coach-owned programs, schedules, reports, invitations, and conversations.
- Athlete: only personal assignments, workouts, progress, photos, notifications, and conversations.

Users can belong to multiple organizations and select an active organization. Organization-owned models use `BelongsToOrganization`; routes, Livewire actions, exports, and `/api/v1` requests enforce the same boundary.

## Web Routes

### Public And Auth

| Route | Purpose |
| --- | --- |
| `/`, `/features`, `/pricing`, `/contact` | Public marketing, pricing, and contact capture |
| `/login`, `/forgot-password`, `/reset-password/{token}` | Authentication and recovery |
| `/invites/{token}` | Invitation acceptance |
| `/dashboard` | Role redirect only |

### Admin

| Route | Purpose | Permission |
| --- | --- | --- |
| `/admin/dashboard` | Operations summary | `admin.access` |
| `/admin/organizations` | Organization list, members, and exports | `organizations.manage` |
| `/admin/users`, `/coaches`, `/athletes` | Account tables and details | `admin.access` |
| `/admin/invitations` | Invitation operations and export | `invitations.manage` |
| `/admin/reports` | Organization coaching delivery and adherence | `reports.view` |
| `/admin/contact-submissions` | Public inquiry inbox | `admin.contacts` |
| `/admin/permissions` | Grouped permissions and overrides | `admin.permissions` |
| `/admin/settings` | Website, invitation, mail, and system control | `admin.settings` |
| `/admin/email-logs` | Mail attempts, failures, filters, and export | `admin.audit` |
| `/admin/audit-log` | Sensitive system action trail and export | `admin.audit` |

Admin and coach list pages use the same Livewire contract: debounced search, AJAX filters, `10/25/50/100/All`, Bootstrap pagination, CSV export where operationally useful, and table-contained horizontal scrolling.

### Coach

| Route | Purpose |
| --- | --- |
| `/coach` | Coach command board |
| `/coach/athletes` and `/{athlete}` | Scoped roster and athlete source of truth |
| `/coach/programs` and `/{program}` | Reusable program, phase, session, and exercise builder |
| `/coach/exercises` | Exercise library |
| `/coach/schedule` | Assigned workout schedule and rescheduling |
| `/coach/reports` | Athlete adherence and execution reporting |
| `/coach/invitations` | Athlete invitations |
| `/coach/messages` | Organization-scoped conversations |

### Athlete

| Route | Purpose |
| --- | --- |
| `/app` | Today, calendar, active assignments, and coach context |
| `/app/programs/{assignment}` | Assigned program, sessions, and media |
| `/app/workouts/{workout}` | Set execution, timers, RPE, notes, and completion state |
| `/app/progress` | Check-ins, charts, records, and photos |
| `/app/messages` | Coach conversations |
| `/app/profile` | Profile, organization, appearance, and account controls |

## Core Workflows

1. Owner creates or manages an organization and its members.
2. Coach invites an athlete or works with an existing organization member.
3. Coach builds a reusable program template with phases, sessions, exercises, targets, rest, notes, images, and video URLs.
4. Coach assigns the program; `ProgramScheduleService` generates dated scheduled workouts.
5. Athlete opens the calendar and logs actual sets, reps, load, RPE, duration, notes, and status.
6. Coach reviews adherence, progress entries, photos, set logs, and messages.
7. Admin monitors organization delivery, email failures, permissions, public content, and audit history.

## Data Model

Identity and control:

- `users`, `organizations`, `organization_memberships`
- `user_permissions`, `membership_permission_overrides`
- `platform_settings`, `organization_settings`
- `audit_logs`, `email_logs`, `contact_submissions`

Coaching:

- `athlete_profiles`, `coach_profiles`, `coach_athlete_assignments`
- `athlete_invitations`
- `exercise_library`, `training_programs`, `program_phases`
- `training_sessions`, `training_session_exercises`
- `program_assignments`, `scheduled_workouts`
- `workout_logs`, `workout_set_logs`

Progress and communication:

- `progress_entries`, `progress_photos`, `personal_records`, `coach_notes`
- `conversations`, `conversation_participants`, `messages`, `media_assets`
- Laravel database notifications and Sanctum personal access tokens

The key chain is:

`Organization -> Program Template -> Program Assignment -> Scheduled Workout -> Workout Log -> Set Logs`

## API And Flutter

The versioned API lives under `/api/v1` and uses consistent JSON envelopes with `data`, `meta`, `links`, and structured errors. The active organization is validated from `X-Organization-ID`. Sanctum tokens are stored only in Flutter SecureStorage.

Flutter provides role-adaptive athlete and coach navigation, organization switching, login/logout, calendar, programs, workout execution, progress, invitations, roster, messaging, notifications, media, and profile controls. Workout drafts use Drift for unreliable connections and server timestamps for conflict handling.

See `docs/openapi.yaml` and `docs/API_v1.md` for endpoint contracts.

## UI Rules

- Theme modes: system, dark, and light.
- Records live in tables; cards summarize.
- Admin remains operational and dense without becoming cluttered.
- Mobile navigation keeps primary athlete and coach actions visible.
- No route names or language expose admin concepts to athletes.
- Every major state has loading, empty, validation, permission-denied, and expired-session handling.

## Verification

Run before every release:

```bash
composer validate --strict
./vendor/bin/pint --test
php artisan test
npm run build
php artisan route:cache
php artisan view:cache
cd mobile && flutter analyze --no-pub --suppress-analytics && flutter test --no-pub --suppress-analytics
```

Use `scripts/hostinger-finalize-release.sh` only after an atomic release has been moved to its final application path. It refuses temporary release paths and verifies cached view roots.

## Deferred Integrations

Payments, automated subscriptions, WHOOP, Health Connect, Apple Health, social login, and push notifications are deliberately outside the accepted core coaching release. They should be added only after web and Flutter coaching acceptance.
