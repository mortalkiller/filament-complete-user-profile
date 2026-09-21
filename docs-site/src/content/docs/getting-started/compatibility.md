---
title: Compatibility and requirements
description: Runtime requirements and optional dependencies.
---

## Runtime requirements

The package currently requires:

| Dependency | Constraint |
| --- | --- |
| PHP | `^8.3` |
| Filament | `^5.7` |
| Laravel components | `^13.0` |

The package description targets Filament 5 and Laravel 13.

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

The repository test workflow validates PHP 8.3, 8.4 and 8.5 against the package constraints. Use the repository CI as the authoritative compatibility check for an unreleased branch.
