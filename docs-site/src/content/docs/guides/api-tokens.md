---
title: API tokens
description: Manage Laravel Sanctum personal access tokens from the account center.
---

API token management is disabled by default and uses Laravel Sanctum.

## Install Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Add `HasApiTokens` to the authenticatable model:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
```

## Enable tokens with an ability whitelist

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;

CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'projects:read' => 'Read projects',
            'projects:write' => 'Manage projects',
        ])
        ->defaultExpiration(30)
        ->maxExpiration(90));
```

An explicit ability whitelist is required. Token abilities outside that list are rejected by the package token manager, and wildcard `*` is not treated as a substitute for the configured whitelist.

## Expiration

- `defaultExpiration(?int $days)` supplies the default number of days.
- `maxExpiration(?int $days)` caps the selectable expiration.
- `null` leaves the corresponding package limit unset.

## Plaintext token lifecycle

Sanctum returns the plaintext token only when it is created. The account UI displays it in the creation confirmation flow and clears it from Livewire state when that modal is completed.

Existing tokens can be listed and revoked, but their plaintext value cannot be recovered.

For tenant-specific tokens, use the stricter [Tenant-scoped API tokens](../tenant-scoped-tokens/) flow.
