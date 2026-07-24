# Hostinger Deployment Checklist

Last updated: 2026-07-24

## Scope

This checklist is for deploying the clean website-first Throughline rebuild to Hostinger. It does not include native mobile, watch sync, Stripe, WHOOP, Redis, or queues.

## Pre-Deploy Rules

- Deploy only after local tests pass.
- Back up the production database before running migrations.
- Do not overwrite production `.env`.
- Do not upload local SQLite backups.
- Do not run destructive reset commands on production.
- Keep the old production files until the rebuild is verified.

## Required Server Values

Confirm these before upload:

- PHP: 8.2 or newer.
- Hostinger CLI: use `/opt/alt/php82/usr/bin/php`; the account default may still point to PHP 7.4.
- Database: production MySQL database name, username, password, host, and port.
- App URL: `https://athlete.ahmaddalao.com`.
- Public document root points to Laravel `public/`.

## Local Build Checks

Run locally before pushing/deploying:

```bash
composer validate --strict
./vendor/bin/pint --test
php artisan test
php artisan optimize:clear
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

## Production Backup

Before touching the live database:

1. Open Hostinger database tools.
2. Export the current production database as SQL.
3. Save the export with a timestamp.
4. Confirm the file is downloadable and not empty.

## Upload Or Pull Code

Preferred flow if SSH/git is available:

```bash
cd /path/to/athlete
git fetch origin
git checkout codex/throughline-clean-rebuild
git pull origin codex/throughline-clean-rebuild
```

If using FTP:

- Upload app files except `.env`, `storage/backups`, `.git`, `node_modules`, and local database files.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Ensure the domain points to the `public/` folder.

## Production `.env`

Minimum required values:

```bash
APP_NAME=Throughline
APP_ENV=production
APP_DEBUG=false
APP_URL=https://athlete.ahmaddalao.com

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp
```

Generate an app key only if production does not already have one:

```bash
PHP_BIN=/opt/alt/php82/usr/bin/php
$PHP_BIN artisan key:generate --force
```

## Install Dependencies

If Composer is available on Hostinger:

```bash
composer install --no-dev --optimize-autoloader
```

If Composer is not available:

- Build vendor locally using the same PHP major version.
- Upload `vendor/` with the app.

## Database Migration

After backup and `.env` verification:

```bash
PHP_BIN=/opt/alt/php82/usr/bin/php
$PHP_BIN artisan migrate --force
```

For first-time clean MVP setup only:

```bash
$PHP_BIN artisan db:seed --force
```

Do not seed over real production users after launch. Seeded credentials are local QA credentials and must never remain unchanged on the public server.

## Cache Production

```bash
PHP_BIN=/opt/alt/php82/usr/bin/php
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
```

## Smoke Test

Verify these pages live:

- `/`
- `/login`
- `/contact`
- `/dashboard`
- `/admin/dashboard`
- `/admin/users`
- `/admin/settings`
- `/admin/audit-log`
- `/coach`
- `/coach/programs`
- `/app`
- `/app/progress`

Verify these workflows:

- Owner/admin login redirects to `/admin/dashboard`.
- Coach login redirects to `/coach`.
- Athlete login redirects to `/app`.
- Normal users cannot open `/admin/dashboard`.
- Admin can edit settings.
- Admin can export users, invitations, contact submissions, and logs.
- Coach can invite an athlete.
- Coach can create/edit a program and session.
- Athlete can complete a workout.
- Athlete can save a progress entry.
- Contact form saves into admin contact inbox.

## Rollback

If production breaks:

1. Put the app in maintenance mode if possible:

```bash
PHP_BIN=/opt/alt/php82/usr/bin/php
$PHP_BIN artisan down
```

2. Restore the previous production files or previous git commit.
3. Restore the SQL backup if migrations damaged data.
4. Clear caches:

```bash
$PHP_BIN artisan optimize:clear
```

5. Bring app back:

```bash
$PHP_BIN artisan up
```

## Post-Deploy Notes

After deployment is accepted, update:

- `docs/Throughline_Rebuild_MVP.md`
- Any production credentials checklist kept outside git
- The current GitHub branch/commit deployed

## Verified Release

- Date: 2026-07-24
- Branch: `codex/throughline-clean-rebuild`
- Commit: `aa923a67f5005ab285745b90464d2c442e871e1c`
- Runtime: PHP 8.2.30, Laravel 12.62.0, MySQL
- Database backup: `/home/u867436826/backups/throughline/database-20260724-211901.sql.gz`
- Code backup: `/home/u867436826/backups/throughline/code-20260724-211901.tar.gz`
- Previous release: `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app.pre-aa923a6.20260724-212557`
- Live smoke: public, owner, coach, athlete, permissions, settings, programs, progress, workout media, Livewire calendar, and role denials passed.
