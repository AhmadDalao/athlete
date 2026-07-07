# Throughline Clean Rebuild MVP

Last updated: 2026-07-07

## Decision

The overloaded React/Inertia/mobile/watch stack was archived. The active product is now a website-first Laravel MVP:

- Laravel 12
- Blade
- Livewire
- Bootstrap 5
- FontAwesome
- MySQL/SQLite compatible schema
- Custom Throughline dark theme
- No Redis
- No native mobile
- No watch/WHOOP/Stripe/OAuth in this slice

This is intentional. The current priority is a direct coaching product that is easy to operate, deploy, and test on Hostinger.

## Safety Archive

Before replacing the old app, the previous state was preserved:

- Backup branch: `codex/archive-pre-rebuild-20260707-113556`
- Backup tag: `codex/pre-rebuild-20260707-113556`
- Local database backup: `storage/backups/database-pre-rebuild-20260707-113556.sqlite`

The backup directory is ignored by git and should not be pushed.

## Login Accounts

Seeded local accounts:

| Role | Email | Password | Landing |
| --- | --- | --- | --- |
| Owner | `owner@throughline.test` | `password` | `/admin/dashboard` |
| Admin | `admin@throughline.test` | `password` | `/admin/dashboard` |
| Coach | `coach@throughline.test` | `password` | `/coach` |
| Athlete | `athlete@throughline.test` | `password` | `/app` |

Owner accounts have every permission and cannot be locked out.

## Core Routes

| Area | Route | Purpose |
| --- | --- | --- |
| Public | `/` | Homepage with login and contact CTA |
| Public | `/contact` | Livewire contact form saved to database |
| Auth | `/login` | Email/password login |
| Redirect | `/dashboard` | Sends users to the correct role area |
| Admin | `/admin/dashboard` | Business operations dashboard |
| Admin | `/admin/users` | User table and account creation |
| Admin | `/admin/users/export` | Filtered user CSV export |
| Admin | `/admin/users/{user}` | User profile, edit form, programs, logs, and audit trail |
| Admin | `/admin/coaches` | Coach table |
| Admin | `/admin/athletes` | Athlete table |
| Admin | `/admin/invitations` | Invitation tracking |
| Admin | `/admin/invitations/export` | Filtered invitation CSV export |
| Admin | `/admin/contact-submissions` | Public contact inbox |
| Admin | `/admin/contact-submissions/export` | Filtered contact CSV export |
| Admin | `/admin/permissions` | Grouped permission control |
| Admin | `/admin/settings` | Website, invite, and mail settings |
| Admin | `/admin/audit-log` | Audit and email log table |
| Admin | `/admin/audit-log/export` | Filtered audit/email CSV export |
| Coach | `/coach` | Coach workspace summary |
| Coach | `/coach/athletes` | Assigned athletes table |
| Coach | `/coach/athletes/{athlete}` | Coach-scoped athlete profile |
| Coach | `/coach/programs` | Program list and creation |
| Coach | `/coach/programs/{program}` | Add sessions and exercises |
| Coach | `/coach/invitations` | Invite athletes by email |
| Athlete | `/app` | Athlete calendar and assigned programs |
| Athlete | `/app/programs/{program}` | Assigned program detail |
| Athlete | `/app/workouts/{session}` | Workout detail and completion |
| Athlete | `/app/progress` | Manual progress log |

## Permission Model

Permissions live in `App\Support\PermissionCatalog`.

Default role behavior:

- `owner`: every permission through `Gate::before`.
- `admin`: admin access, audit, contact inbox, users, coaches, athletes, invitations.
- `coach`: coach workspace, programs, assigned athlete records, invitations.
- `athlete`: athlete app, own progress, own workout completion.

The current MVP route guards are role-safe:

- Coaches and athletes cannot open `/admin/dashboard`.
- Athletes can only open programs/sessions assigned to them.
- Coaches can only open their own programs.

## Data Model

The rebuild uses one compact migration:

- `users`
- `user_permissions`
- `platform_settings`
- `audit_logs`
- `email_logs`
- `contact_submissions`
- `athlete_invitations`
- `coach_athlete_assignments`
- `training_programs`
- `training_sessions`
- `workout_logs`
- `progress_entries`

