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
