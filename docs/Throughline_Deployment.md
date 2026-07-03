# Throughline Deployment Contract

Date: 2026-06-21

## Production target

This app is deployed at the athlete subdomain root:

- public URL: `https://athlete.ahmaddalao.com`
- base path: `/`
- API base URL: `https://athlete.ahmaddalao.com/api/v1`

The old `https://ahmaddalao.com/athlete` path is legacy. Redirect it to the subdomain so the main domain stays clean.

## Required production env values

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://athlete.ahmaddalao.com
ASSET_URL=
VITE_ASSET_BASE=/build/

GOOGLE_REDIRECT_URI=https://athlete.ahmaddalao.com/auth/google/callback
APPLE_REDIRECT_URI=https://athlete.ahmaddalao.com/auth/apple/callback
WHOOP_REDIRECT_URI=https://athlete.ahmaddalao.com/wearables/whoop/callback
WHOOP_WEBHOOK_SECRET=your-whoop-webhook-secret
PHONE_AUTH_ENABLED=false
PHONE_AUTH_DRIVER=log
```

Notes:

- `APP_URL` is the source of truth for generated absolute URLs.
- Leave `ASSET_URL` empty on the subdomain root or you will reintroduce bad asset prefixes.
- `VITE_ASSET_BASE` must include `/build/` so Vite lazy chunks load from `/build/assets/...`.
- Google and WHOOP callbacks should match the deployed subdomain exactly.

## Frontend build

Use this build command for production:

```bash
npm run build:athlete
```

That expands to:

```bash
VITE_ASSET_BASE=/build/ vite build
```

If you use the old `/athlete/build/` base here, you will get broken asset paths. That bug already happened once. No need to repeat it.

## Hostinger layout

Current deployment shape:

- app code root: `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app`
- public web root for `athlete.ahmaddalao.com`: `/home/u867436826/domains/ahmaddalao.com/public_html/athlete`
- database name: `u867436826_athlete`

Frontend deploy rule:

- sync the compiled `public/build` directory to both:
- `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/public/build`
- `/home/u867436826/domains/ahmaddalao.com/public_html/athlete/build`

If those two build directories drift apart, the app serves stale chunks or broken hashes. That is exactly what caused the old dark dashboard to stay live after the local refactor was already done.

Backend deploy rule:

- when a release adds or renames controllers, named routes, middleware wiring, or shared Inertia props, deploy the PHP app code in `/throughline-athlete-app` in the same release window
- after that, clear Laravel's cached bootstrap state with `/opt/alt/php85/usr/bin/php artisan optimize:clear`

If you only ship the Vite bundle and skip the PHP layer, Ziggy will happily crash the frontend with missing-route errors. That already happened with `coaches.index`. Once is enough.

## PHP requirement

- app dependency floor: `PHP 8.2+`
- set the Hostinger site runtime and CLI cron binary to the same PHP family
- as of `2026-06-22`, the live `athlete.ahmaddalao.com` app is serving `PHP 8.5.4`, so artisan and cron commands should use the Hostinger `php85` binary, not `php82`
- as of `2026-06-23`, Hostinger PHP 8.5 CLI does not expose `ext-sodium`; Composer install currently needs `--ignore-platform-req=ext-sodium` because Apple OAuth is staged, not part of the active MVP login flow

Do not point cron at a different PHP binary than the one serving the site. That is how shared-hosting deploys get weird fast.

## 2026-06-23 live deploy note

Dashboard/user-profile deployment shipped these runtime changes:

- full PHP runtime source slice deployed to `/throughline-athlete-app`
- compiled Vite build deployed with `/build/` asset base
- Composer installed production dependencies through `/opt/alt/php85/usr/bin/php`
- trusted database backup created before migrations at `/home/u867436826/db-backups/athlete-20260623-072725.sql.gz`
- four pending migrations applied: Stripe billing fields, device sync review fields, WHOOP webhook events, and phone auth challenges
- Laravel config, route, and view caches rebuilt successfully

Post-deploy smoke passed for public routes, protected dashboard redirect, live asset manifest, authenticated admin dashboard payload, and admin user profile payload.

## 2026-06-23 admin-control/workout-media deploy note

Admin control, notifications, settings, workout video, and responsive shell fixes were deployed to production.

Runtime changes shipped:

- full PHP runtime source slice deployed to `/throughline-athlete-app`
- compiled Vite build deployed with `/build/` asset base
- compiled build synced to both app `public/build` and subdomain `public_html/athlete/build`
- Composer optimized autoload refreshed through `/opt/alt/php85/usr/bin/php`
- trusted database backup created before migrations at `/home/u867436826/db-backups/athlete-20260623112837.sql.gz`
- Laravel config, route, and view caches rebuilt successfully

Migrations applied:

- `2026_06_23_000000_create_platform_settings_table`
- `2026_06_23_010000_create_system_notifications_tables`
- `2026_06_23_020000_add_video_url_to_training_sessions_table`

Post-deploy smoke passed:

- public home, login, register, contact, and live manifest returned `200`
- protected `/dashboard` returned `302` to `/login`
- production migration status shows all three new migrations as `Ran`
- route cache exposes `admin.system-settings.index`, `notifications.index`, and `admin.users.store`
- live public browser smoke passed with no console errors, failed app requests, blank pages, or mobile horizontal overflow

## 2026-06-23 operational-search/logs deploy note

KONA-style operational search, audit logs, and email logs were deployed to production.

Runtime changes shipped:

- full PHP runtime source slice deployed to `/throughline-athlete-app`
- compiled Vite build deployed with `/build/` asset base
- compiled build synced to both app `public/build` and subdomain `public_html/athlete/build`
- Composer autoload refreshed through `/opt/alt/php85/usr/bin/php /usr/local/bin/composer2.phar`
- trusted database backup created before migrations at `/home/u867436826/db-backups/athlete-20260623085447.sql.gz`
- Laravel config, route, and view caches rebuilt successfully

Migrations applied:

- `2026_06_23_030000_create_platform_audit_logs_table`
- `2026_06_23_040000_create_email_delivery_logs_table`

Post-deploy smoke passed:

- public home returned `200`
- protected `/search`, `/admin/audit-log`, and `/admin/email-logs` returned `302` to `/login`
- live `/build/manifest.json` returned `200`
- production migration status shows both new log migrations as `Ran`
- route cache exposes `search.index`, `admin.audit-log.index`, and `admin.email-logs.index`

## 2026-06-28 athlete app/workout execution deploy note

Athlete web app, workout execution, messaging, API access, billing scaffolding, owner/admin permissions, and the latest light-mode dashboard overhaul were deployed to production.

Runtime changes shipped:

- full PHP runtime source slice deployed to `/throughline-athlete-app`
- compiled Vite build deployed with `/build/` asset base
- compiled build synced to both app `public/build` and subdomain `public_html/athlete/build`
- Composer autoload refreshed through `/opt/alt/php85/usr/bin/php /usr/local/bin/composer2.phar`
- trusted database backup created before migrations at `/home/u867436826/db-backups/athlete-20260628064145.sql.gz`
- source snapshot created at `/home/u867436826/db-backups/athlete-source-20260628064049.tar.gz`
- Laravel config, route, and view caches rebuilt successfully

Migrations applied:

- `2026_06_27_000000_add_owner_permissions_to_users`
- `2026_06_27_010000_create_workout_set_logs_table`
- `2026_06_27_020000_add_journal_fields_to_workout_logs_table`
- `2026_06_27_030000_create_coach_athlete_messages_table`

Post-deploy smoke passed:

- public home, login, register, contact, and live manifest returned `200`
- protected `/dashboard`, `/app`, `/messages`, and `/api-access` redirect unauthenticated users to `/login`
- production migration status shows all four new migrations as `Ran`
- route cache exposes athlete workout execution, messages, notifications, admin user profile, and system settings routes
- live manifest checksum matches local and both remote build directories
- authenticated Laravel route smoke returned `200` for admin dashboard/control center/users/settings, coach roster/training/messages, and athlete app/progress/messages/workout execution
- public browser smoke passed on mobile width with no console errors

## 2026-06-28 workout media deploy note

Workout image references and the athlete media slider were deployed to production.

Runtime changes shipped:

- full PHP runtime source slice deployed to `/throughline-athlete-app`
- compiled Vite build deployed with `/build/` asset base
- compiled build synced to both app `public/build` and subdomain `public_html/athlete/build`
- Composer autoload refreshed through `/opt/alt/php85/usr/bin/php /usr/local/bin/composer2.phar`
- trusted database backup created before migrations at `/home/u867436826/db-backups/athlete-media-20260628182842.sql.gz`
- source snapshot created at `/home/u867436826/db-backups/athlete-source-media-20260628182842.tgz`
- Laravel config, route, and view caches rebuilt successfully

Migration applied:

- `2026_06_28_190000_add_media_items_to_training_sessions_table`

Post-deploy smoke passed:

- public home, login, register, contact, and live manifest returned `200`
- protected `/app` and `/training` returned `302` to `/login`
- production migration status shows the new media migration as `Ran`
- route cache exposes training program/session store routes and athlete workout execution routes
- live manifest checksum matches local and both remote build directories: `ed57ce4307e3d39e413bed476e072f61b1f2dfc320cc1f28d001ec2dfa942eaf`

## Next deploy note: coach athlete ownership and file library

This local slice adds database migrations and new route/controller/page assets. Before deploying it to production:

- create a trusted database backup
- deploy PHP source and compiled Vite build together
- run `php artisan migrate --force`
- run `php artisan optimize:clear`
- rebuild config, route, and view caches
- smoke test `/roster/invites`, `/admin/invitations`, `/athletes/{id}`, `/admin/files`, and `/invites/{token}` with real authenticated roles

## 2026-07-03 native mobile API deploy note

This slice adds a real Expo/React Native mobile app scaffold and mobile-focused Laravel API endpoints.

Backend runtime changes:

- new mobile app endpoints under `/api/v1/app/*`
- API messages endpoints under `/api/v1/messages`
- authenticated mobile wearable sync at `/api/v1/wearables/mobile-sync`
- new API abilities: `messages:read`, `messages:write`, `wearable:write`
- new mobile wearable providers: `apple_health`, `health_connect`

Mobile app changes:

- new local Expo project at `mobile/`
- SecureStore token persistence
- athlete and coach mobile home flows
- mobile calendar and assigned program detail screens
- workout execution screen using existing set-log APIs
- progress, wearables, messages, and profile screens

No database migration is required for this slice because device providers are stored as strings and workout/message tables already exist.

Production deploy result:

- source backup: `/home/u867436826/db-backups/athlete-source-native-mobile-20260703114059.tgz`
- migration result: not required for this slice
- production manifest checksum: `d4ff9d7a6abaa8a3e9bfaede5ff72d1c45d95f1d3bb29293fa0cd527187c513a`
- source synced to `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app`
- compiled build synced to both app `public/build` and subdomain `public_html/athlete/build`
- Composer optimized autoload refreshed
- Laravel config, route, and view caches rebuilt successfully

Live smoke passed:

- `https://athlete.ahmaddalao.com` returns HTTP 200
- `https://athlete.ahmaddalao.com/login` returns HTTP 200
- guest `/api/v1/app/home`, `/api/v1/messages`, and `/api/v1/wearables/mobile-sync` return HTTP 401
- production route list includes `/api/v1/app/home`, `/api/v1/app/calendar`, and `/api/v1/app/programs/{trainingProgram}`
- temporary-token athlete smoke passed for `/api/v1/app/home`, `/api/v1/app/calendar`, and `/api/v1/messages`
- temporary-token coach smoke passed for `/api/v1/app/home`, `/api/v1/app/calendar`, and `/api/v1/messages`

## 2026-06-28 backend table-first UI deploy note

This deployment tightened the backend UI toward the KONA table-first style.

- Production target: `https://athlete.ahmaddalao.com`
- Source backup: `/home/u867436826/db-backups/athlete-source-backend-ui-20260628190625.tgz`
- Migration result: `Nothing to migrate`
- Live manifest checksum: `c1aa7eb947cf4580e03651ab57accbe686f7f0b1a137e110971cca3b1bc5c039`
- Build synced to:
    - `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/public/build`
    - `/home/u867436826/domains/ahmaddalao.com/public_html/athlete/build`
- Laravel caches refreshed:
    - autoload
    - config
    - routes
    - views
- Authenticated browser smoke passed for admin/control center, users, roster, training, progress, memberships, wearables, and API access.
- Temporary QA owner used for browser smoke was removed after testing.

## 2026-06-28 admin users simplification deploy note

This deployment simplified `/admin/users` after visual review.

- Production target: `https://athlete.ahmaddalao.com`
- UI change: compact filters, removed extra queue copy, simplified user table rows, and reduced badge clutter.
- Local checks passed:
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `npm run build:athlete`
- Production manifest checksum: `9638127eadbaf044a77a0b56be79072c7cf055c935ac6eb1074068fb68919c47`

## 2026-07-02 athlete mobile UX parity deploy note

This deployment shipped the Phase 5 athlete mobile UX parity slice.

- Production target: `https://athlete.ahmaddalao.com`
- UI changes:
    - athlete app shell now uses a mobile drawer plus fixed bottom navigation with a centered workout action
    - `/app` prioritizes today's workout, readiness, compact schedule, assigned programs, and health trends
    - `/app/workouts/{trainingSession}` now follows a workout-first mobile flow with media hero, exercise rail, set inputs, journal/media actions, rest timer controls, and sticky previous/close/next controls
    - athlete `/wearables` now has Daily and Trends tabs with compact metric cards and device prompts
    - athlete profile settings now include Latest Session, Last 7 Days, Last 30 Days, and PR performance summaries
- Backend changes:
    - profile settings payload now computes workout performance from existing workout logs and set logs
    - exercise presentation now supports optional `section`, `superset_label`, `media_url`, and `movement_type` JSON fields without a migration
- Local checks passed:
    - `npm run build:athlete`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `git diff --check`
- Production deploy result:
    - server source backup: `/home/u867436826/db-backups/athlete-source-phase5-20260702191424.tgz`
    - migration result: `Nothing to migrate`
    - production manifest checksum: `3b8b8b72b8328cbd2a8f5330cd21564f7f06e13031fcddb408bf3a25ba1d1a3b`
    - build synced to both app `public/build` and subdomain `public_html/athlete/build`
    - Laravel config, route, and view caches rebuilt successfully
- Live smoke passed:
    - `https://athlete.ahmaddalao.com` returns HTTP 200
    - `https://athlete.ahmaddalao.com/login` returns HTTP 200
    - guest `/app` redirects to login
    - production app routes list includes `/app`, `/app/programs/{trainingProgram}`, and workout execution routes

## 2026-07-02 athlete calendar and wearable card follow-up

This follow-up kept the Phase 5 app shell but restored the athlete calendar to a full month grid.

- Production target: `https://athlete.ahmaddalao.com`
- UI changes:
    - `/app` calendar is back to a full 7-column month grid with weekday headers, blank leading days, selected-day state, workout indicators, and compact mobile sizing
    - athlete `/wearables` keeps the light page shell but uses darker WHOOP-style signal cards for sleep, recovery, strain, and daily metric rows
    - admin and coach table-first workspaces were not changed in this slice
- Local checks passed:
    - `npm run build:athlete`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `git diff --check`
- Production deploy result:
    - server source backup: `/home/u867436826/db-backups/athlete-source-calendar-card-20260702194950.tgz`
    - migration result: `Nothing to migrate`
    - production manifest checksum: `f1d0e77c85eded9da79a8ed37d7df18ea3ff4f96383b3d9f7454fd6f73411f12`
    - build synced to both app `public/build` and subdomain `public_html/athlete/build`
    - Laravel config, route, and view caches rebuilt successfully
- Live smoke passed:
    - `https://athlete.ahmaddalao.com` returns HTTP 200
    - `https://athlete.ahmaddalao.com/login` returns HTTP 200
    - guest `/app` and `/wearables` redirect to login
    - remote manifest checksum matches the local build checksum

## 2026-07-02 homepage login follow-up

This follow-up made access clearer from the public homepage.

- Production target: `https://athlete.ahmaddalao.com`
- UI changes:
    - homepage hero now includes a compact email/password login panel above the product loop
    - logged-in visitors see a direct `Open app` panel instead of another guest form
    - public header login button is more visible on desktop and now appears in the mobile top link row
    - standalone `/login` page copy and controls were tightened to match the same access flow
- Routing behavior:
    - the homepage form posts to the existing `POST /login` endpoint
    - successful logins still use the existing role-aware landing path for admin, coach, and athlete accounts
- Local checks passed:
    - `npm run build:athlete`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `git diff --check`
- Production deploy result:
    - migration result: `Nothing to migrate`
    - production manifest checksum: `5e7778c3abf6e02768d28d217c21e4eb43a81d0e525ec187d940ecca563789d3`
    - build synced to both app `public/build` and subdomain `public_html/athlete/build`
    - Laravel config, route, and view caches rebuilt successfully
- Live smoke passed:
    - `https://athlete.ahmaddalao.com` returns HTTP 200
    - `https://athlete.ahmaddalao.com/login` returns HTTP 200
    - remote manifest checksum matches the local build checksum

## 2026-07-02 athlete calendar no-reload follow-up

This follow-up stopped the athlete calendar from hitting Laravel every time the user moves between months or selects a day.

- Production target: `https://athlete.ahmaddalao.com`
- Reason for the issue:
    - month arrows and day cells were implemented as Inertia links back to `/app?month=...&date=...`
    - every month/day click therefore triggered a server request and page refresh cycle
- UI/data changes:
    - `/app` now receives the assigned schedule sessions in the initial Inertia payload
    - the calendar keeps `selectedDay` and `calendarMonth` in local React state
    - previous/next month and day selection now update locally without a page reload
    - opening a workout or changing to another real app route still uses normal routing
- Local checks passed:
    - `npm run build:athlete`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `git diff --check`
- Production deploy result:
    - migration result: `Nothing to migrate`
    - production manifest checksum: `edde5266d317c2f1e124d5416fc83ba697128c2e648fd674752990ae8b6df4c7`
    - build synced to both app `public/build` and subdomain `public_html/athlete/build`
    - Laravel config, route, and view caches rebuilt successfully
- Live smoke passed:
    - `https://athlete.ahmaddalao.com` returns HTTP 200
    - `https://athlete.ahmaddalao.com/login` returns HTTP 200
    - guest `/app` redirects to login
    - remote manifest checksum matches the local build checksum

## 2026-07-03 coach and admin table controls follow-up

This follow-up applies the same table-first cleanup to coach and admin workspaces instead of leaving the polish only on the athlete app.

- Production target: `https://athlete.ahmaddalao.com`
- Coach app changes:
    - assigned athletes, schedule, owned programs, and pending workout logs now have direct table search
    - each coach table supports `Show 10`, `25`, `50`, `100`, and `All`
    - mobile coach cards stay lightweight while desktop remains table-first
- Admin control center changes:
    - renewal queue, payment queue, device queue, athlete coverage gaps, and coach load now have direct table search
    - each operations table supports `Show 10`, `25`, `50`, `100`, and `All`
    - queue controls are local and instant, so admins do not need a page reload just to narrow visible rows
- Role-navigation cleanup:
    - shared `Back home` and `Open app` links now use `auth.user.landing_path`
    - normal users are sent back to their app/coach landing page instead of generic `/dashboard`
- Local checks passed:
    - `npm run build:athlete`
    - `npx eslint resources/js --max-warnings=0`
    - `php artisan test`
    - `git diff --check`
- Production deploy result:
    - migration result: `Nothing to migrate`
    - production manifest checksum: `5466fe2275bbba47a90336257d2f3f1df3b8751e701baf1617980fbc3ae64654`
    - build synced to both app `public/build` and subdomain `public_html/athlete/build`
    - Laravel config, route, and view caches rebuilt successfully
- Live smoke passed:
    - `https://athlete.ahmaddalao.com` returns HTTP 200
    - `https://athlete.ahmaddalao.com/login` returns HTTP 200
    - guest `/app`, `/coach`, and `/admin/control-center` redirect to login
    - remote manifest checksum matches the local build checksum

## Cron jobs

Hostinger shared hosting does not expose `crontab` over SSH here, so add these in hPanel:

```cron
* * * * * /opt/alt/php85/usr/bin/php /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/artisan schedule:run >/dev/null 2>&1
* * * * * /opt/alt/php85/usr/bin/php /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/artisan queue:work --stop-when-empty --tries=3 --max-time=50 >/dev/null 2>&1
```

## Immediate post-deploy hardening

Run this once after a fresh seeded deploy so the public demo passwords stop being real:

```bash
/opt/alt/php85/usr/bin/php /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/artisan throughline:security:lock-demo-users --admin-email=admin@athlete.ahmaddalao.com --admin-name="Ahmad Dalao"
```

## Callback URLs

Use these exact production callbacks in provider dashboards:

- Google: `https://athlete.ahmaddalao.com/auth/google/callback`
- Apple: `https://athlete.ahmaddalao.com/auth/apple/callback`
- WHOOP: `https://athlete.ahmaddalao.com/wearables/whoop/callback`
- WHOOP webhook: `https://athlete.ahmaddalao.com/webhooks/whoop`

## API URLs

Absolute production examples:

- token issue: `POST https://athlete.ahmaddalao.com/api/v1/auth/tokens`
- current user: `GET https://athlete.ahmaddalao.com/api/v1/me`
- dashboard: `GET https://athlete.ahmaddalao.com/api/v1/dashboard`
- ingest: `POST https://athlete.ahmaddalao.com/api/device-connections/{public_id}/ingest`

## Why the app code is safe for the subdomain root

These pieces are locked to the subdomain-root deployment model:

- Laravel URL generation is forced from `APP_URL`.
- Vite asset base is configurable and built for `/build/`.
- public `index.php` normalizes shared-hosting subdirectory request paths.
- Google OAuth redirect fallback now resolves from the real callback route.
- WHOOP redirect fallback resolves from `APP_URL` or the callback route.
- public storage URLs are normalized so trailing slashes do not create garbage URLs.
