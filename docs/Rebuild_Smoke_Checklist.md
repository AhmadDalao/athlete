# Throughline Rebuild Smoke Checklist

Last updated: 2026-08-12

Use this checklist before deploying or after pulling the rebuild onto Hostinger.

## Test Accounts

| Role | Email | Password | Expected landing |
| --- | --- | --- | --- |
| Owner | `owner@throughline.test` | `password` | `/admin/dashboard` |
| Admin | `admin@throughline.test` | `password` | `/admin/dashboard` |
| Coach | `coach@throughline.test` | `password` | `/coach` |
| Athlete | `athlete@throughline.test` | `password` | `/app` |

## Public Website

- Open `/` and confirm the top Login button is visible.
- Confirm homepage headline, pricing cards, and contact CTA render.
- Open `/contact`, submit a test message, and confirm the success status.
- Login as owner and confirm the contact message appears in `/admin/contact-submissions`.

## Owner/Admin

- Login as owner and confirm `/dashboard` redirects to `/admin/dashboard`.
- Open `/admin/users`, search a user, change page size, and export CSV.
- Open a user detail page and confirm role/status/contact fields are visible.
- Confirm an organization admin sees only active-organization users, coaches, athletes, counts, details, and exports.
- Confirm disabling a multi-organization member changes only the active organization membership.
- Confirm organization owners cannot be disabled or demoted from the people screens.
- Open `/admin/permissions`, select an admin, change permissions, and save.
- Open an organization member's permission page, deny one role-default permission, and confirm the matching page/action returns `403` for that member only.
- Change that member's organization role and confirm stale permission overrides are cleared.
- Open `/admin/settings`, update homepage/contact/pricing copy, and confirm the public website changes.
- Open `/admin/reports`, change the date range/search/page size, and export the organization-scoped CSV.
- Open `/admin/email-logs`, filter delivery status/type/date, and export CSV.
- Open `/admin/audit-log`, filter action/entity/date, and export CSV.
- Confirm an organization admin cannot open `/admin/email-logs` or `/admin/audit-log` without `admin.audit`.

## Coach

- Login as coach and confirm `/dashboard` redirects to `/coach`.
- Open `/coach/athletes` and confirm only assigned athletes are listed.
- Open an athlete profile and review programs, schedule, workout logs, and progress logs.
- Open `/coach/programs`, create a program, then open it.
- Add a session with exercises, sets, reps, rest, load, notes, and media URL.
- Edit the session, add/remove exercise rows, then save.
- Open `/coach/invitations`, create an invite, resend it, then cancel it.
- Accept a new-user invite and confirm the athlete membership, profile, and coach assignment belong to the inviting organization.
- Accept an existing-user invite with the current account password and confirm no duplicate account or global role rewrite occurs.
- Confirm an incorrect existing-account password, inactive coach, expired invite, and cross-organization invite mutation are rejected.

## Athlete

- Login as athlete and confirm `/dashboard` redirects to `/app`.
- Confirm active programs and calendar load.
- Change calendar month and pick a day with a workout.
- Open the workout, review exercises, enter actual reps/load/RPE, and save partial.
- Reopen the workout and mark it completed.
- Open `/app/progress`, add a check-in, filter the progress table, and confirm charts update.
- Confirm athlete cannot open another athlete program URL.

## Mobile Width

Test at 390px and 430px browser widths:

- Sidebar opens from the Menu button and closes correctly.
- Bottom navigation is visible and does not hide primary actions.
- Calendar cells fit without horizontal page overflow.
- Athlete daily schedule uses cards on mobile.
- Athlete workout execution uses cards for exercises and set logging.
- Coach program builder uses cards for exercise input rows.
- Coach athlete profile uses cards for programs, schedule, workout logs, and progress logs.
- Admin tables scroll inside their table panel, not across the whole page.

## Pass Criteria

- No dashboard route is exposed to normal athlete users.
- No horizontal page overflow on phone widths.
- Livewire filters update without a full browser reload.
- Exports download CSV files.
- Audit/email logs are written for settings, permissions, invitations, and contact actions.
- Flutter athlete/coach login, navigation, organization switching, calendar, workout, progress, and messaging load without overflow.
- `php artisan test`, `./vendor/bin/pint --test`, `npm run lint`, `npm run build`, Flutter analyze/tests, `php artisan route:cache`, and `php artisan view:cache` all pass.
