---
title: API Tokens feature
description: Reference for Sanctum token abilities, expiration and tenant scoping.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens
```

The feature extends `AbstractFeature`, has default sort `50`, and is disabled by default by the plugin.

## Configuration methods

```php
abilities(array $abilities): static
defaultExpiration(?int $days): static
maxExpiration(?int $days): static
tenantScoped(bool $condition = true): static
```

`abilities()` accepts an associative `array<string, string>` where keys are abilities and values are labels.

## Accessors

```php
getAbilities(): array
getDefaultExpiration(): ?int
getMaxExpiration(): ?int
isTenantScoped(): bool
```

Defaults:

| Property | Default |
| --- | --- |
| abilities | `[]` |
| default expiration | `null` |
| max expiration | `null` |
| tenant scoped | `false` |

## Requirement check

```php
getRequirementIssue(Authenticatable $user): ?string
```

When enabled, the feature validates the infrastructure visible to the current application:

1. Sanctum is installed.
2. The user exposes `createToken()` and `tokens()`, normally through `HasApiTokens`.
3. The ability whitelist is not empty.
4. If tenant-scoped, `personal_access_tokens` contains `context_type` and `context_id`.

The method returns the first translated requirement issue or `null` when ready.

Tenant-scoped management and API request enforcement both use the package `TenancyResolver`. Filament native tenancy is the default; applications can replace it through `CompleteUserProfilePlugin::tenancyResolver()`.
