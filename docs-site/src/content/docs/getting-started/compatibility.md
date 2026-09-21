---
title: Compatibility and requirements
description: Runtime requirements and optional dependencies.
---

## Runtime requirements

The package currently requires:

| Dependency | Constraint |
| --- | --- |
| PHP | `^8.3` |
| Filament | `>=5.7.6 <6.0.0` |
| Laravel components | `^13.0` |

Version 1 supports Filament 5 only. Filament 4 and Filament 6 are intentionally outside the supported range. The minimum is Filament 5.7.6 because earlier 5.x versions are affected by known MFA security advisories that are relevant to this package's authenticator-app MFA integration.

## Optional infrastructure

Some capabilities need host-application infrastructure that is intentionally not installed automatically.

| Capability | Additional requirement |
| --- | --- |
| Authenticator-app MFA | User model implements the package MFA contract and trait. |
| Email MFA | Filament email-MFA contract, package storage trait, Laravel notifications. |
| Browser sessions | Laravel database session driver and sessions table. |
| API tokens | Laravel Sanctum and `HasApiTokens`. |
| Tenant-scoped API tokens | Sanctum, context columns, active tenant/context, resolver binding and middleware. |

Laravel Sanctum is a Composer suggestion rather than a required runtime dependency.

## Storage

The default `user` storage mode uses package-managed columns on the configured authenticatable model. The alternative `separate` mode uses the package-owned `filament_user_profiles` table.

The migrations check configured columns before adding them, allowing applications to reuse existing equivalent columns.

## Supported development matrix

The repository test workflow validates PHP 8.3, 8.4 and 8.5 against both the lowest supported dependency set and the latest dependency versions allowed by Composer. This verifies the Filament 5 range from the secure 5.7.6 baseline through the newest compatible 5.x release while explicitly rejecting any resolved non-5.x major. CI also runs `composer audit` against the resolved lock file.
