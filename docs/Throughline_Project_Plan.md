# Throughline Project Plan

## Product Thesis

Throughline should not try to be a generic fitness app.

The winning MVP is narrower:

- One product for coaches and serious recreational strength athletes.
- One core loop: wearable recovery data -> coach decision -> updated training plan -> athlete progress.
- One real differentiator: recovery-aware coaching inside the coaching workflow, not recovery charts floating in space.

That is the product. Everything else is support.

## Delivery Status (2026-06-27)

Current state, no bullshit:

- Phase 1 MVP foundations are in place:
    - role-aware auth and registration
    - coach, athlete, and admin dashboards
    - coach/admin roster workspace and assignment control
    - training assignment and workout logging
    - memberships with days-remaining tracking
    - manual payment-event control
    - wearable ingest and normalized metric storage
    - admin user management
    - admin control center
    - owner/admin account model with explicit permission groups
- Phase 2 recovery intelligence and auth/integration hardening are functionally complete for MVP scope:
    - WHOOP OAuth scaffolding and sync flow
    - 7-day analytics and recovery alerts
    - athlete progress workspace with manual check-ins for food, body, hydration, soreness, stress, sleep quality, and energy
    - athlete-first premium dashboard, training, and recovery surfaces
    - coach weekly briefs driven by recovery, compliance, and manual check-in signal
    - wearable sync review queue with stored failure state and repair guidance
    - signed WHOOP webhooks with replay protection and cron-safe processing
    - live Apple OAuth
    - live phone OTP auth
- Phase 3 has started:
    - public coach discovery page
    - cleaner shell, auth, and guest-entry UX
    - consistent light-only design system and simplified navigation
    - roster and training workspace simplification so high-volume coach tasks read faster
    - KONA-style operational admin patterns:
        - global workspace search
        - audit log
        - email delivery log
        - Website control naming
        - table-first admin pages with filters and CSV export
    - phone-first athlete app based on the reference workout app video:
        - `/app` athlete home
        - `/app/workouts/{trainingSession}` execution screen
        - sticky mobile bottom navigation
        - set-by-set workout tracking
        - journal and media tabs
        - simple coach-athlete messaging
- Still not live-launch ready:
    - richer page-content / CMS-style admin controls beyond current Website control fields
    - deeper operator tooling for retries, reconnects, and billing/provider edge cases

## Strong Stack Recommendation

### Backend

- Laravel 12
- PHP 8.2+
- MySQL 8.4 LTS
- database queue and database cache for the MVP
- Redis later when the hosting setup actually supports it cleanly

### Frontend

- React 19
- TypeScript
- Inertia.js with Laravel starter kit
- Tailwind CSS

### Why this stack

Do not split this into a separate React frontend and Laravel API on day one.

That sounds modern right up until you maintain duplicate auth, duplicate validation, duplicate route logic, duplicate deployment concerns, and a pile of avoidable glue code.

The clean stack here is:

- Laravel as the core application
- React as the UI layer
- Inertia to connect them cleanly

That gives you a real React frontend without turning the first six months into API plumbing.

## MVP Hosting Profile

If the first launch is on a cheap Hostinger shared plan, the stack should be trimmed to fit reality instead of pretending we have a proper app server.

Shared-hosting-safe MVP profile:

- Laravel 12
- React + Inertia + TypeScript
- MySQL
- cron-driven scheduling
- database queue driver
- database cache driver
- AJAX / polling for near-real-time refresh where needed

What to avoid on cheap hosting:

- Redis dependency
- long-running queue workers
- WebSockets as a core feature
- Reverb, Horizon, Octane
- heavy real-time features
- mixing Livewire into a React/Inertia app

This is acceptable for the MVP as long as we design around it on purpose.

## What I Recommend Against

### phpMyAdmin as a core decision

phpMyAdmin is not a platform choice. It is a database admin tool.

For a demanding product, the real database decisions are:

- schema design
- indexing
- background jobs
- data aggregation
- retention rules
- monitoring

If you like phpMyAdmin for local inspection, fine. Use it locally in Docker if you want. Do not treat it as the backbone of the system, and do not expose it in production.

### Starting with a pure API-first microservice setup

Bad move for this phase.

You do not have the scale or team size that justifies the complexity. A modular Laravel monolith is the right choice until traffic, org structure, or mobile-native requirements force a split.

### Starting with big-data infrastructure

Also a bad move.

You mentioned large datasets. That does not mean we should start with Kafka, ClickHouse, or five databases because it feels enterprise.

Start with:

- MySQL for transactional data
- database-backed queue and cache for the MVP
- precomputed aggregates for dashboards
- object storage for files and raw payload archives

If analytics volume later gets ugly, then we add Redis, a warehouse, or a columnar store. Not before.

## Product Modules

### 1. Authentication and roles

Roles for v1:

- Owner
- Admin
- Coach
- Athlete

Rules:

- owners can create owner/admin accounts and always have every permission
- the app must keep at least one owner account active
- admins can manage normal users and assigned operational permissions, but cannot create or edit owners
- one user account can own multiple roles later
- coaches only see assigned athletes
- athletes only see their own data
- admins see platform-wide business and ops data

