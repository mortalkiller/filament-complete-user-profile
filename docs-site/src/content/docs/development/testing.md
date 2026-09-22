---
title: Testing
description: Run the package's tests, static analysis and documentation checks.
---

## Package checks

The package exposes:

```bash
composer validate --strict
composer check
```

`composer check` runs:

1. Pint in check mode;
2. PHPStan;
3. PHPUnit.

You can also run them separately:

```bash
composer lint
composer analyse
composer test
```

## Routed-page browser checks

The local workbench includes a `Billing` page section with a local refresh action and no
external payment calls. After preparing the workbench, run
`npm run test:browser -- tests/Browser/account-pages.spec.mjs`. Repeat with
`WORKBENCH_SPA=false` and with `WORKBENCH_HEADER_MODE=sticky` or `compact` to cover both
navigation modes and header variants. The default header mode is `normal`.

The browser tests exercise desktop/mobile navigation, active state, native actions,
direct URLs, reload and history. The preparation command resets the local demo database;
do not use it against data you want to retain.

## CI matrix

The repository test workflow exercises the supported PHP matrix defined by the project. The code-quality workflow separately validates Composer metadata, style, static analysis, PHP syntax and the distribution archive.

Documentation work must not weaken or bypass those workflows.

## Documentation site

From `docs-site/`:

```bash
npm install --no-audit --no-fund
npm run build
```

The static output is written to:

```text
docs-site/dist/
```

Do not commit `dist/`.

## Before a pull request

At minimum verify:

- package checks still pass when runtime code was touched;
- Starlight builds successfully;
- generated links stay below `/filament-complete-user-profile/`;
- favicon, manifest and browserconfig URLs use the base path;
- public examples contain no private application or infrastructure details.
