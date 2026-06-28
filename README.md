# Throughline

Recovery-aware coaching platform for coaches and serious recreational athletes.

## Stack

- Laravel 12
- React 19
- Inertia 2
- TypeScript
- Tailwind CSS
- MySQL in production
- database queue and database cache for the MVP
- PHP 8.2+ in production

## Current MVP surface

- role-based auth for owners, admins, coaches, and athletes
- owner/admin user control with position labels and explicit permission groups
- richer signup and profile onboarding with phone, goal, contact preference, and live auth-method rollout
- live Google and Apple OAuth with role-aware account creation and account linking
- live phone OTP sign-up and login with session-safe verification
- coach and athlete dashboards backed by real membership, roster, training, and device data
- coach athlete invitations with secure accept links and automatic roster assignment
- coach-facing athlete profiles for schedules, completed sessions, progress, devices, membership, messages, and files
- athlete file library with private downloads, admin move/archive controls, and audit logging
- admin user control for roles, permissions, onboarding metadata, and account contact settings
- phone-first athlete dashboard slice for today workout, wearable prompt, exercise rows, journal/media actions, and workout logging handoff
- training workspace for coach program assignment, session planning, and athlete workout logs
- membership control page with status filters, payment-event history, and manual operations
- API access page with personal token creation, ingest endpoint visibility, and copy-ready curl examples
- coach weekly briefs that summarize recovery, compliance, and check-in risk in plain language
- cron-safe membership audit command to keep lifecycle states clean
- wearable control page with normalized recovery snapshots
- wearable sync review queue with stored failure state and repair guidance
- signed WHOOP webhook intake with replay protection and cron-safe processing
- ingest endpoint for device data with per-connection keys and raw payload archival
- public coach discovery page as the first Phase 3 marketplace slice

## Local setup

This repo ships with a local `.env` configured for SQLite so you can boot it without a local MySQL server.

```bash
composer install
npm install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
composer run dev
```

If you want to match production more closely, switch `.env` to MySQL and fill in your local database credentials.

## Shared hosting deployment notes

The target deployment profile is cheap shared hosting with cron support, not Redis and not daemon workers.

Production URL target for this repo:

- app URL: `https://athlete.ahmaddalao.com`
- app-relative base path: `/`
- API base URL: `https://athlete.ahmaddalao.com/api/v1`

Important:

- The current app floor is PHP `8.2+`.
- Keep the site runtime and the CLI binary on the same PHP family when you add cron jobs.
- This repo is designed around shared-hosting-safe scheduling, not Redis or daemon workers.

Recommended cron jobs:

```cron
* * * * * /opt/alt/php82/usr/bin/php /home/USER/domains/DOMAIN/throughline-athlete-app/artisan schedule:run >> /dev/null 2>&1
* * * * * /opt/alt/php82/usr/bin/php /home/USER/domains/DOMAIN/throughline-athlete-app/artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
```

The canonical production host is the athlete subdomain, not the main domain subfolder.

Use these production env values:

```dotenv
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

Use this production frontend build:

```bash
npm run build:athlete
```

If you want a direct belt-and-suspenders billing audit outside the scheduler, run:

```bash
php artisan throughline:memberships:audit
```

After first production deploy, harden access immediately:

```bash
php artisan throughline:security:lock-demo-users --admin-email=admin@athlete.ahmaddalao.com --admin-name="Ahmad Dalao"
```

That command creates or updates the real owner/admin account, assigns the owner and admin roles, and rotates seeded demo credentials.

## Useful commands

```bash
composer run dev
composer run setup
composer run deploy:optimize
composer run queue:drain
npm run build
npm run build:athlete
php artisan test
php artisan throughline:memberships:audit
php artisan throughline:whoop:sync
php artisan throughline:whoop:webhooks:process
php artisan throughline:security:lock-demo-users --admin-email=admin@athlete.ahmaddalao.com --admin-name="Ahmad Dalao"
```

## API ingest shape

Normalized device snapshots are accepted at:

```text
POST /api/device-connections/{public_id}/ingest
Header: X-Throughline-Key: {ingest_key}
```

Production absolute URL:

```text
https://athlete.ahmaddalao.com/api/device-connections/{public_id}/ingest
```

Example JSON body:

```json
{
    "metric_date": "2026-06-10",
    "external_event_id": "provider-event-123",
    "metrics": {
        "readiness_score": 82,
        "sleep_minutes": 445,
        "strain_score": 13.4,
        "steps": 11240,
        "resting_heart_rate": 47,
        "heart_rate_variability": 72.1,
        "training_load": 418.6
    }
}
```

## Planning docs

- [Project plan](docs/Throughline_Project_Plan.md)
- [Deployment contract](docs/Throughline_Deployment.md)
- [Shareable DOCX plan](output/doc/Throughline_Project_Plan.docx)
- [Progress report](docs/Throughline_Progress_Report.md)
- [API reference](docs/Throughline_API.md)
- [Billing reference](docs/Throughline_Billing.md)
