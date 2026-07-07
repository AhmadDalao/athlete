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
| Admin | `/admin/coaches` | Coach table |
| Admin | `/admin/athletes` | Athlete table |
| Admin | `/admin/invitations` | Invitation tracking |
| Admin | `/admin/permissions` | Grouped permission control |
| Admin | `/admin/settings` | Website, invite, and mail settings |
| Admin | `/admin/audit-log` | Audit and email log table |
| Coach | `/coach` | Coach workspace summary |
| Coach | `/coach/athletes` | Assigned athletes table |
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
- `admin`: admin access, audit, users, coaches, athletes, invitations.
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
- Athlete can mark own workout completed.

## Next Build Slice

Build next in this order:

1. Add exports and row-detail pages to admin tables.
2. Add admin edit screens for users, coaches, and athletes.
3. Add stronger invitation email templates and resend/cancel audit records.
4. Add coach-side athlete profile detail with programs, sessions, logs, and progress.
5. Add athlete workout set logging instead of only session status.
6. Add settings-driven public pricing/membership copy.
7. Add production deployment checklist for Hostinger.

Do not reintroduce native mobile, watch sync, Stripe, OAuth, or API complexity until the website MVP is stable.
