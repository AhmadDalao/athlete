# Throughline System Report

Date: 2026-06-28

## Executive Summary

Throughline is now a Laravel, Inertia, React, and MySQL coaching platform for coaches, athletes, and platform operators.

The platform currently supports:

- public marketing, coach discovery, contact intake, login, registration, password reset, Google auth, Apple auth scaffolding, and phone auth scaffolding
- owner, admin, coach, and athlete roles
- explicit permission groups for admin and coach access control
- coach roster management and coach-owned athlete invitations
- public invite acceptance for new and existing athletes
- athlete profile drill-down pages for coaches and admins
- athlete file library with upload, download, category, visibility, archive, and admin move controls
- training program assignment, structured exercise builder, workout logs, and set-by-set workout execution
- athlete web app at `/app` with bottom navigation and workout execution screen
- progress check-ins for weight, nutrition, hydration, body, sleep quality, stress, soreness, and energy
- membership and payment event tracking
- wearable/device connection tracking, ingest keys, WHOOP OAuth scaffolding, WHOOP webhooks, and recovery snapshots
- coach-athlete messaging
- admin control center, users, invitations, files, website settings, audit logs, email logs, global search, and API token management
- API v1 endpoints for future app/mobile/partner usage

The product is no longer only a dashboard. It now has the core cycle:

1. Coach invites athlete.
2. Athlete accepts and joins the system.
3. Coach assigns a training program and sessions.
4. Athlete sees today's work in `/app`.
5. Athlete logs workout execution set by set.
6. Athlete logs progress and health context.
7. Wearables feed recovery data.
8. Coach reviews athlete profile, progress tables, set execution, messages, devices, files, and memberships.
9. Admin/owner controls users, permissions, billing, files, logs, settings, and platform operations.

## Production Environment

Production URL:

- `https://athlete.ahmaddalao.com`

Production app layout:

- Laravel app root: `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app`
- public web root: `/home/u867436826/domains/ahmaddalao.com/public_html/athlete`
- Vite build URL base: `/build/`
- production PHP CLI/runtime: `/opt/alt/php85/usr/bin/php`
- database: MySQL on Hostinger

Current shared-hosting rule:

- no Redis dependency
- no WebSockets dependency
- no long-running workers required
- use cron-driven Laravel schedule and database queue/cache for MVP

Important hosting issue:

- Hostinger PHP 8.5 currently does not expose `ext-sodium`.
- Composer needs `--ignore-platform-req=ext-sodium`.
- Apple login dependencies can be affected until Hostinger enables `sodium`.

## Technical Stack

Backend:

- Laravel 12
- PHP 8.2+ target, production currently using PHP 8.5
- MySQL
- Laravel Sanctum for API tokens
- Laravel Mail for invitation and delivery logging
- Laravel scheduler and database-backed queues/cache for MVP-safe hosting

Frontend:

- React 19
- TypeScript
- Inertia.js
- Tailwind CSS
- Vite production build with `/build/` asset base

Why this stack is correct right now:

- Laravel handles auth, validation, routing, policies, database, queues, mail, and server rendering bridge.
- React handles complex dashboard/app interfaces.
- Inertia avoids duplicate API/auth plumbing for the web app.
- MySQL is enough for the current MVP data volume if indexes and aggregates are kept sane.

## Role Model

### Owner

Owner is the highest platform role.

Owners can:

- create owner/admin accounts
- access all permission-controlled areas
- manage platform-wide controls
- keep the business from being locked out

System rule:

- the platform must keep at least one owner account active.

### Admin

Admins manage operations.

Admins can:

- view and manage users, depending on permissions
- view all athletes, coaches, memberships, devices, files, invitations, and logs
- manage settings and website control fields
- move athlete files between athlete profiles
- review billing and operational queues

Admins cannot:

- create or edit owner accounts unless they are also owner.

### Coach

Coaches manage assigned athletes only.

Coaches can:

