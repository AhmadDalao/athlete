# Throughline API v1

The Flutter app uses Laravel Sanctum bearer tokens and the versioned API at `/api/v1`.

## Request flow

1. Call `POST /api/v1/auth/login` with email, password, and a human-readable device name.
2. Save the returned token only in platform secure storage.
3. Select an organization from the login response.
4. Send `Authorization: Bearer <token>` and `X-Organization-ID: <id>` on authenticated requests.
5. If the server returns `401`, clear local authentication and return to login.
6. If the server returns `409 sync_conflict`, refresh the workout before replaying a local draft.

## Response contract

Successful responses always include:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

Failures add a structured `error` object:

```json
{
  "data": null,
  "meta": {},
  "links": {},
  "error": {
    "code": "validation_failed",
    "message": "The submitted data is invalid.",
    "fields": {
      "email": ["The email field is required."]
    }
  }
}
```

Paginated responses use `meta.current_page`, `meta.last_page`, `meta.per_page`, and `meta.total`. Page size is capped at 100.

## Flutter login example

```dart
final response = await dio.post<Map<String, dynamic>>(
  '/api/v1/auth/login',
  data: {
    'email': email,
    'password': password,
    'device_name': 'Throughline Android',
  },
);

final token = response.data!['data']['token'] as String;
await secureStorage.write(key: 'access_token', value: token);
```

The full machine-readable contract is in [`docs/openapi.yaml`](openapi.yaml).

## Coach write workflow

The coach mobile workspace uses the same organization-scoped contract as the web workspace:

1. Create or update a reusable program with `/coach/programs`.
2. Add phases and ordered sessions under `/coach/programs/{program}`.
3. Include complete exercise prescriptions in each session: section, sets, reps, load, unit, rest, coaching cue, and media URL.
4. Assign the template with `/coach/programs/{program}/assignments`; Laravel generates the dated athlete schedule.
5. Reschedule individual workouts with `/coach/schedule/{workout}/reschedule` without mutating the template.
6. Invite athletes through `/coach/invitations` and manage pending links through the resend/cancel actions.

Every coach operation is checked against both the active `X-Organization-ID` and the authenticated coach's ownership. Mobile clients cannot override those boundaries.
