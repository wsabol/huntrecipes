# HuntRecipes

HuntRecipes is a Laravel 12 family recipe application. It retains the existing Twig and Bootstrap presentation while using Laravel routing, middleware, Eloquent/query builder, API resources, policies, validation, storage, mail, scheduling, and Vite.

Authentication is passwordless and owned by Firebase Authentication:

- Google OAuth
- Firebase email-link sign-in
- Secure, HTTP-only Firebase session cookies

The application never presents, accepts, imports, or resets passwords. MySQL remains authoritative for profiles, account state, roles, chef links, recipes, applications, and favorites.

## Requirements

- PHP 8.2 and Composer
- MySQL 8-compatible database
- Node.js 22 and npm
- Separate Firebase projects for development and production
- An Apache document root pointing to `public/`

PHP 8.2 and Laravel 12 require a follow-up upgrade before December 2026.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan storage:link
php artisan migrate
php artisan serve --port=8001
```

Configure the database and Firebase browser settings in `.env`. Set `FIREBASE_CREDENTIALS` to an absolute service-account JSON path outside the repository. Enable Google and Email/Password in Firebase Authentication; Firebase requires the latter for email links, but HuntRecipes does not expose password authentication. Add the application host to Firebase authorized domains and use `/auth/email/finish` as the email-link callback.

Run `composer test:firebase-emulator` to exercise sign-in, session cookies, provider linking and revocation against the Firebase Auth Emulator. It needs Node and no credentials, and CI runs it too. `FIREBASE_AUTH_EMULATOR_HOST` disables token signature checks, so the app refuses to boot in production when it is set. The manual checks against real Firebase projects (Google OAuth, email delivery, authorized domains, HTTPS cookies) are listed in [docs/firebase-verification.md](docs/firebase-verification.md).

## Existing account migration

Back up the database and uploads first, then inspect the migration without writing:

```bash
php artisan huntrecipes:migrate-firebase-users --dry-run
```

Run the command without `--dry-run` to create deterministic `hr_<id>` Firebase identities and write their UIDs locally. Use `--from-id=<id>` to resume after an interruption. The command skips accounts already migrated and records structured progress in Laravel logs.

After restoring the uploads backup, migrate legacy recipe and profile uploads onto Laravel's public disk:

```bash
php artisan huntrecipes:migrate-uploads --source=/absolute/path/to/legacy/assets --dry-run
php artisan huntrecipes:migrate-uploads --source=/absolute/path/to/legacy/assets
```

Legacy password/session tables and password hashes should remain as inaccessible rollback data for 30 days after deployment, then be removed in a separately reviewed migration.

## Scheduled work

Configure the host cron once:

```text
* * * * * cd /absolute/path/to/huntrecipes/current && php artisan schedule:run >> /dev/null 2>&1
```

Laravel schedules the daily featured recipe and chef command. There is no public operations endpoint.

## Verification

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm run build
```

CI additionally runs the schema migration against MySQL 8.4. The legacy 61-test behavioral baseline remains in the suite while coverage expands around Laravel routes, Twig rendering, Firebase sessions, policies, validation, and adversarial ownership cases.

## Deployment checklist

1. Capture a schema-only production dump, a full database backup, and an uploads backup.
2. Verify the production MySQL version and compare the dump to the guarded adoption migration.
3. Put the existing application into maintenance mode and retain the prior release.
4. Install production dependencies and build assets.
5. Run migrations, `storage:link`, and the Firebase user migration.
6. Run `config:cache`, `route:cache`, and `view:cache` where supported.
7. Point Apache at `public/`, configure HTTPS, and start the scheduler cron.
8. Smoke-test public pages, logout, account access, recipe ownership, developer review, uploads, mail, and `/up`, and complete the production section of [docs/firebase-verification.md](docs/firebase-verification.md).
9. Preserve the Firebase migration audit for rollback.

The production schema dump and real Firebase credentials are intentionally not committed.

## Production workflow configuration

Production deployment runs from `main` through `deploy-production.yml`, after its reusable `run-tests.yml` quality gate passes. Configure a protected GitHub environment named `production` with:

- Secrets: `HOST`, `USER`, `PRIVATE_KEY`, `PORT`, `PATH_TO_REPO`, and `PHP_BIN`.
- Optional secret: `PASSPHRASE` when the deployment key is encrypted.

On the host, create `PATH_TO_REPO/shared/.env` before the first deployment. It must use `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, and an absolute `FIREBASE_CREDENTIALS` path outside the release tree. The workflow packages a clean release, activates it through `current`, checks `/up`, database connectivity, passwordless sign-in, compiled assets, and sensitive-path isolation, and automatically restores the previous release if the health check fails. Database migrations are intentionally not rolled back automatically.
