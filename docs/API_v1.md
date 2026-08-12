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

## Progress photos

Athletes can list, upload, and delete their own progress photos. Coaches can upload photos for assigned athletes and choose `private`, `coaches`, or `athlete` visibility. A coach can edit or delete only records they created.

```bash
curl -X POST "https://athlete.ahmaddalao.com/api/v1/app/photos" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Organization-ID: $ORGANIZATION_ID" \
  -F "photo=@front.jpg" \
  -F "taken_on=2026-08-11" \
  -F "category=front" \
  -F "notes=Monthly check-in"
```

Accepted image types are JPEG, PNG, and WebP. The maximum file size is 10 MB. Photo `url` values are protected API endpoints, so image requests must include the same bearer token and organization header as JSON requests.

Coach review endpoints:

- `POST /coach/athletes/{athlete}/notes`
- `PATCH /coach/athletes/{athlete}/notes/{note}`
- `DELETE /coach/athletes/{athlete}/notes/{note}`
- `POST /coach/athletes/{athlete}/photos`
- `DELETE /coach/athletes/{athlete}/photos/{photo}`

Private coach notes are visible only to their author. Organization notes are visible to other authorized coaches assigned to the athlete. Every note and photo mutation is audited.

## Message attachments

`POST /messages/{conversation}` accepts JSON for text-only messages or `multipart/form-data` for an attachment. JPEG, PNG, WebP, and PDF files up to 10 MB are accepted. Either `body` or `attachment` is required.

```bash
curl -X POST "https://athlete.ahmaddalao.com/api/v1/messages/$CONVERSATION_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Organization-ID: $ORGANIZATION_ID" \
  -F "body=Latest movement check" \
  -F "attachment=@movement.jpg"
```

Attachment URLs are protected and only conversation participants can download them. Flutter currently selects image attachments; PDF upload remains available through the API and web clients.

## Mobile feature endpoints

| Area | Endpoints |
| --- | --- |
| Athlete progress | `GET/POST /app/progress` |
| Athlete photos | `GET/POST /app/photos`, `DELETE /app/photos/{photo}` |
| Coach athlete review | `GET /coach/athletes/{athlete}`, note and photo actions above |
| Messaging | `GET /messages`, `GET/POST /messages/{conversation}` |
| Profile | `GET/PUT /profile`, `PUT /profile/theme` |
| Protected media | `GET /media/progress-photos/{photo}`, `GET /media/message-attachments/{media}` |

## Media access

API media URLs require authentication. Laravel rechecks the active organization, athlete assignment or conversation participation on every request. Clients must not strip authorization headers when loading an image URL.