This keeps the MVP direct. Memberships, files, payments, watch sync, and native app tables are intentionally postponed.

`workout_logs` now stores simple execution data: status, duration, RPE, notes, and a JSON set log for actual reps/load/RPE per prescribed set. That is enough for the MVP without rebuilding the old oversized set-log system.

## UI Rules

- Dark premium Throughline identity across public, admin, coach, and athlete areas.
- Records belong in tables.
- Cards summarize only.
- Tables use Livewire refresh, search, page size `10/25/50/100/All`, Bootstrap pagination, and horizontal overflow protection.
- Normal users do not see an admin dashboard path.
- `/dashboard` is only a role-aware redirect.

## Verification

Current checks run clean:

```bash
composer validate --strict
./vendor/bin/pint --test
php artisan test
php artisan optimize:clear
php artisan route:cache
php artisan view:cache
```

Current automated coverage:

- Dashboard redirects by role.
- Coach/athlete admin access is forbidden.
- Athlete can only view own program.
- Athlete can mark own workout completed with execution data.
- Admin can open and export users.
- Coach can open only assigned athlete profiles.
- Admin can resend invitations and write email/audit logs.
- Admin can export invitation records.
- Admin can review contact submissions and export them.
- Admin can filter and export audit/email logs.
- Admin permissions and settings writes create audit logs.
- Coach can update programs, edit sessions, and delete empty sessions.
- Athlete can save progress check-ins and filter the progress table.

## Completed Rebuild Slices

### Foundation

- Archived old React/Inertia/mobile-heavy application.
- Rebuilt public/auth/admin/coach/athlete shell with Laravel, Blade, Livewire, Bootstrap, FontAwesome, and a custom dark theme.
- Added seeded owner/admin/coach/athlete accounts.
- Added role-aware `/dashboard` redirect.

### Control And Coaching

- Added admin user detail/edit page.
- Added filtered user CSV export.
- Added coach athlete profile detail with programs, schedule, workout logs, and progress logs.
- Added athlete workout execution logging for status, duration, RPE, notes, and set rows.
- Added centralized invitation delivery service.
- Added coach/admin invite resend and cancel actions.
- Added email and audit logging for invitation actions.
- Added invitation email subject/body settings with template tokens.
- Added public pricing/membership settings for three editable plan cards.
- Added permissions defaults/clear shortcuts and audit logging.
- Grouped settings into website identity, invitation control, and mail labels.
- Added admin contact inbox with Livewire search, status filter, page size control, CSV export, and audit logging.
- Added invitation CSV export that respects current search/status filters.
- Added coach program update/archive controls.
- Added coach session edit/delete controls. Empty sessions can be deleted; sessions with athlete logs are cancelled instead to protect history.
- Added audit/email log filters for action, entity, status, type, date range, search, page size, and CSV export.
- Improved athlete progress with summary stats, compact trend charts, date filters, and the table as the source of truth.
- Added Hostinger deployment checklist: `docs/Hostinger_Deployment_Checklist.md`.

### Mobile Usability

- Added a compact mobile top bar with a slide-out role navigation menu.
- Added role-specific fixed bottom navigation for admin, coach, and athlete users.
- Tightened mobile spacing, calendar cells, panels, stat cards, forms, and table overflow rules.
- Added mobile-first athlete workout cards for the selected daily schedule.
- Added mobile-first athlete program session cards.
- Added mobile-first athlete workout execution cards for exercises and set logging while keeping desktop tables intact.
- Added mobile-first coach program builder exercise cards for creating and editing sessions.
- Added mobile-first coach athlete profile cards for programs, schedule, workout logs, and progress logs.

## Next Build Slice

Build next in this order:

1. Add settings-driven contact and pricing polish where needed.
2. Add a final local browser smoke checklist for owner, coach, and athlete accounts.
3. Deploy rebuild branch only after local smoke testing is accepted.

Do not reintroduce native mobile, watch sync, Stripe, OAuth, or API complexity until the website MVP is stable.
