---
title: Tenant-scoped API tokens
description: Bind Sanctum tokens to Filament, stancl/tenancy, or another active tenant context.
---

Tenant-scoped tokens are opt-in and intentionally fail closed.

The package uses one tenancy resolver for the complete tenant-token lifecycle:

- token creation;
- token listing;
- token revocation;
- API request enforcement through `EnsureTokenContext`.

## Requirements

You need all of the following:

1. Laravel Sanctum and `HasApiTokens`.
2. An active tenant/context resolved as an Eloquent model.
3. `context_type` and `context_id` columns on `personal_access_tokens`.
4. `EnsureTokenContext` after `auth:sanctum` on tenant-sensitive API routes.

## Native Filament tenancy

No resolver configuration is required when the application uses Filament's native tenancy. The package defaults to `Filament::getTenant()`:

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;

CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->tenantScoped()
        ->abilities([
            'projects:read' => 'Read projects',
        ]));
```

## Publish the context migration

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-token-migrations
php artisan migrate
```

The opt-in migration adds nullable `context_type` and `context_id` metadata to Sanctum's personal access token table.

In multi-database or database-per-tenant applications, the columns must exist on the connection used by Sanctum's configured `PersonalAccessToken` model. The package resolves the user's actual `tokens()` relationship and checks that related model's connection; it does not assume the request's current/default database connection.

## Custom tenancy resolver

For a non-Filament tenancy system, implement:

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver
```

The resolver returns the active Eloquent tenant/context model or `null`.

### stancl/tenancy / archtechx/tenancy

```php
namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;

final class StanclTenancyResolver implements TenancyResolver
{
    public function resolve(): ?Model
    {
        $tenant = tenant();

        return $tenant instanceof Model ? $tenant : null;
    }
}
```

Register it on the same Filament panel plugin:

```php
use App\Support\Tenancy\StanclTenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;

CompleteUserProfilePlugin::make()
    ->tenancyResolver(StanclTenancyResolver::class)
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->tenantScoped()
        ->abilities([
            'projects:read' => 'Read projects',
        ]));
```

The package does not depend on `stancl/tenancy`. The adapter belongs to the consuming application, so the same API works with any tenancy package.

`tenancyResolver()` accepts:

```php
->tenancyResolver(StanclTenancyResolver::class)
->tenancyResolver(new StanclTenancyResolver())
->tenancyResolver(fn (): ?Model => tenant() instanceof Model ? tenant() : null)
```

Class-strings are the recommended form because Laravel resolves constructor dependencies through the container.

## Protect API routes

Place the package middleware after Sanctum authentication:

```php
use Mortalkiller\FilamentCompleteUserProfile\Http\Middleware\EnsureTokenContext;

Route::middleware([
    'auth:sanctum',
    EnsureTokenContext::class,
])->group(function (): void {
    // Tenant-sensitive API routes...
});
```

The middleware uses the same tenancy resolver configured for token management.

## Fail-closed behavior

Tenant-aware token operations fail when no active tenant can be resolved.

The middleware returns HTTP 403 when:

- no authenticated API token is available;
- the resolver returns no active context;
- the token does not contain valid context metadata;
- the token context does not match the active context.

Token creation, listing, and revocation are all scoped through the same resolver, so a token created for one context cannot be managed or accepted from another context through the package flow.

## Backwards compatibility

The previous `TokenContextResolver` contract remains available and extends `TenancyResolver`. Existing container bindings continue to work. New integrations should use `TenancyResolver` and configure it with `tenancyResolver()`.
