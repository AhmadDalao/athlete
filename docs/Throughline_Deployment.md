# Throughline Deployment Contract

Date: 2026-06-15

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
WHOOP_REDIRECT_URI=https://athlete.ahmaddalao.com/wearables/whoop/callback
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

## PHP requirement

- app dependency floor: `PHP 8.4.1+`
- deployed Hostinger runtime: `PHP 8.5`

Do not downgrade the site to PHP `8.2` and then act surprised when Composer or Symfony explodes.

## Cron jobs

Hostinger shared hosting does not expose `crontab` over SSH here, so add these in hPanel:

```cron
* * * * * /opt/alt/php85/usr/bin/php /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/artisan schedule:run >/dev/null 2>&1
* * * * * /opt/alt/php85/usr/bin/php /home/u867436826/domains/ahmaddalao.com/throughline-athlete-app/artisan queue:work --stop-when-empty --tries=3 --max-time=50 >/dev/null 2>&1
```

## Callback URLs

Use these exact production callbacks in provider dashboards:

- Google: `https://athlete.ahmaddalao.com/auth/google/callback`
- WHOOP: `https://athlete.ahmaddalao.com/wearables/whoop/callback`

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