### 2. Coach workspace

Core features:

- athlete roster
- athlete profile
- program builder
- workout assignment
- training log review
- wearable recovery summary
- AI weekly summary
- alerts for missed sessions, low recovery, or declining performance

### 3. Athlete workspace

Core features:

- phone-first today's session
- session execution with set-by-set actual reps/load/RPE, completion checks, rest timer, media, and journal notes
- goal tracking
- membership status
- progress view with weight, nutrition, body, and recovery trends
- advanced metrics view
- device connection status
- coach-assigned program detail with sets, reps, load, rest, target, and notes
- bottom-nav web app model for Feed, Board, Workout, Messaging, and Profile

Important rule:

Wearables do not cover the whole product.

WHOOP, Garmin, and similar APIs can give us sleep, strain, HRV, resting heart rate, and activity data. They do not reliably give us food intake, deliberate weight tracking, soreness context, or the athlete's own compliance notes. That is why the MVP needs both wearable sync and manual check-ins.

### 4. Device integrations

MVP priority:

- Garmin
- Strava
- Whoop if approved in time
- Oura if approved in time

Apple Health is not an MVP web feature. It needs a mobile app path.

### 5. Membership and billing

You explicitly need this, so we design it properly from the start.

Membership objects must track:

- plan name
- billing cycle
- status
- start date
- end date
- renewal date
- grace period end
- cancellation date
- days remaining

Both coach and athlete dashboards should show plan status clearly. Nobody should need support to answer "how many days are left?"

### 6. Admin control center

The admin dashboard should focus on decisions, not vanity numbers.

Admin metrics for v1:

- total users
- total coaches
- total athletes
- active subscriptions
- expiring subscriptions
- failed payments
- monthly revenue
- payouts due
- device connection counts
- active vs inactive users

Admin controls for v1:

- manage users
- manage roles
- suspend/reactivate accounts
- manage plans
- review payments
- review subscription states
- view device sync health
- manage page content and platform settings
- search across users, athletes, plans, programs, devices, and notifications
- audit important admin, billing, roster, training, API-token, and check-in changes
- review password reset and workflow email delivery logs

## Architecture Direction

### Application shape

- one Laravel app
- React/Inertia pages for web UI
- versioned API routes for device ingestion, webhooks, and future mobile use
- background jobs for all sync-heavy work

### Data shape

Use separate domains instead of one giant mess of tables:

- identity: users, profiles, roles
- coaching: coaches, athletes, coach_athlete_links
- programming: programs, blocks, workouts, exercises, workout_logs
- wearable: device_connections, sync_jobs, metric_snapshots, recovery_scores
- billing: plans, subscriptions, invoices, payments, payouts
- analytics: daily_aggregates, monthly_aggregates, dashboard_counters
- admin: audits, settings, announcements

### Important data rule

Do not dump every raw wearable event directly into the tables the dashboard reads from.

Keep two layers:

- raw ingest / sync tables
- normalized and aggregated reporting tables

That is how we stay fast.

## Large Dataset Strategy

Your dataset can get big fast because time-series health and training data is noisy and frequent.

So the plan is:

- store transactional records in MySQL
- archive raw provider payloads in object storage if needed
- normalize only fields we actually use
- compute daily summaries per athlete
- compute weekly summaries per coach roster
- cache hot dashboard queries in the database cache for MVP
- move hot-cache paths to Redis later if the hosting stack changes

Examples of aggregates we should store instead of recalculating every request:

- athlete daily readiness
- weekly compliance percentage
- 7-day sleep average
- acute vs chronic load summary
- coach roster risk counts
- subscription status counts

## Billing Recommendation

Do not let billing logic live only inside a payment provider dashboard.

We need first-class billing tables in our database because the product needs:

- subscriptions
- renewals
- expiry tracking
- failed payment handling
- grace periods
- coach-facing visibility
- athlete-facing visibility
- admin reporting

If the final PSP supports the right subscription model cleanly, we can use Laravel Cashier for the SaaS subscription side. But marketplace-style coach-athlete payment flows and payout rules will still need our own billing domain model.

## AI Scope

Keep AI useful and controlled.

MVP AI jobs:

- summarize the athlete week for the coach
- rank athletes who need attention
- explain progress to the athlete in plain language

MVP AI should not:

- diagnose injury
- prescribe medical action
- auto-modify a training plan without coach approval

## Operations and quality

We should not build blind.

From the start, include:

- queued jobs for sync, imports, notifications, and AI work
- audit logs for admin and billing actions
- error tracking
- job monitoring
- application metrics
- automated tests for billing, permissions, and sync flows

The bugs that kill trust in this product will not be cosmetic. They will be permission leaks, bad subscription states, duplicate charges, and broken sync logic.

## Dashboard Design Rules

### Coach dashboard

Dense, fast, action-first.

Show:

- who needs attention now
- why they need attention
- what changed this week
- what action is suggested

### Athlete dashboard

Calm by default.

Show:

- today's session
- progress toward goal
- one useful insight
- membership status

Advanced charts should be opt-in, not the default screen.

### Admin dashboard