- invite athletes
- view their roster
- create training programs
- assign sessions and exercises
- review assigned athlete profile pages
- upload and manage files for assigned athletes
- message assigned athletes
- view progress, wearable, workout, and membership data for assigned athletes

Coaches cannot:

- view athletes assigned to another coach
- hijack athletes actively assigned to another coach
- edit athlete workout execution logs directly

### Athlete

Athletes use the app.

Athletes can:

- see `/app`
- open today's workout
- log set-by-set workout execution
- save partial workouts
- complete workouts
- mark workouts missed
- log progress check-ins
- connect/view wearables
- view memberships
- message assigned coaches
- view their own records

Athletes cannot:

- access admin pages
- access roster management
- access another athlete's data

## Permission System

Permissions are grouped by workspace:

- dashboard
- notifications
- roster
- athletes
- athlete files
- training
- progress
- wearables
- memberships
- API access
- admin users
- admin invitations
- admin system settings
- audit and email logs

The system supports:

- role defaults
- explicit per-user permission overrides
- owner inheritance of every permission
- admin UI for creating/editing users and permissions

This mirrors the inventory project's owner/admin control model, but adapted for coaches, athletes, memberships, training, and wearables.

## Main Web Routes

Public:

- `/`
- `/coaches`
- `/contact`
- `/login`
- `/register`
- `/forgot-password`
- `/invites/{token}`

Authenticated app:

- `/dashboard`
- `/app`
- `/app/workouts/{trainingSession}`
- `/messages`
- `/notifications`
- `/search`
- `/api-access`
- `/settings/profile`
- `/settings/password`

Coach/admin operations:

- `/roster`
- `/roster/invites`
- `/training`
- `/progress`
- `/memberships`
- `/wearables`
- `/athletes/{user}`
- `/athlete-files/{athleteFile}/download`

Admin/owner:

- `/admin/control-center`
- `/admin/users`
- `/admin/users/{user}`
- `/admin/invitations`
- `/admin/files`
- `/admin/audit-log`
- `/admin/email-logs`
- `/admin/system-settings`

## API Surfaces

Production API base:

- `https://athlete.ahmaddalao.com/api/v1`

Implemented API v1 endpoints:

- `POST /api/v1/auth/tokens`
- `DELETE /api/v1/auth/tokens/current`
- `GET /api/v1/me`
- `GET /api/v1/dashboard`
- `GET /api/v1/roster`
- `GET /api/v1/training`
- `POST /api/v1/training/sessions/{trainingSession}/workout-log`
- `GET /api/v1/training/sessions/{trainingSession}/execution`
- `POST /api/v1/training/sessions/{trainingSession}/sets`
- `POST /api/v1/training/sessions/{trainingSession}/complete`
- `GET /api/v1/progress`
- `POST /api/v1/progress/check-ins`
- `PATCH /api/v1/progress/check-ins/{athleteCheckIn}`
- `GET /api/v1/memberships`
- `GET /api/v1/wearables`
- `GET /api/v1/admin/control-center`

Device ingest:

- `POST /api/device-connections/{deviceConnection}/ingest`

WHOOP webhook:

- `POST /webhooks/whoop`

Stripe webhook:

- `POST /webhooks/stripe`

API auth:

- Laravel Sanctum bearer tokens
- role rules enforced on top of token abilities
- device ingest uses `X-Throughline-Key`

## Core Data Model

User/account:

- users
- roles
- permissions
- OAuth/social accounts
- phone auth challenges

Coach/athlete ownership:

- coach athlete assignments
- athlete invitations
- coach-athlete messages

Training:

- training programs
- training sessions
- workout logs
- workout set logs

Progress:

- athlete check-ins
- food/macros
- weight/body metrics
- hydration
- energy/soreness/stress/sleep quality

Membership/billing:

- membership plans
- memberships
- payment events
- Stripe customer/subscription/invoice metadata

Wearables:

- device connections
- raw device metric ingests
- daily metric snapshots
- WHOOP webhook events
- sync failure state

Admin/platform:

