# Throughline

Website-first coaching MVP for owners, administrators, coaches, and athletes.

## Stack

- PHP 8.2+
- Laravel 12
- Blade
- Livewire 4
- Bootstrap 5
- Font Awesome 6
- Vite 6
- MySQL in production, SQLite for local tests

The active rebuild intentionally excludes React, Inertia, native mobile, Redis, watch sync, Stripe, and OAuth. Those systems stay out until the coaching workflow is accepted.

## Product Areas

- Public website, login, pricing, and contact capture
- Owner/admin operations, users, permissions, settings, invitations, and audit logs
- Coach roster, athlete profiles, invitations, programs, sessions, exercises, and media
- Athlete calendar, assigned programs, workout execution, and progress logging

`/dashboard` only redirects users by role:

- owner/admin -> `/admin/dashboard`
- coach -> `/coach`
- athlete -> `/app`

## Modular Structure

```text
app/
  Livewire/
    Admin/                 Admin screens
    Athlete/               Athlete screens
    Coach/                 Coach screens
    Concerns/              Shared Livewire table behavior
    Forms/                 Reusable form state and validation
  Services/                Domain operations, delivery, and auditing
  Support/                 Permission and training-media helpers

resources/
  css/
    theme/                 Design tokens
    base/                  Foundation rules
    layout/                Shell and navigation
    components/            Reusable UI modules
    pages/                 Role/page-specific rules
    utilities/             Responsive overrides
  js/
    modules/               Focused browser behaviors
    app.js                 Frontend entry point
  views/
    components/tl/         Reusable Throughline Blade components
    livewire/              Screen templates by role
```

Vite owns Bootstrap, Font Awesome, custom CSS, and browser JavaScript. Production does not depend on frontend CDNs.

## Local Setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

For active frontend work, run `npm run dev` in a second terminal.

## Verification

```bash
composer validate --strict
./vendor/bin/pint --test
npm audit
npm run build
php artisan test
php artisan view:cache
git diff --check
```

## Production

The live site is `https://athlete.ahmaddalao.com`. Hostinger runs PHP 8.2 and MySQL. Build frontend assets locally and deploy the generated `public/build` directory with the PHP application.

See:

- [Rebuild system guide](docs/Throughline_Rebuild_MVP.md)
- [Hostinger deployment checklist](docs/Hostinger_Deployment_Checklist.md)
- [Browser smoke checklist](docs/Rebuild_Smoke_Checklist.md)
