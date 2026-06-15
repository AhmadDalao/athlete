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
- PHP 8.4.1+ in production

## Current MVP surface

- role-based auth for admins, coaches, and athletes
- richer signup and profile onboarding with phone, goal, contact preference, and staged auth-method rollout
- coach and athlete dashboards backed by real membership, roster, training, and device data
- admin user control for roles, onboarding metadata, and account contact settings
- training workspace for coach program assignment, session planning, and athlete workout logs
- membership control page with status filters, payment-event history, and manual operations
- cron-safe membership audit command to keep lifecycle states clean
- wearable control page with normalized recovery snapshots
- ingest endpoint for device data with per-connection keys and raw payload archival

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

- app URL: `https://ahmaddalao.com/athlete`
- app-relative base path: `/athlete`
- API base URL: `https://ahmaddalao.com/athlete/api/v1`

Important:

- The current lock file requires PHP `8.4.1+`.
- On Hostinger, this deployment is running under PHP `8.5`.
- If you force the site back to PHP `8.2`, this app will break. That is not a theory; it already failed on install.
- If you insist on PHP `8.2`, do a dependency downgrade pass first instead of pretending the lock file says something it does not.

Recommended cron jobs:

```cron
* * * * * /opt/alt/php85/usr/bin/php /home/USER/domains/DOMAIN/throughline-athlete-app/artisan schedule:run >> /dev/null 2>&1
* * * * * /opt/alt/php85/usr/bin/php /home/USER/domains/DOMAIN/throughline-athlete-app/artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
```

The production subdirectory is not optional here. Build and deploy for `/athlete`, or your assets and callback URLs will be wrong.

Use these production env values:

```dotenv
APP_URL=https://ahmaddalao.com/athlete
ASSET_URL=/athlete
VITE_ASSET_BASE=/athlete/build/
GOOGLE_REDIRECT_URI=https://ahmaddalao.com/athlete/auth/google/callback
WHOOP_REDIRECT_URI=https://ahmaddalao.com/athlete/wearables/whoop/callback
```

Use this production frontend build:

```bash
npm run build:athlete
```

If you want a direct belt-and-suspenders billing audit outside the scheduler, run:

```bash
php artisan throughline:memberships:audit
```

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
```

## API ingest shape

Normalized device snapshots are accepted at:

```text
POST /api/device-connections/{public_id}/ingest
Header: X-Throughline-Key: {ingest_key}
```

Production absolute URL:

```text
https://ahmaddalao.com/athlete/api/device-connections/{public_id}/ingest
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
