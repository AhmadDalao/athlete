# Throughline

Multi-tenant coaching platform for owners, administrators, coaches, and athletes, with a Laravel web application and role-adaptive Flutter mobile app.

## Stack

- PHP 8.2+
- Laravel 12
- Blade
- Livewire 4
- Bootstrap 5
- Font Awesome 6
- Vite 6
- MySQL in production, SQLite for local tests
- Laravel Sanctum API under `/api/v1`
- Flutter mobile app with Riverpod, GoRouter, Dio, SecureStorage, and Drift

The active platform does not use React, Inertia, Redis, or WebSockets. Payments, social login, and wearable integrations remain later integrations.

## Product Areas

- Public website, login, pricing, and contact capture
- Owner/admin operations, organizations, users, permissions, reports, settings, invitations, email logs, and audit logs
- Coach roster, athlete profiles, invitations, reusable programs, scheduling, reports, photos, and messaging
- Athlete calendar, assigned programs, workout execution, progress, photos, notifications, and messaging
- Flutter athlete and coach workflows backed by the same Laravel API and organization rules

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
  Queries/                 Reusable reporting and list queries
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

mobile/
  lib/
    core/                  API, auth, storage, theme, routing
    features/              Athlete, coach, workout, progress, and messaging screens
  test/                    Flutter unit and widget tests
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
flutter analyze --no-pub --suppress-analytics
flutter test --no-pub --suppress-analytics
git diff --check
```

## Production

The live site is `https://athlete.ahmaddalao.com`. Hostinger runs PHP 8.2 and MySQL. Build frontend assets locally and deploy the generated `public/build` directory with the PHP application.

See:

- [Rebuild system guide](docs/Throughline_Rebuild_MVP.md)
- [Hostinger deployment checklist](docs/Hostinger_Deployment_Checklist.md)
- [Browser smoke checklist](docs/Rebuild_Smoke_Checklist.md)
