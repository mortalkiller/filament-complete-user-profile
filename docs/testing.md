# Demo and browser testing

The repository includes a local workbench for inspecting the package in a real Filament panel.

From the repository root:

```bash
composer install
composer browser:prepare
php workbench/artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000/demo/profile?section=profile`.

The workbench is a local testing application. It uses SQLite, creates a fictional demo user, stores package-owned profile data in the package profile table, and must not be exposed publicly.

Run the browser showcase suite with:

```bash
npm install
npx playwright install chromium
npm run test:browser -- tests/Browser/profile-showcase.spec.mjs
```

The showcase captures the real Filament account page in desktop/mobile and light/dark modes. Generated screenshots are written below `test-results/`; inspect them before copying the selected captures to `docs/screenshots/`.

The README screenshots use fictional account data and are direct browser captures. They are not reconstructed UI mockups.

[Back to the README](../README.md)