Business-first and operationally clear.

Show:

- user growth
- subscription health
- revenue and payment failures
- sync failures
- high-level usage trends

## Development Phases

### Phase 0 - foundation

- lock stack
- define database domains
- scaffold Laravel + React/Inertia app
- set up auth and roles
- set up environments, CI, queues, cache, storage
- write the living project documentation

### Phase 1 - core MVP

- coach auth and onboarding
- athlete auth and onboarding
- coach-athlete assignment
- program builder
- workout logging
- membership system
- admin dashboard v1
- Garmin and Strava integration first
- internal analytics and audit logs

### Phase 2 - recovery intelligence

- Whoop/Oura if approved
- recovery trends
- readiness flags
- AI weekly summaries
- better roster triage

### Phase 3 - commercial expansion

- marketplace
- payouts
- coach discovery
- reviews
- Arabic/RTL
- native mobile plan

### Current phase status

As of 2026-06-15, core Phase 1 is functionally complete and Phase 2 has started.

Completed inside Phase 1:

- auth, roles, and role-aware dashboards
- richer signup and profile onboarding with phone, goal, contact preference, and signup-channel tracking
- coach-athlete assignments
- coach/admin roster management UI
- membership system and cron-safe audit
- training program builder and athlete workout logging
- structured workout prescriptions with sets, reps, load, rest, target, and note support
- wearable ingest framework and first operator views
- admin user management from the UI
- payment event history and manual membership operations
- deeper admin analytics for payments, new users, and operational attention queues

Completed inside early Phase 2:

- WHOOP OAuth scaffolding
- WHOOP sync command and token storage
- deeper recovery metrics including sleep need, sleep debt, respiratory rate, and blood oxygen
- 7-day wearable trend analytics for dashboard and wearable views
- athlete progress workspace with manual daily check-ins and coach/admin progress visibility
- live Google auth
- coach weekly briefs for roster triage
- stored sync-failure review for device connections

Still missing before a cleaner launch:

- live Apple auth
- live phone auth
- WHOOP webhook hardening
- richer page-content / CMS-style admin controls

## First Build Order

This is the order I would build in:

1. Laravel app with React/Inertia starter stack
2. Auth, roles, and account structure
3. Core database schema
4. Coach dashboard shell
5. Athlete dashboard shell
6. Membership system
7. Program builder and workout logging
8. Admin dashboard
9. Device integration framework
10. First provider integration
11. AI summaries

That order is not random. It gets the product skeleton standing before we plug in the expensive moving parts.

## Shared Hosting Adjustments

If we ship the first MVP on shared hosting, use these rules:

- run `php artisan schedule:run` via cron every minute
- process queued jobs through cron on an interval instead of a permanent worker
- use polling for dashboard refreshes instead of real-time sockets
- keep sync and AI jobs short, idempotent, and retry-safe
- move wearable raw payload normalization into batched jobs
- precompute dashboard metrics so admin pages stay fast

This keeps the MVP clean without baking in fake infrastructure.

### UI refresh rule

If we are committing to React on the frontend, stay committed.

Use:

- Inertia reloads for page-level updates
- AJAX / fetch polling for dashboard widgets, sync status, and notifications

Do not add Livewire on top. That gives us two frontend interaction models in one app, which is sloppy and harder to maintain for no real gain.

## Current Foundation

The repo is now scaffolded on:

- Laravel 12
- the official Laravel React starter kit
- Inertia 2
- React 19
- TypeScript
- Tailwind CSS
- database sessions, cache, and queue support

Local development can use SQLite for convenience. Deployment should use MySQL.

Current implemented MVP slices:

- role-backed registration and shared auth state
- coach-athlete assignment domain
- roster workspace for coach/admin assignment control
- subscription plans and memberships
- device connection domain scaffolding
- device ingest keys and public connection ids
- raw device ingest archival and normalized daily metric snapshots
- athlete progress workspace with manual check-ins, food/body tracking, and alerting
- token-authenticated API v1 surface for external apps and partner services
- admin / coach / athlete dashboard views
- membership control page with status filtering and pagination
- wearable control page with recovery snapshots and ingest credentials
- scheduled membership lifecycle audit command for cron-based hosting
- seeded local demo data with 6 users, 3 coach-athlete assignments, and 21 athlete check-ins

Current regression status:

- migrations and seeders pass cleanly
- full feature test suite passes: 107 tests, 922 assertions
- production Vite build passes
- live browser validation for this latest Phase 3 UX slice is still pending

## Non-Negotiables

- TypeScript on the frontend
- role-based authorization from day one
- queues for sync and AI work from day one
- audit logs for admin-sensitive actions
- documented API contracts for provider integrations
- environment-based secrets management
- no production phpMyAdmin exposure
- no fake analytics calculated live from giant raw tables

## My Recommendation in One Line

Build Throughline as a modular Laravel monolith with React/Inertia, MySQL 8.4 LTS, database-backed queue/cache for the MVP, and a strict first release centered on coach dashboards, memberships, and recovery-aware coaching. Keep phpMyAdmin in the toolbox if you want, but nowhere near the architecture diagram.
