# Throughline API

Date: 2026-06-15

## What is live

The API is now split into two parts:

- `API v1` for authenticated app and partner access
- public ingest for device metric delivery

This is not an OpenAPI dump yet. It is the developer doc for what is actually implemented right now.

## Base rules

- Production API base URL: `https://ahmaddalao.com/athlete/api/v1`
- App-relative API base path: `/api/v1`
- All endpoint paths below are app-relative. In production they are served under `/athlete`.
- Auth for app endpoints: Laravel Sanctum bearer tokens
- Auth for ingest: `X-Throughline-Key` header tied to a device connection
- Dates use `YYYY-MM-DD`
- Timestamps use ISO 8601 strings
- Responses are JSON

Common response shape:

```json
{
    "data": {},
    "meta": {
        "version": "v1",
        "generatedAt": "2026-06-15T00:00:00+03:00"
    }
}
```

Paginated collections use:

```json
{
    "data": {
        "items": {
            "data": [],
            "meta": {
                "currentPage": 1,
                "lastPage": 1,
                "perPage": 10,
                "total": 3,
                "from": 1,
                "to": 3
            },
            "links": {
                "prev": null,
                "next": null
            }
        }
    }
}
```

## Auth

### 1. Issue a bearer token

`POST /api/v1/auth/tokens`

Production absolute URL:

`POST https://ahmaddalao.com/athlete/api/v1/auth/tokens`

Use this for mobile apps, admin tooling, cron jobs, or partner services that need user-scoped access.

Request:

```json
{
    "email": "athlete1@throughline.test",
    "password": "password",
    "token_name": "ios-app",
    "abilities": ["profile:read", "progress:read", "progress:write"]
}
```

Notes:

- `token_name` is required
- `device_name` is also accepted as an alias for `token_name`
- if `abilities` is omitted, the token gets the full default scope for that user role
- token expiry is controlled by `THROUGHLINE_API_TOKEN_TTL_DAYS` and defaults to `30`

Response:

```json
{
    "data": {
        "token": "1|plain-text-token",
        "tokenType": "Bearer",
        "tokenName": "ios-app",
        "abilities": ["profile:read", "progress:read", "progress:write"],
        "expiresAt": "2026-07-15T00:00:00+03:00",
        "user": {
            "id": 4,
            "email": "athlete1@throughline.test",
            "primaryRole": "athlete"
        }
    }
}
```

### 2. Revoke the current bearer token

`DELETE /api/v1/auth/tokens/current`

This only revokes the token used on the request.

### 3. Inspect the authenticated user

`GET /api/v1/me`

Returns:

- viewer profile
- current token metadata
- allowed abilities for that user
- linked social accounts

## Token abilities

These are the abilities currently implemented:

| Ability           | Purpose                                                 | Typical roles         |
| ----------------- | ------------------------------------------------------- | --------------------- |
| `profile:read`    | Read `/me` and token metadata                           | admin, coach, athlete |
| `dashboard:read`  | Read role-aware dashboard data                          | admin, coach, athlete |
| `roster:read`     | Read coach-athlete assignments                          | admin, coach          |
| `training:read`   | Read programs, sessions, and logs                       | admin, coach, athlete |
| `training:write`  | Submit athlete workout logs                             | athlete               |
| `progress:read`   | Read athlete check-ins and progress analytics           | admin, coach, athlete |
| `progress:write`  | Create or update athlete check-ins                      | athlete               |
| `membership:read` | Read memberships and recent payment history             | admin, coach, athlete |
| `wearable:read`   | Read device connections, snapshots, and trend analytics | admin, coach, athlete |
| `admin:read`      | Read admin control-center metrics and queues            | admin                 |

Role rules are enforced on top of abilities.

That matters:

- a coach token with `admin:read` is rejected when the token is created
- an athlete token with `roster:read` is rejected when the token is created
- an athlete who somehow hits `/api/v1/roster` still gets blocked by role checks

## API v1 endpoints

Production absolute base for every route in this section:

`https://ahmaddalao.com/athlete`

### `GET /api/v1/dashboard`

Returns the role-aware dashboard surface.

Payload sections:

- `viewer`
- `admin`
- `coach`
- `athlete`

Only the sections allowed for the authenticated role are populated.

### `GET /api/v1/roster`

Coach/admin only.

Returns:

- roster summary counts
- paginated assignments
- coach options for admins
- athlete options
- assignment status options

Each assignment includes:

- coach summary
- athlete summary
- membership state
- latest wearable snapshot
- latest athlete check-in
- current training program status

### `GET /api/v1/training`

Returns:

- role-aware training summary
- paginated programs
- session detail
- normalized exercise rows
- workout logs
- coach roster athlete list when the viewer is a coach

