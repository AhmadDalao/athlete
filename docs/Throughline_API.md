# Throughline API

Date: 2026-06-28

## What is live

The API is now split into two parts:

- `API v1` for authenticated app and partner access
- public ingest for device metric delivery

This is not an OpenAPI dump yet. It is the developer doc for what is actually implemented right now.

The 2026-06-28 coach ownership slice also adds internal Inertia web routes for athlete invitations, athlete profiles, and athlete files. Those routes are not external JSON API endpoints yet.

## Base rules

- Production API base URL: `https://athlete.ahmaddalao.com/api/v1`
- App-relative API base path: `/api/v1`
- All endpoint paths below are app-relative. In production they are served from the athlete subdomain root.
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

`POST https://athlete.ahmaddalao.com/api/v1/auth/tokens`

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
| `training:read`   | Read programs, sessions, logs, and workout execution    | admin, coach, athlete |
| `training:write`  | Submit athlete workout logs and set execution           | athlete               |
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

`https://athlete.ahmaddalao.com`

## Internal web routes for coach-owned athlete onboarding

These are normal Laravel/Inertia web routes, not `/api/v1` JSON routes.

| Route | Purpose | Access |
| --- | --- | --- |
| `GET /roster/invites` | Coach invitation table | coach/admin with `roster.invite` |
| `POST /roster/invites` | Create athlete invitation | coach/admin with `roster.invite` |
| `POST /roster/invites/{invitation}/resend` | Resend invite link | owning coach or admin |
| `POST /roster/invites/{invitation}/cancel` | Cancel pending invite | owning coach or admin |
| `GET /admin/invitations` | Platform-wide invitation table | admin with `admin.invitations.view` |
| `GET /invites/{token}` | Public invite acceptance form | public token route |
| `POST /invites/{token}` | Accept invite and create/link athlete | public token route |
| `GET /athletes/{user}` | Athlete profile/detail table | assigned coach or admin |
| `POST /athletes/{user}/files` | Upload athlete file | assigned coach/admin with file manage permission |
| `GET /athlete-files/{athleteFile}/download` | Private file download | authorized viewer |
| `PATCH /athlete-files/{athleteFile}` | Move/edit file metadata | assigned coach/admin; athlete move is admin-only |
| `POST /athlete-files/{athleteFile}/archive` | Archive file | assigned coach/admin |
| `GET /admin/files` | Platform-wide file library | admin with `athlete.files.view` |

Invitation tokens are stored only as hashes. Athlete files are stored privately and downloaded through authorization checks.

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

### `GET /api/v1/training/sessions/{trainingSession}/execution`

Athlete only in this slice.

Returns the mobile-ready workout execution payload:

- session title, focus, schedule, instructions, and video URL
- parent training program
- assigned coach
- normalized exercise rows
- one target set row per prescribed set
- existing actual set log data if the athlete already started
- existing workout log and journal values if present

Rules:

- requires `training:read`
- the authenticated athlete must own the session
- coaches/admins can still view training data through the broader training surfaces, but they do not edit athlete execution data

### `POST /api/v1/training/sessions/{trainingSession}/sets`

Athlete only.

Creates or updates set-level execution rows.

Request:

```json
{
    "sets": [
        {
            "exercise_index": 0,
            "set_number": 1,
            "actual_reps": "6",
            "actual_load": "120 kg",
            "actual_rpe": 8,
            "completed": true,
            "notes": "Moved well."
        }
    ]
}
```

Rules:

- requires `training:write`
- the authenticated athlete must own the session
- rows must match a prescribed exercise/set target
- saving any set data creates/updates the session workout log
- if all target sets are completed, the workout log becomes `completed`
- otherwise it remains `partial`

### `POST /api/v1/training/sessions/{trainingSession}/complete`

Athlete only.

Marks the workout status and saves journal fields.

Request:

```json
{
    "completion_status": "completed",
    "performed_at": "2026-06-27",
    "duration_minutes": 52,
    "exertion_rating": 8,
    "energy_score": 7,
    "soreness_score": 4,
    "stress_score": 3,
    "sleep_quality_score": 8,
    "notes": "Completed from the athlete app."
}
```

Allowed `completion_status` values:

- `completed`
- `partial`
- `missed`

Rules:

- requires `training:write`
- the authenticated athlete must own the session
- `completed` backfills missing target set rows as completed for MVP consistency
- `missed` records the opt-out/missed state

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

## Authenticated web billing endpoints

These are not part of API v1 because they are browser-driven app routes, not token APIs. They still matter for integrations and launch docs.

## Authenticated web API access page

### `GET /api-access`

Auth:

- session-authenticated user

What it does:

- shows allowed API abilities for the current user
- lets the user create and revoke personal bearer tokens
- shows manageable device ingest endpoints and keys
- provides copy-ready curl examples for common integration paths

### `POST /api-access/tokens`

Auth:

- session-authenticated user

Request fields:

- `token_name`
- `abilities[]`

What it does:

- creates a Laravel Sanctum personal access token for the authenticated user
- constrains requested abilities to the role-allowed set
- returns the plain text token through the redirected page once

### `DELETE /api-access/tokens/{tokenId}`

Auth:

- session-authenticated user

What it does:

- revokes one token belonging to the authenticated user
- does not allow deleting another user's token

### `POST /billing/checkout`

Auth:

- session-authenticated user

Request:

```json
{
    "plan_id": 2
}
```

What it does:

- validates that the plan exists and is active
- creates a Stripe Checkout session
- redirects the browser to Stripe

### `POST /billing/portal`

Auth:

- session-authenticated user with a saved `stripe_customer_id`

What it does:

- creates a Stripe Billing Portal session
- redirects the browser to Stripe

## Public billing webhook endpoint

### `POST /webhooks/stripe`

Auth:

- `Stripe-Signature` header verified against `STRIPE_WEBHOOK_SECRET`

Handled events:

- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

What it does:

- stores the webhook id for replay protection
- syncs membership state from Stripe subscriptions
- records invoice outcomes in `payment_events`

## Public WHOOP webhook endpoint

### `POST /webhooks/whoop`

Auth:

- `X-WHOOP-Signature`
- `X-WHOOP-Signature-Timestamp`

What it does:

- verifies the WHOOP HMAC signature and timestamp window
- validates the webhook payload shape
- stores one `whoop_webhook_events` row per unique `trace_id`
- returns a fast success response for shared-hosting-safe intake
- relies on `php artisan throughline:whoop:webhooks:process` for targeted reconciliation sync

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

- public partner registration flow
- roster write endpoints
- training-program creation/update via API

Those can be added next, but they are not part of the live API surface today.

## Training media note

Training session payloads now include nullable workout video metadata:

```json
{
    "videoUrl": "https://www.youtube.com/watch?v=example"
}
```

Clients should render supported providers directly when possible:

- YouTube watch, short, or `youtu.be` URLs
- Vimeo page URLs
- direct MP4/WebM/Ogg video URLs
- any other URL as a normal external video link