- platform settings
- platform audit logs
- email delivery logs
- contact submissions
- system notifications
- athlete files

## Main Product Cycle

### 1. Coach invites athlete

Route:

- `/roster/invites`

Flow:

1. Coach enters athlete name, email, phone, goal, and notes.
2. System creates `athlete_invitations`.
3. Token is stored as a hash.
4. Email is sent using configured invitation template.
5. Invitation appears in coach/admin invitation table.
6. Email delivery is logged.

Admin can also view the platform-wide version:

- `/admin/invitations`

### 2. Athlete accepts invitation

Route:

- `/invites/{token}`

Flow for new athlete:

1. Athlete opens invite link.
2. Athlete sets account details/password.
3. System creates user.
4. System assigns athlete role.
5. System creates coach-athlete assignment.
6. Invite becomes accepted.
7. Athlete can log in and land in `/app`.

Flow for existing athlete:

1. Athlete opens invite link.
2. Athlete confirms existing account.
3. System links the existing account.
4. No duplicate user is created.

### 3. Coach creates training program

Route:

- `/training`

Flow:

1. Coach chooses assigned athlete.
2. Coach creates program title, goal, dates, notes.
3. Coach adds first session.
4. Coach builds exercise rows:
    - name
    - sets
    - reps/time
    - load
    - rest
    - target
    - notes
5. Data is stored as normalized JSON in `training_sessions.exercises`.

Fallback:

- old pipe-text exercise input still works for existing data and fast paste.

### 4. Athlete executes workout

Routes:

- `/app`
- `/app/workouts/{trainingSession}`

Flow:

1. Athlete sees today's workout in `/app`.
2. Athlete starts workout.
3. Workout screen loads training session, exercises, media, and current set logs.
4. Athlete enters actual reps/time, actual load, RPE, notes, and completion state per set.
5. System writes `workout_set_logs`.
6. System creates/updates `workout_logs`.
7. Completion status becomes:
    - `completed` when all required sets are complete
    - `partial` when some work is logged
    - `missed` when athlete opts out

### 5. Coach reviews athlete profile

Route:

- `/athletes/{user}`

The athlete profile shows tables for:

- contact and goal
- coach assignment history
- memberships
- training schedule
- completed/partial/missed sessions
- set-by-set workout execution
- progress check-ins
- wearable status and latest recovery
- files
- payments
- messages

This is the core coach tracking page.

### 6. Athlete logs progress

Route:

- `/progress`

Athlete can log:

- date
- weight
- body fat
- waist
- calories
- protein
- carbs
- fat
- water
- meals logged
- energy
- soreness
- stress
- sleep quality
- notes

Coach/admin can view assigned/visible athlete progress in tables.

### 7. Wearables sync

Routes:

- `/wearables`
- `/wearables/whoop/connect`
- `/wearables/whoop/callback`
- `/webhooks/whoop`
- `/api/device-connections/{deviceConnection}/ingest`

Supported concepts:

- ingest key connection
- OAuth provider connection
- raw ingest archival
- normalized daily snapshots
- sync error tracking
- review queue for broken/stale links
- WHOOP webhook replay protection

### 8. Membership and billing control

Route:

- `/memberships`

Tracks:

- plan
- status
- start date
- renewal date
- end date
- days remaining
- billing provider
- provider IDs
- payment events

Stripe scaffolding exists:

- checkout
- portal
- webhook sync
- subscription/invoice event recording

Manual membership events exist for MVP control.

### 9. Admin control

Routes:

- `/admin/control-center`
- `/admin/users`
- `/admin/users/{user}`
- `/admin/invitations`
- `/admin/files`
- `/admin/audit-log`
- `/admin/email-logs`
- `/admin/system-settings`

Admin can:

- inspect users
- create/edit admins, coaches, athletes
- manage permissions
- review files
- review invitations
- inspect audit/email logs
- manage website/invite settings
- see operational queues and metrics

## Frontend UX State

Current visual direction:

