# Hostinger Deployment Checklist

Last updated: 2026-07-25

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
composer install
npm ci
npm audit
npm run build
composer validate --strict
./vendor/bin/pint --test
php artisan test
php artisan optimize:clear
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

Confirm `public/build/manifest.json` exists after the build. Production does not run Vite and does not load Bootstrap or Font Awesome from a CDN.

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
git checkout main
git pull origin main
```

If using FTP:

- Upload app files except `.env`, `storage/backups`, `.git`, `node_modules`, and local database files.
- Upload the locally generated `public/build/` directory.
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

Node is not required on Hostinger. Frontend dependencies are compiled locally; upload `public/build` from the verified local release.

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

## Atomic Release Swap

If the upload is prepared in a temporary directory such as `throughline-athlete-app.release-<commit>`, do not run `config:cache` or `view:cache` there. Laravel stores absolute filesystem paths in its cache files. Caching before the directory swap leaves production pointing at the old temporary path and causes `View [...] not found` errors even when the Blade files exist.

The safe order is:

1. Prepare dependencies and run migrations from the temporary release.
2. Swap the temporary directory into the final production path.
3. Reconnect the production `.env`, storage symlink, and public build.
4. Run the finalization script from the final production path.

## Cache Production

```bash
cd /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app
PHP_BIN=/opt/alt/php82/usr/bin/php ./scripts/hostinger-finalize-release.sh
```

The script refuses temporary release paths and verifies that every cached Blade view path exists after caching.

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
- Owner can upload/remove the public logo, edit public content, switch the guest theme, and control public feature visibility.
- An admin without `admin.settings` or `admin.permissions` receives `403` from those control surfaces.
- Hidden features, pricing, and contact routes return `404` and leave no dead public CTA links.
- Pausing invitations blocks coach web and mobile API invitation creation/resend without deleting existing invitations.
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

- Date: 2026-08-12
- Branch: `codex/throughline-flutter-refactor`
- Commit: `ec66d77`
- Runtime: PHP 8.2.30, Laravel 12.62.0, MySQL
- Database backup: `/home/u867436826/backups/throughline/database-pre-1471aca-20260812-075634.sql.gz`
- Code backup: `/home/u867436826/backups/throughline/code-pre-1471aca-20260812-075634.tar.gz`
- Previous release: `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app.previous-ec66d77`
- Live smoke: public pages, API status, owner/admin controls, coach roster/program/schedule/invitation/message pages, athlete home/progress/message/profile pages, and cross-role `403` denials passed.
