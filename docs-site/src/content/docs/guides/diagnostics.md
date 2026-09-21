---
title: Diagnostics
description: Validate the package installation and optional capabilities.
---

Run:

```bash
php artisan filament-complete-user-profile:check
```

The command inspects the registered Filament panels and the infrastructure required by enabled package capabilities.

## Result levels

- **PASS** — an enabled requirement is correctly configured.
- **INFO** — informational state, commonly for optional capabilities that are not enabled.
- **FAIL** — an enabled capability or required package setup is not usable.

The command exits with code `0` when the enabled configuration is ready and code `1` when a required check fails.

## Categories checked

Depending on the configuration, the command validates areas such as:

- plugin registration on at least one Filament panel;
- authenticatable user model resolution;
- selected profile storage mode;
- configured avatar and locale columns;
- authenticator-app MFA requirements;
- email MFA requirements;
- database session requirements;
- Sanctum availability and `HasApiTokens`;
- tenant-token context migration and resolver binding;
- page header plugin registration, reporting whether it was registered by this package or by
  the application, and its configured mode.

A disabled optional capability should not make the command fail merely because its infrastructure is absent.

Use the checker after installation and whenever you enable one of the optional security features.
