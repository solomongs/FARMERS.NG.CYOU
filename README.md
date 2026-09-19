# Farmers.ng.cyou

A modular, multi-tenant Farm Management SaaS built with Laravel for farmers, farm owners, and their teams.

**Production domain:** https://farmers.ng.cyou  
**Status:** Foundation / active development  
**Powered by Ojenene.com & Qazeem Basit Oladimeji**

## Product goals

Farmers evolves the supplied PoultryPlus workflow into a database-backed SaaS application with:

- Multi-tenant farm/workspace isolation
- Owner and staff accounts
- Granular, server-enforced permissions
- Email registration, verification, login and password reset
- Modular farm features that can be enabled/extended independently
- Superuser-only MCP and platform settings
- Installable PWA experience on phone, tablet and desktop
- Native-app-style mobile shell with bottom navigation
- Browser-based installation wizard for shared hosting
- MySQL/MariaDB persistence
- Feature request workflow for farmers
- API-ready architecture

The initial product is free to use. Billing is intentionally not active.

## Stack

- Laravel 12
- PHP 8.2+
- MySQL 8+ / MariaDB 10.6+
- Blade
- Vanilla JavaScript
- Chart.js via CDN
- PWA manifest + service worker
- Laravel Mail / Notifications

No Node.js runtime is required in production for the initial build.

## Current milestone

The first milestone establishes:

- Laravel application skeleton
- Installation wizard
- Environment/database validation
- Superuser provisioning during install
- Core tenant schema
- Farm ownership/membership model
- Module registry and module database records
- Authentication-ready routes and views
- Farmer dashboard shell
- Responsive desktop sidebar
- Native-style mobile bottom navigation
- PWA manifest/service worker/install page
- Initial Batch module
- Feature request schema
- Audit log schema
- Shared-hosting deployment documentation

Further CRUD modules are implemented incrementally while preserving the same module registry and tenant boundary.

## Installation

### Recommended: browser installation wizard

1. Point your domain document root to the repository's `public/` directory.
2. Ensure PHP 8.2+ is enabled.
3. Create an empty MySQL/MariaDB database and database user.
4. Copy `.env.example` to `.env`.
5. Make `storage/` and `bootstrap/cache/` writable.
6. Visit `https://farmers.ng.cyou/install`.
7. The wizard checks PHP, extensions, writable directories, and database connectivity.
8. Enter application/database details and the first Superuser account.
9. The installer writes the environment configuration, runs migrations, registers core modules, creates the Superuser and writes an install lock.

After successful installation, `/install` is locked.

### Optional installer protection

Before exposing a fresh deployment publicly, set:

```env
INSTALLER_TOKEN=replace-with-a-long-random-value
```

Then open:

```text
https://farmers.ng.cyou/install?token=replace-with-a-long-random-value
```

When `INSTALLER_TOKEN` is configured, the installer rejects requests without the correct token.

### CLI fallback

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ModuleSeeder --force
php artisan storage:link
php artisan optimize
```

Create the first Superuser through the browser installer where possible.

## Shared-hosting notes

The production web root must point to `public/`. Do not expose the project root directly.

Writable directories:

```text
storage/
bootstrap/cache/
```

Suggested permissions are normally `775`, subject to the hosting provider.

Scheduler:

```text
* * * * * php /absolute/path/to/artisan schedule:run >/dev/null 2>&1
```

## Environment

```env
APP_NAME="Farmers"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://farmers.ng.cyou

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

INSTALLER_TOKEN=
```

Never commit real credentials or production secrets.

## Tenancy

Every farm-owned record is server-scoped to its farm/workspace. Tenant isolation is enforced through authenticated membership checks, scoped queries, policies/middleware, foreign keys, and request validation. A farm ID received from the browser is never trusted by itself.

## Account hierarchy

### Superuser

Platform-wide control for farmers/farms/users, modules, feature requests, system settings, audit logs, MCP configuration, health and maintenance. MCP and platform settings are Superuser-only.

### Farm Owner

Can manage their farm profile, batches, staff, roles/permissions, farm modules, reports and feature requests.

### Staff

Staff access is limited to assigned farms and permissions.

## Modules

The module registry reserves keys for Dashboard, Farm Profile, Batch Management, Daily Records, Feed, Medication & Vaccination, Mortality, Egg Production, Sales, Expenses, Inventory, Weekly Body Weight, Performance, Profit Calculator, Reports, Team, Notifications, Feature Requests, Audit Log, and Backup / Export.

New functionality should be added through the module registry instead of tightly coupling it to the core application.

## PWA

The app includes:

- `public/manifest.webmanifest`
- `public/sw.js`
- offline fallback
- install page at `/install-app`
- standalone mobile display
- mobile safe-area support
- native-style bottom navigation

On iPhone/iPad, install through Safari → Share → Add to Home Screen.

## Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ModuleSeeder
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## Security rules

- Never commit `.env`.
- Never expose installer/database/MCP secrets.
- Never use frontend filtering as tenant security.
- All destructive operations require authorization.
- Sensitive Superuser/MCP routes require server-side guards.
- Feed cost remains the source of truth for feed expenditure and must not be double-counted as a normal expense.

## Roadmap

1. Foundation + installer + responsive application shell
2. Authentication + onboarding + email verification/reset
3. Farm/team/permission workflows
4. Batch + daily records
5. Feed/medication/mortality/egg production
6. Sales/expenses/inventory
7. Performance/calculator/reports
8. Push notifications and reminders
9. Superuser control center + MCP controls
10. API v1 and extended agricultural modules

## License / ownership

Copyright © Farmers project owners. All rights reserved unless a separate license is added.
