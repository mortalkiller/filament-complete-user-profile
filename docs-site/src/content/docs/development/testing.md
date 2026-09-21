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
