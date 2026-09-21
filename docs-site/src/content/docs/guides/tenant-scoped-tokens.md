---
title: Tenant-scoped API tokens
description: Bind Sanctum tokens to the active tenant or application context.
---

Tenant-scoped tokens are opt-in and intentionally fail closed.

## Requirements

You need all of the following:

1. Laravel Sanctum and `HasApiTokens`.
2. An active Filament tenant when a token is created, listed or revoked.
3. `context_type` and `context_id` columns on `personal_access_tokens`.
4. A `TokenContextResolver` container binding.
5. `EnsureTokenContext` after `auth:sanctum` on tenant-sensitive API routes.

## Enable tenant scoping

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

## Bind the context resolver

The resolver must return the active Eloquent model that represents the context for the current API request:

```php
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;

$this->app->bind(TokenContextResolver::class, function (): TokenContextResolver {
    return new class implements TokenContextResolver {
        public function resolve(): ?Model
        {
            $workspace = request()->route('workspace');

            return $workspace instanceof Workspace ? $workspace : null;
        }
    };
});
```

Use your own routing and tenancy mechanism. The package does not guess the current context.

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

## Fail-closed behavior

The middleware returns HTTP 403 when:

- no authenticated API token is available;
- the resolver is not bound;
- the resolver returns no active context;
- the token does not contain valid context metadata;
- the token context does not match the active context.

Token creation also fails when tenant scoping is enabled but no active Filament tenant is available. Listing and revocation are scoped to the active tenant.

Do not remove the resolver or middleware requirement merely because token creation already writes context metadata. Both sides are part of the package's security boundary.
