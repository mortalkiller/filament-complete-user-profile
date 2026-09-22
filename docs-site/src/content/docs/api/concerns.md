---
title: Public concerns and traits
description: Traits for native account pages and model-backed Filament MFA storage.
---

## InteractsWithAccountSection

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection
```

Use this trait on a concrete custom `Filament\Pages\Page` registered through
`AccountSection::page()`. It supplies the account header, heading, description,
breadcrumbs and sub-navigation, and hides the main-sidebar entry by default.

The boot hook requires an associated section on the current panel, including Livewire
updates. It does not replace the page's `mount()`, `canAccess()`, content or actions.
Override its presentation methods as normal PHP methods when needed. Visibility is not
authorization: keep access checks on the page and sensitive actions.

Resource, auth/profile, cluster, parameterized and `PageConfiguration` pages are not
supported. See [custom account sections](../../guides/custom-account-sections/).

## InteractsWithMultiFactorAuthentication

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication
```

Use this trait on an Eloquent authenticatable model that implements the package `HasMultiFactorAuthentication` contract.

It provides the storage methods used by Filament's authenticator-app MFA provider:

- `getAppAuthenticationSecret()`
- `saveAppAuthenticationSecret()`
- `getAppAuthenticationHolderName()`
- `getAppAuthenticationRecoveryCodes()`
- `saveAppAuthenticationRecoveryCodes()`

With `storage => 'user'`, the trait also applies encrypted casts to the configured secret and recovery-code columns and hides them from model serialization.

The trait throws `LogicException` if used where an authenticatable Eloquent model is required but not available.

## InteractsWithEmailAuthentication

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication
```

It implements package-backed storage for Filament's email-MFA state:

- `hasEmailAuthentication(): bool`
- `toggleEmailAuthentication(bool $condition): void`

With user storage, the configured email-enabled column receives a boolean cast.

The consuming model must separately implement Filament's native `HasEmailAuthentication` contract and be able to send notifications.
