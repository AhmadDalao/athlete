# Throughline Deployment Contract

Date: 2026-06-15

## Production target

This app is deployed under a subdirectory:

- public URL: `https://ahmaddalao.com/athlete`
- base path: `/athlete`
- API base URL: `https://ahmaddalao.com/athlete/api/v1`

Do not treat the domain root `/` as the app root. If you do, assets, OAuth callbacks, and generated links will drift.

## Required production env values

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ahmaddalao.com/athlete
ASSET_URL=/athlete
VITE_ASSET_BASE=/athlete/

GOOGLE_REDIRECT_URI=https://ahmaddalao.com/athlete/auth/google/callback
WHOOP_REDIRECT_URI=https://ahmaddalao.com/athlete/wearables/whoop/callback
```

Notes:

- `APP_URL` is the source of truth for generated absolute URLs.
- `ASSET_URL` and `VITE_ASSET_BASE` keep built assets pointed at `/athlete/build/...`.
- Google and WHOOP callbacks should match the deployed subdirectory exactly.

## Frontend build

Use this build command for production:

```bash
npm run build:athlete
```

That expands to:

```bash
VITE_ASSET_BASE=/athlete/ vite build
```

If you use plain `npm run build` for this deployment target, expect broken asset paths sooner or later.

## Hostinger layout

Current deployment shape:

- app code root: `/home/u867436826/domains/ahmaddalao.com/throughline-athlete-app`
- public web root: `/home/u867436826/domains/ahmaddalao.com/public_html/athlete`
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

- Google: `https://ahmaddalao.com/athlete/auth/google/callback`
- WHOOP: `https://ahmaddalao.com/athlete/wearables/whoop/callback`

## API URLs

Absolute production examples:

- token issue: `POST https://ahmaddalao.com/athlete/api/v1/auth/tokens`
- current user: `GET https://ahmaddalao.com/athlete/api/v1/me`
- dashboard: `GET https://ahmaddalao.com/athlete/api/v1/dashboard`
- ingest: `POST https://ahmaddalao.com/athlete/api/device-connections/{public_id}/ingest`

## Why the app code is safe for `/athlete`

These pieces were locked to the subdirectory deployment model:

- Laravel URL generation is forced from `APP_URL`.
- Vite asset base is configurable and can be built for `/athlete/`.
- public `index.php` normalizes shared-hosting subdirectory request paths.
- Google OAuth redirect fallback now resolves from the real callback route.
- WHOOP redirect fallback resolves from `APP_URL` or the callback route.
- public storage URLs are normalized so trailing slashes do not create garbage URLs.
