# HuntRecipes

HuntRecipes is a family recipe website that brings the *Summers with the Hunts* reunion cookbook online. Visitors can browse and search recipes; registered members can manage recipes and favorites. The application also includes user and chef administration, email-based account flows, and optional OpenAI-powered recipe image generation.

## Stack

- PHP 8.2 with a custom `HuntRecipes` autoloader and Composer
- Twig 3 templates
- MySQL through PHP's `mysqli` extension
- JavaScript and Sass built with Webpack 5 and npm
- Bootstrap 5, jQuery, and DataTables in the browser
- PHPUnit 11 for the PHP test suite
- Apache-compatible configuration in `.htaccess`

The main web entry point is `index.php`. Page entry points live in directories such as `home/`, `recipes/`, and `account/`; JSON API endpoints live under `api/v1/`. The frontend bundle starts at `lib/js/app.js` and Webpack writes generated assets to `js/` and `css/`.

## Requirements

- PHP 8.2.x
- PHP extensions: JSON and MySQLi
- Composer
- MySQL-compatible database server
- Node.js and npm for rebuilding frontend assets
- A web server whose document root is this repository

The repository does not currently declare a supported Node.js version. **TODO:** document and enforce one (for example with an `.nvmrc` or the `engines` field in `lib/package.json`). Production uses Apache-specific behavior from `.htaccess`, including custom error pages and maintenance mode.

## Setup

1. Install the PHP dependencies from the repository root:

   ```bash
   composer install
   ```

2. Create the local environment file:

   ```bash
   cp .env.example .env
   ```

   Replace the placeholder values and add the variables described in [Environment variables](#environment-variables). Do not commit `.env`.

3. Prepare the database and grant the configured user access to it.

   The application currently connects to a database named `saboldru_recipes`, which is hard-coded in `includes/HuntRecipes/Database/SqlController.php`. No schema or migration files are included. **TODO:** add a reproducible schema/migration workflow and make the database name configurable.

4. Install and build the frontend dependencies:

   ```bash
   cd lib
   npm ci
   npm run build
   cd ..
   ```

5. Point the web server's document root at the repository root. For basic local development, PHP's built-in server can serve the application:

   ```bash
   php -S 127.0.0.1:8000 -t .
   ```

   Then open <http://127.0.0.1:8000/>. The built-in server does not apply `.htaccess`, so Apache-specific error and maintenance behavior is not available with this command.

The root route redirects signed-in users to `/home/` and other visitors to `/welcome/`. A database health endpoint is available at `/api/v1/system/ping/`.

## Environment variables

Environment variables are loaded from the root `.env` file by `vlucas/phpdotenv`.

| Variable | Purpose | When needed |
| --- | --- | --- |
| `DB_HOST` | MySQL host | Required at application bootstrap |
| `DB_USERNAME` | MySQL username | Required at application bootstrap |
| `DB_PASSWORD` | MySQL password | Required at application bootstrap |
| `PRODUCTION` | Boolean-like flag controlling error display and development-only behavior | Set for all environments (`0` locally, `1` in production) |
| `MAIL_HOST` | SMTP server hostname | Email and account-recovery flows |
| `MAIL_PORT` | SMTP server port; the mailer currently uses SSL | Email and account-recovery flows |
| `MAIL_USERNAME` | SMTP username and From address | Email and account-recovery flows |
| `MAIL_PASSWORD` | SMTP password | Email and account-recovery flows |
| `EMAIL_CONTACT` | Recipient for contact-form submissions | Contact form |
| `OPENAI_API_KEY` | OpenAI API key | AI recipe prompt and image generation |

Only the three database variables are currently validated by the bootstrap code. Keep all credentials out of version control.

## Scripts and commands

PHP dependencies are managed at the repository root. `composer.json` does not currently define Composer scripts.

Frontend commands run from `lib/`:

| Command | Description |
| --- | --- |
| `npm run build` | Create minified production bundles in `js/app.min.js` and `css/style.css` with source maps |
| `npm run watch` | Watch frontend sources and rebuild production bundles |
| `npm run watch-dev` | Watch frontend sources and write the development JavaScript bundle to `js/app.dev.js` |

The `js/` and `css/` outputs are generated artifacts. Edit sources in `lib/js/` and `lib/scss/` instead.

## Tests

Install development dependencies with `composer install`, configure the required database environment values, and run:

```bash
vendor/bin/phpunit tests --do-not-cache-result --colors
```

This is the command used by GitHub Actions with PHP 8.2. The suite contains unit tests as well as code paths that bootstrap the application; there is no separate test database configuration or checked-in PHPUnit configuration. **TODO:** document test database isolation and fixtures before running database-backed tests against shared data.

No frontend test script is currently defined.

## Project structure

```text
.
├── account/, admin/       Account and administration page entry points
├── api/v1/                Versioned JSON API endpoints
├── assets/                Images, favicons, measurement data, and raw recipe data
├── css/                   Generated CSS bundle
├── includes/HuntRecipes/  PHP domain, database, endpoint, email, and user classes
├── js/                    Generated JavaScript bundle and bundled assets
├── lib/                   Frontend source, npm manifest, and Webpack configuration
├── tests/Tests/           PHPUnit tests
├── views/                 Twig layouts, pages, partials, emails, and recipe views
├── composer.json          PHP dependencies and PHP version constraint
├── index.php              Root web entry point and redirect
└── .htaccess              Apache error-page and maintenance-mode behavior
```

## License

No repository-level license file is present. **TODO:** choose a license and add it as `LICENSE`. The frontend package metadata in `lib/package.json` currently declares ISC, but that does not by itself document licensing for the whole repository.
