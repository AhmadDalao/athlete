# Throughline WHOOP Integration

Date: 2026-06-14

## Why WHOOP matters here

WHOOP is not just another badge on the integrations page.

It gives us data that directly improves coaching decisions:

- recovery score
- resting heart rate
- HRV
- sleep duration
- sleep stages
- sleep need
- respiratory rate
- blood oxygen
- skin temperature
- daily strain
- workout duration and distance

That is enough to make roster triage, session adjustments, and recovery-aware programming materially better.

## Current implementation status

Implemented now:

- WHOOP OAuth connect route
- WHOOP OAuth callback handler
- encrypted token storage on `device_connections`
- WHOOP API client wrapper
- WHOOP sync service
- cron command for sync
- normalized snapshot mapping into `metric_snapshots`
- wearable UI visibility for WHOOP integration state

Not implemented yet:

- webhook receiver and signature validation
- sync failure inbox for operators
- background queue fan-out for large connection counts
- account linking rules across duplicate emails or mixed provider signups

## Environment variables

Add these to the deployment environment before enabling WHOOP in production:

- `WHOOP_CLIENT_ID`
- `WHOOP_CLIENT_SECRET`
- `WHOOP_REDIRECT_URI`
- `WHOOP_SCOPES`
- `WHOOP_SYNC_LOOKBACK_DAYS`

Optional defaults already exist in config for:

- base API URL
- auth URL
- token URL
- callback fallback from `APP_URL`

Production callback URL for the current deployment target:

- `https://ahmaddalao.com/athlete/wearables/whoop/callback`

## Main files

| File                                               | Purpose                                                                                     |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/WhoopConnectController.php`  | Starts the OAuth redirect flow.                                                             |
| `app/Http/Controllers/WhoopCallbackController.php` | Exchanges the auth code, stores tokens, saves profile payload, and triggers the first sync. |
| `app/Services/Whoop/WhoopApiClient.php`            | Thin WHOOP API wrapper for token exchange and collection fetches.                           |
| `app/Services/Whoop/WhoopSyncService.php`          | Pulls WHOOP collections, normalizes them by date, and persists snapshots.                   |
| `app/Services/MetricAnalyticsService.php`          | Builds dashboard and wearable trend summaries from stored snapshots.                        |
| `app/Models/DeviceConnection.php`                  | Stores auth mode, encrypted OAuth tokens, scopes, and provider payloads.                    |
| `app/Models/MetricSnapshot.php`                    | Stores normalized daily recovery and activity metrics.                                      |
| `routes/console.php`                               | Registers the cron-safe WHOOP sync command.                                                 |

## OAuth flow

1. Authenticated user opens `/wearables/whoop/connect`
2. App creates an OAuth `state` token and stores it in session
3. User is redirected to WHOOP
4. WHOOP redirects back to `/wearables/whoop/callback`
   Production absolute URL: `https://ahmaddalao.com/athlete/wearables/whoop/callback`
5. App validates `state`
6. App exchanges `code` for access and refresh tokens
7. App fetches basic profile data
8. App creates or updates the user’s `device_connections` record
9. App runs the first WHOOP sync immediately

This gives us a usable connection without manual database work.

## Sync flow

The sync command is:

`php artisan throughline:whoop:sync`

Flow:

1. Query WHOOP OAuth connections
2. Refresh access tokens when they are near expiry
3. Pull these WHOOP collections:
    - cycles
    - recovery
    - sleep activity
    - workout activity
4. Normalize all collection data into daily buckets
5. Upsert a `metric_snapshots` row per day
6. Store raw source payloads inside `device_metric_ingests`
7. Mark the connection synced or flag it for attention on failure

This is cron-safe and shared-hosting friendly.

## Normalized mapping

| WHOOP source                   | Throughline field                                               |
| ------------------------------ | --------------------------------------------------------------- |
| `recovery_score`               | `readiness_score`                                               |
| `resting_heart_rate`           | `resting_heart_rate`                                            |
| `hrv_rmssd_milli`              | `heart_rate_variability`                                        |
| `spo2_percentage`              | `blood_oxygen_percent`                                          |
| `skin_temp_celsius`            | `skin_temperature_celsius`                                      |
| sleep stage totals             | `sleep_minutes`, `rem_sleep_minutes`, `slow_wave_sleep_minutes` |
| sleep needed totals            | `sleep_need_minutes`                                            |
| `sleep_performance_percentage` | `sleep_performance_percentage`                                  |
| `sleep_consistency_percentage` | `sleep_consistency_percentage`                                  |
| `sleep_efficiency_percentage`  | `sleep_efficiency_percentage`                                   |
| `respiratory_rate`             | `respiratory_rate`                                              |
| cycle `strain`                 | `strain_score`                                                  |
| cycle `kilojoule`              | `training_load`                                                 |
| workout `distance_meter`       | `distance_meters`                                               |
| workout duration               | `active_minutes`                                                |

## Why metrics are split this way

Some fields should be aggregated by max per day, not sum, because multiple devices can duplicate them.

Examples:

- sleep minutes
- sleep need
- strain score
- training load

Some fields are better as averages:

- resting heart rate
- HRV
- respiratory rate
- blood oxygen

That logic sits inside `MetricAnalyticsService`, not inside React.

## UI surfaces using WHOOP-backed data

- dashboard athlete recovery cards
- dashboard athlete trend summaries
- wearables connection detail cards
- wearables trend view
- coach visibility through shared athlete snapshots

## Current limitations

- No webhook receiver yet, so sync is polling-based.
- WHOOP token failure visibility is still basic.
- Duplicate wearable providers can still create overlapping coverage, so analytics stay conservative.
- OAuth UI does not yet show a polished success or failure toast.

## Recommended next improvements

1. Add a WHOOP webhook endpoint and verification layer.
2. Add retry history and error detail per connection.
3. Add operator controls for reconnect, token refresh, and last-failure review.
4. Add per-athlete coaching rules driven by readiness and sleep debt.
5. Add social auth rollout so signup and device connection feel like one clean system instead of separate chores.