- light mode only
- cream/white admin workspace
- KONA-style left navigation and table-first layouts
- large filter panels
- clean top metrics
- direct action buttons
- searchable/exportable tables
- athlete app uses phone-first cards and bottom navigation

Current table-first state:

- admin users: table
- admin user profile: tables
- athlete profile: tables
- roster: table
- invitations: table
- files: table
- training: nested tables
- progress: tables
- memberships: tables
- wearables: tables
- audit logs: table
- email logs: table

## Deployment Cycle

Standard release cycle:

1. Check local git status.
2. Run local validation:
    - `git diff --check`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `npm run build:athlete`
3. Commit and push to GitHub.
4. Create production database backup.
5. Create production source backup.
6. Package local release without `.env`, `vendor`, `node_modules`, `.git`, and `storage`.
7. Upload package to Hostinger.
8. Extract package into `/throughline-athlete-app`.
9. Sync `public/build` to public web root.
10. Run Composer autoload refresh with PHP 8.5.
11. Run migrations:
    - `/opt/alt/php85/usr/bin/php artisan migrate --force`
12. Clear and rebuild Laravel caches:
    - `optimize:clear`
    - `config:cache`
    - `route:cache`
    - `view:cache`
13. Smoke test:
    - public pages
    - protected redirects
    - manifest and JS chunks
    - route cache
    - production migration status
    - key feature routes

Default from this point:

- every completed local product slice should be deployed live so Ahmad can test it.

## Current Verification Baseline

Latest local gate before this report:

- `git diff --check`: passed
- `npx eslint resources/js --max-warnings=0`: passed
- `php artisan test`: 140 tests passed, 1287 assertions
- `npm run build:athlete`: passed

## What Still Needs Work

### Must do before proper launch

1. Enable or solve PHP `ext-sodium` on Hostinger.
2. Configure real production mail provider.
3. Configure final Google OAuth credentials.
4. Configure final Apple OAuth credentials or hide Apple until ready.
5. Configure real WHOOP app credentials and webhook secret.
6. Configure Stripe products, prices, checkout, portal, and webhook secret.
7. Add cron jobs in Hostinger hPanel:
    - Laravel schedule
    - queue work stop-when-empty
8. Run security hardening command after seed/demo data is finalized.
9. Browser-test every live role manually with real accounts.

### Product work still needed

1. Make admin/coach table filters more AJAX-like where they still require explicit submit.
2. Add more export buttons to high-value tables:
    - athlete profile set logs
    - progress check-ins
    - training sessions
    - roster assignments
3. Add richer athlete file previews for images/PDFs.
4. Add coach notes per athlete separate from workout/session notes.
5. Add notification preferences per user.
6. Add admin resend/test email controls beyond invitations.
7. Add billing retry/admin repair workflows.
8. Add wearable reconnect workflow and provider-specific repair instructions.
9. Add better empty-state setup flows for new coach accounts.
10. Add public pricing/subscription page if this is sold self-serve.

### Mobile/native later

The current `/app` is web-first and responsive. Native iOS/Android can come later.

Native app unlocks:

- Apple HealthKit
- Garmin Connect Mobile workflows
- push notifications
- better camera/file upload
- offline workout logging

Do not start native until the web workflow is stable.

### Infrastructure later

Add only when usage justifies it:

- Redis
- queue worker daemon
- Horizon
- WebSockets/Reverb
- analytics warehouse
- object storage/S3-compatible bucket
- CDN

Right now, shared-hosting-safe Laravel is the correct MVP path.

## Strong Next Phase Recommendation

Next phase should be boring and valuable:

1. Deploy this report and profile drill-down slice live.
2. Manually test live owner/admin/coach/athlete workflows.
3. Fix live-only issues immediately.
4. Convert remaining filters to instant AJAX/preserve-state behavior.
5. Add CSV export to athlete profile data tables.
6. Improve file previews and admin email controls.
7. Finalize production provider credentials.
8. Start launch hardening.

No new major feature should be started before live role testing proves the current cycle works end to end.

