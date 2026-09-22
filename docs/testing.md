# Demo and browser testing

The repository includes a full local workbench for inspecting the package in a real Filament panel.

From the repository root:

```bash
composer install
composer browser:prepare
php workbench/artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000/demo/profile`.

The workbench enables every built-in account area:

- Overview
- Profile, avatar and locale
- Password management
- Authenticator-app MFA with recovery codes
- Email MFA
- Browser session management
- Sanctum API tokens with configured abilities and expiration limits
- Custom `job_title` and `phone` fields persisted on the demo user
- An `Application Settings` section persisted with `spatie/laravel-settings`
- An `Addresses` section backed by a `DemoUser::addresses()` Eloquent relationship
- A routed `Billing` section at `/demo/profile/billing`, with local demo data and a native refresh action (no payment provider)

The fictional demo user is `alex@example.test`. Use `workbench-password` whenever a reauthentication form asks for the current password.

Browser sessions use Laravel's database session driver. The workbench also creates a secondary fictional Safari/macOS session so revoke actions can be tested without another browser.

Email MFA uses the `log` mailer. Verification codes are written to `workbench/storage/logs/laravel.log`, so no external mail service is required.

API token management uses a local Sanctum `personal_access_tokens` table and the following demo abilities: `profile:read`, `profile:update`, and `security:read`. Tenant-scoped tokens are an integration mode and remain application-specific because they require the consuming application's tenant model and `TokenContextResolver`.

The workbench is a local testing application backed by SQLite. It stores package-owned profile data in the package profile table and must not be exposed publicly. `spatie/laravel-settings` is installed only in `require-dev` for the integration example and is not a runtime dependency of the package.

Run the browser showcase suite with:

```bash
npm install
npx playwright install chromium
npm run test:browser -- tests/Browser/profile-showcase.spec.mjs
npm run test:browser -- tests/Browser/account-pages.spec.mjs
```

The browser suite verifies that every built-in account area and both extension examples are available with their required infrastructure. It captures Overview, Profile, Security, Sessions, API Tokens, Application Settings, and Addresses at desktop width in light/dark modes, plus the Profile area at mobile width. Generated screenshots are written below `test-results/`; inspect them before copying the selected captures to `docs/screenshots/`.

The README screenshots use fictional account data and are direct browser captures. They are not reconstructed UI mockups.

The routed-page tests cover desktop tabs, mobile navigation, active state, a native action,
direct URLs, reload and browser history. Run them with `WORKBENCH_SPA=false` as well as the
default SPA mode. `WORKBENCH_HEADER_MODE=normal|sticky|compact` selects the header mode.
If Chromium is already installed outside Playwright's default cache, set
`PLAYWRIGHT_CHROMIUM_EXECUTABLE` to that executable. Do not run `browser:prepare` against
demo data you want to retain: it includes `migrate:fresh`.

[Back to the README](../README.md)
