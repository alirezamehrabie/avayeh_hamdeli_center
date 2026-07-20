# Avayeh Hamdeli Center

A Laravel 11 application for managing beneficiaries, guardians, social workers,
services, activity attendance, sponsorship workflows, QR identities, and
role-based operational panels.

The backend is built with Laravel, Eloquent, Livewire 3, and PHPUnit. Frontend
assets are built with Vite, Tailwind CSS, Bootstrap, Alpine.js, and Sass.

## Main Features

- Authentication with role and permission based panel redirects.
- Admin dashboard for people, guardians, social workers, services, activities,
  users, reports, QR identity scanning, and child supporter workflows.
- Beneficiary and guardian registration, editing, soft deletion, saved filters,
  and advanced reporting.
- Social worker dashboard and service delivery history.
- Distribution operator workflows for service definition, allocations, entry
  gates, delivery gates, and exit gates.
- Activity definition, activity lists, and QR-based attendance check-in.
- Sponsor registration and sponsor list management.
- PDF, Excel, QR code, and scanner-related integrations.

## Tech Stack

- PHP 8.2+
- Laravel 11
- Livewire 3
- Vite 6
- Tailwind CSS 3
- Bootstrap 5 and Bootstrap Icons
- Alpine.js
- PHPUnit 11
- SQLite by default, with Laravel-supported database alternatives available

## Project Structure

```text
app/
  Console/              Artisan command classes
  Exports/              Excel/export classes
  Helpers/              Shared helper functions and utilities
  Http/                 Controllers, middleware, and HTTP entry points
  Livewire/             Livewire page and UI components
    Activities/         Activity definition, lists, and scanner UI
    Admin/              Admin dashboard, users, reports, and scanners
    Auth/               Login flow
    ChildSupporters/    Sponsor and child supporter workflows
    DistributionOperators/
                         Service allocation and gate workflows
    Guardians/          Guardian listing, editing, and deleted records
    People/             Beneficiary registration, listing, and reporting
    Services/           Service catalog, delivery, archive, and reports
    Shared/             Shared Livewire components such as QR modals
    SocialWorkers/      Social worker CRUD, dashboard, and account pages
  Models/               Eloquent models
  Observers/            Model observers
  Providers/            Service providers, gates, observers, morph maps
  Queries/              Query builders and reusable query logic
  Services/             Application services
  Traits/               Reusable PHP traits
  View/                 View composers and view-related classes

bootstrap/              Laravel bootstrap files
config/                 Laravel and package configuration
database/
  factories/            Model factories
  migrations/           Database schema migrations
  seeders/              Lookup data and primary admin seeder
lang/                   Translation files
public/                 Public web root and built assets
resources/
  css/                  Tailwind/CSS entry points
  fonts/                Local font assets
  js/                   JavaScript entry points
  sass/                 Sass and Bootstrap styling entry points
  views/                Blade and Livewire views
routes/
  web.php               Web routes and Livewire page routing
  console.php           Console route definitions
storage/                Logs, cache, uploaded files, and generated files
tests/
  Feature/              Feature, Livewire, route, and workflow tests
  Unit/                 Unit tests for isolated application logic
```

## Main Components

- `routes/web.php` defines the authenticated web routes and maps most pages to
  Livewire components.
- `app/Providers/AppServiceProvider.php` registers model observers, morph maps,
  and authorization gates such as `full-access`, `manage-people`,
  `access-admin-panel`, `access-social-worker-panel`, and distribution gate
  permissions.
- `app/Models/User.php` contains role, access-level, permission, and panel
  redirect behavior used throughout the application.
- `app/Livewire/*` contains the main page-level application screens.
- `database/seeders/DatabaseSeeder.php` loads lookup tables and creates the
  primary admin account through `AdminUserSeeder`.
- `vite.config.js` builds `resources/sass/app.scss`,
  `resources/css/app.css`, and `resources/js/app.js`.

## Setup

1. Install PHP dependencies.

   ```bash
   composer install
   ```

2. Install frontend dependencies.

   ```bash
   npm install
   ```

3. Create the environment file.

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

4. Generate the application key.

   ```bash
   php artisan key:generate
   ```

5. Configure the database in `.env`.

   The example environment uses SQLite:

   ```env
   DB_CONNECTION=sqlite
   ```

   If you use SQLite, create the database file before running migrations:

   ```bash
   touch database/database.sqlite
   ```

   On Windows PowerShell:

   ```powershell
   New-Item -ItemType File database/database.sqlite -Force
   ```

6. Run migrations and seeders.

   ```bash
   php artisan migrate --seed
   ```

7. Create the public storage symlink if the application needs public uploaded
   files.

   ```bash
   php artisan storage:link
   ```

8. Start the development servers.

   ```bash
   composer run dev
   ```

   Alternatively, run Laravel and Vite separately:

   ```bash
   php artisan serve
   npm run dev
   ```

## Common Commands

```bash
composer install       # Install PHP dependencies
npm install            # Install frontend dependencies
composer run dev       # Run Laravel and Vite together
php artisan serve      # Run the Laravel development server
npm run dev            # Run the Vite development server
npm run build          # Build production frontend assets
php artisan migrate    # Run database migrations
php artisan db:seed    # Seed lookup data; first run requires ADMIN_BOOTSTRAP_PASSWORD
php artisan test       # Run the PHPUnit test suite
./vendor/bin/pint      # Format PHP code
```

On the first seed of a new database, set `ADMIN_BOOTSTRAP_PASSWORD` to a unique
secret of at least 16 characters. Store it in the deployment secret manager,
not in version control. Later seeder runs preserve the primary administrator's
existing password and do not require the bootstrap secret.

## Testing

Run the full test suite with:

```bash
php artisan test
```

Feature tests live in `tests/Feature` and cover Livewire workflows, route
authorization, service distribution gates, activity scanning, user management,
and related application behavior. Unit tests live in `tests/Unit`.

When changing reports, exports, authorization, QR scanning, or delivery gates,
add regression coverage near the existing feature tests for that workflow.

## Development Notes

- Keep PHP code PSR-4 aligned with the folder structure under `app/`.
- Livewire component classes are in `app/Livewire`; their Blade views are under
  `resources/views`.
- Routes are mostly authenticated and gate-protected. Add or update gates in
  `AppServiceProvider` and permission behavior in the `User` model when adding
  new panels or protected workflows.
- The application uses database-backed session, cache, and queue settings in
  `.env.example`, so the related migrations must be present in any deployed
  database.
- Seeders populate lookup/reference data used by forms and reports. Run them in
  local environments after migrations.
- `AdminUserSeeder` creates or updates the primary admin account. Review and
  change seeded credentials before using the application outside local
  development.
- Keep secrets out of source control. Start from `.env.example` and configure
  production values in the deployment environment.
- Build production assets with `npm run build` before deploying if the
  deployment process does not build assets automatically.

## Deployment Checklist

Before deploying, confirm:

- `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`, and locale/timezone settings are
  correct.
- Database, queue, cache, session, mail, filesystem, and trusted proxy settings
  are configured for the target environment.
- Migrations have run successfully.
- Required seed/reference data exists.
- Public storage is linked if uploaded files are served from `storage/app/public`.
- Frontend assets have been built with `npm run build`.
- Default seeded credentials have been changed or disabled.
