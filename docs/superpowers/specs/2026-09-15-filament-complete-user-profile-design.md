# Filament Complete User Profile — Design Specification

## Goal

Build a Laravel-style, Filament-native account center for Filament 5 that provides profile management, password management, optional MFA, optional browser sessions, and optional Sanctum API tokens through a small, composable API.

The package reuses native Laravel 13 and Filament 5 capabilities whenever they already solve the problem. The package owns composition, UX, configuration, validation, diagnostics, and safe integration rather than reimplementing authentication or token primitives.

## Platform

- PHP `^8.3`
- Laravel `^13.0`
- Filament `^5.7`
- Laravel Sanctum `^4.3` only when API tokens are enabled
- Database sessions only when session management is enabled

Optional features must not make their dependencies mandatory when disabled.

## Principles

1. Zero-configuration installation for the common case.
2. Public API reads like Laravel / Filament code.
3. Prefer native Filament and Laravel components.
4. Features are independently enabled or disabled.
5. Never silently weaken authentication or tenant isolation.
6. Migrations touching application-owned tables are defensive.
7. Package-owned tables may migrate and roll back normally.
8. Normal customization does not require publishing views.
9. No hard dependency on tenancy packages, Fortify, or social-auth providers.
10. Advanced extension points stay out of the way of the default API.

## Defaults

| Feature | Default |
| --- | --- |
| Overview | ON |
| Profile | ON |
| Avatar | ON |
| Name | ON |
| Email | ON |
| Locale | ON |
| Password | ON |
| Authenticator-app MFA | OFF |
| Email MFA | OFF |
| Sessions | OFF |
| API Tokens | OFF |

## Canonical public API

The normal configuration surface is hierarchical: the plugin root selects a feature and the feature callback configures that feature.

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;

CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Sidebar)
    ->profile(fn (Profile $profile): Profile => $profile
        ->locale(['pt', 'en', 'es', 'fr']))
    ->security(fn (Security $security): Security => $security
        ->password()
        ->appAuthentication()
        ->emailAuthentication())
    ->sessions()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ])
        ->defaultExpiration(30)
        ->maxExpiration(90)
        ->tenantScoped());
```

Explicit disabling remains available:

```php
CompleteUserProfilePlugin::make()
    ->overview(false)
    ->profile(false)
    ->security(false)
    ->sessions(false)
    ->apiTokens(false);
```

Feature-level runtime controls remain available through `enabled()`, `visible()`, and `sort()`.

The canonical API deliberately does not provide duplicate `*With()` methods or plugin-root shortcuts for Security internals.

## Navigation

Tabs are the default. `navigation(AccountNavigationLayout::Sidebar)` switches to Filament's native page sub-navigation. Sidebar selection is persisted in the Livewire `section` URL state and navigation URLs always target the canonical Filament profile route.

## Profile

Default fields are Avatar, Name, Email, and Locale.

The feature API is:

```text
avatar()
name()
email()
locale()
fields()
modifyFieldsUsing()
mutateDataBeforeSaveUsing()
afterSave()
```

`locale(['pt', 'en'])` resolves common native language names automatically. Associative arrays remain authoritative when the host application supplies custom labels. Automatic locale source priority is `app.available_locales`, then `app.supported_locales`, then `app.locale`.

Custom fields belong to the host application's domain model; the package does not create a generic custom-fields JSON column.

## Security

Password management is enabled by default and uses the active Filament guard plus Laravel password validation/hashing.

Authenticator-app MFA is configured with:

```php
->security(fn (Security $security): Security => $security
    ->appAuthentication())
```

It uses Filament's native recoverable `AppAuthentication` provider. TOTP is not reimplemented by this package.

Email MFA is configured with:

```php
->security(fn (Security $security): Security => $security
    ->emailAuthentication())
```

It uses Filament's native `EmailAuthentication` provider and Laravel notifications.

Both providers may be enabled simultaneously. The package's `HasMultiFactorAuthentication` contract and `InteractsWithMultiFactorAuthentication` trait remain the storage integration for authenticator-app MFA. `InteractsWithEmailAuthentication` provides compatible email-MFA state persistence for both package storage modes.

External login flows must not bypass Filament's native MFA challenge flow.

## Reauthentication

Sensitive operations share the `Reauthentication` contract. The default implementation confirms the current password when available. Passwordless or external-auth applications may bind another secure implementation.

Expected consumers include password changes, session revocation, and sensitive token operations.

## Sessions

Sessions are disabled by default and support Laravel database sessions in v1.

```php
CompleteUserProfilePlugin::make()->sessions();
```

The UI lists only the authenticated user's sessions, protects the current session, and supports revoking one other session or all other sessions after reauthentication.

## API Tokens

API-token management uses Laravel Sanctum and is disabled by default.

```php
CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'customers:read' => 'Read customers',
        ])
        ->defaultExpiration(30)
        ->maxExpiration(90));
```

Abilities are an application-owned whitelist. Wildcard abilities are never granted automatically. Plaintext tokens are displayed exactly once after creation.

Tenant-scoped tokens are opt-in with `tenantScoped()`. Context storage remains generic (`context_type`, `context_id`), and API enforcement depends on the host application's `TokenContextResolver` binding plus `EnsureTokenContext` middleware.

A missing context or context mismatch fails closed.

## Storage

The structural config contains infrastructure decisions only:

```php
return [
    'user_model' => null,
    'storage' => 'user',
    'columns' => [
        'avatar' => 'avatar_url',
        'locale' => 'locale',
        'mfa' => [
            'secret' => 'app_authentication_secret',
            'recovery_codes' => 'app_authentication_recovery_codes',
            'email_enabled' => 'has_email_authentication',
        ],
    ],
    'profile_table' => 'filament_user_profiles',
];
```

`storage = user` stores package-managed data on the authenticatable table. `storage = separate` uses the package-owned `filament_user_profiles` table. Application-owned columns are never destructively dropped on rollback.

## Diagnostics

The package provides:

```bash
php artisan filament-complete-user-profile:check
```

It validates only enabled/relevant capabilities, including profile storage, configured columns, authenticator-app MFA, email MFA, database sessions, Sanctum integration, token abilities, and tenant-token infrastructure. Any enabled capability that is not ready causes a non-zero exit code.

## Extension points

Stable application-facing extension points are intentionally small:

- feature visibility and sorting;
- profile field additions/customization;
- profile data mutation hooks;
- user model resolution;
- storage mode and column mapping;
- `Reauthentication`;
- `SessionStore`;
- `TokenContextResolver`;
- API-token ability definitions.

## Roadmap boundary

Passkeys/WebAuthn are not part of the current implementation. The exploratory direction is documented in `docs/roadmap.md`. Fortify is not required by the current architecture.

## API simplification record

The final pre-release public API decisions are specified in `docs/superpowers/specs/2026-09-15-api-simplification-design.md`.