### `POST /api/v1/training/sessions/{trainingSession}/workout-log`

Athlete only.

Creates or updates the athlete log for that session.

Request:

```json
{
    "completion_status": "completed",
    "performed_at": "2026-06-15T18:30:00+03:00",
    "duration_minutes": 61,
    "exertion_rating": 7,
    "notes": "Felt clean."
}
```

Rules:

- the authenticated athlete must own the session through the training program
- one workout log per athlete per session

### `GET /api/v1/progress`

Returns the progress surface.

Athlete response includes:

- personal summary
- progress report
- recent check-ins
- latest check-in
- current training context
- latest wearable snapshot

Coach/admin response includes:

- high-level progress summary
- paginated visible athletes
- progress overview and alerts per athlete

### `POST /api/v1/progress/check-ins`

Athlete only.

Creates or updates the athlete check-in for the provided `logged_date`.

Request fields:

- `logged_date`
- `weight_kg`
- `body_fat_percentage`
- `waist_cm`
- `calories_consumed`
- `protein_grams`
- `carbs_grams`
- `fat_grams`
- `water_liters`
- `meals_logged_count`
- `energy_score`
- `soreness_score`
- `stress_score`
- `sleep_quality_score`
- `notes`

Important:

- this is intentionally the manual layer
- wearable APIs do not reliably cover food intake, bodyweight, soreness context, or compliance notes

### `PATCH /api/v1/progress/check-ins/{athleteCheckIn}`

Athlete only.

Rules:

- the athlete can update only their own check-ins
- trying to patch another athlete's record returns `404`

### `GET /api/v1/memberships`

Returns visible memberships for the authenticated user scope.

Query params:

- `status`

Returns:

- summary metrics
- paginated memberships
- recent payment events per membership
- valid payment event types and statuses for clients that need UI select options

Visibility:

- admin sees all
- coach sees own membership plus assigned-athlete memberships
- athlete sees only their own membership

### `GET /api/v1/wearables`

Returns:

- device connection summary
- paginated connections
- latest normalized snapshot
- per-connection analytics
- WHOOP integration metadata

For non-OAuth connections, owners and admins also get the ingest endpoint path and last four characters of the ingest key.

We do not return the full ingest key through the API. That would be sloppy.

### `GET /api/v1/admin/control-center`

Admin only.

Returns:

- top-line business and ops summary
- membership queue
- payment queue
- device attention queue
- athlete coverage gaps
- coach load
- signup mix
- ops playbook

## Public ingest endpoint

### `POST /api/device-connections/{public_id}/ingest`

This route is for normalized metric delivery from external integrations or middleware you control.

Auth:

- header: `X-Throughline-Key: {ingest_key}`

Request:

```json
{
    "metric_date": "2026-06-15",
    "external_event_id": "garmin-event-123",
    "metrics": {
        "readiness_score": 82,
        "strain_score": 13.4,
        "sleep_minutes": 445,
        "sleep_need_minutes": 480,
        "resting_heart_rate": 48,
        "heart_rate_variability": 71.2,
        "training_load": 438.2
    },
    "raw_payload": {
        "provider": "garmin",
        "source": "partner-sync"
    }
}
```

What it does:

- validates normalized fields
- writes a raw ingest audit row
- upserts a normalized `metric_snapshots` row
- refreshes the device connection sync state

Response status:

- `202 Accepted`

If the key is wrong:

- `401 Invalid ingest key`

## Visibility model

These rules apply across the API:

- athletes only see their own data
- coaches only see assigned athletes and their own coach-side data
- admins see platform-wide data

This applies even if the token ability looks broad enough. Ability checks do not replace ownership checks.

## Error model

Common statuses:

- `401` invalid bearer token or invalid ingest key
- `403` missing token ability or blocked by role rules
- `404` resource exists but is not visible to that user
- `422` validation failed

Validation errors follow Laravel's standard JSON error format.

## Local demo users

These are seeded for local testing:

- `admin@throughline.test`
- `coach@throughline.test`
- `coach2@throughline.test`
- `athlete1@throughline.test`
- `athlete2@throughline.test`
- `athlete3@throughline.test`

Password for all demo users:

- `password`

## Environment and ops notes

- Sanctum is installed and token-backed auth is live
- expired tokens should be pruned by the scheduled command:
    - `php artisan sanctum:prune-expired --hours=24`
- same-domain SPA cookie auth can be used through Sanctum if `SANCTUM_STATEFUL_DOMAINS` is configured correctly
- shared hosting is still fine here; none of this requires Redis

## Not live yet

These are not API features yet, and pretending otherwise would be dumb:

- billing provider webhooks
- public partner registration flow
- roster write endpoints
- training-program creation/update via API
- Apple auth
- phone auth

Those can be added next, but they are not part of the live API surface today.
